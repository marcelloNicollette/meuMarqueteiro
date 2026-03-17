<?php

namespace App\Services\AI;

use App\Enums\AIProviderEnum;
use Illuminate\Support\Facades\Log;

/**
 * Abstração multi-provider para chamadas de IA.
 * Suporta OpenAI, Anthropic (Claude) e Google Gemini.
 * O provider padrão é configurado em AI_DEFAULT_PROVIDER no .env.
 */
class AIProviderService
{
    private string $provider;

    public function __construct()
    {
        // Tenta ler do banco (SystemSetting), fallback para .env
        try {
            $this->provider = \App\Models\SystemSetting::get('ai_default_provider')
                ?? config('ai.default_provider', 'anthropic');
        } catch (\Exception $e) {
            $this->provider = config('ai.default_provider', 'anthropic');
        }
    }

    public function withProvider(string $provider): static
    {
        $clone = clone $this;
        $clone->provider = $provider;
        return $clone;
    }

    /**
     * Enviar uma mensagem de chat e receber a resposta.
     *
     * @param  array  $messages  [['role' => 'user|assistant|system', 'content' => '...']]
     * @param  array  $options   temperature, max_tokens, etc.
     */
    public function chat(array $messages, array $options = []): AIResponse
    {
        return match ($this->provider) {
            'openai'    => $this->chatOpenAI($messages, $options),
            'anthropic' => $this->chatAnthropic($messages, $options),
            'gemini'    => $this->chatGemini($messages, $options),
            default     => throw new \InvalidArgumentException("Provider inválido: {$this->provider}"),
        };
    }

    /**
     * Gerar embedding vetorial para um texto.
     * Usado pelo sistema RAG para indexar documentos e buscas.
     */
    public function embed(string $text): array
    {
        // Voyage AI — provider oficial de embeddings para Anthropic
        $voyageKey = $this->getSetting('voyage_api_key', env('VOYAGE_API_KEY', ''));
        if (!empty($voyageKey)) {
            return $this->embedVoyage($text, $voyageKey);
        }

        // OpenAI — fallback se chave configurada
        $openaiKey = $this->getSetting('openai_api_key', config('ai.providers.openai.api_key', ''));
        if (!empty($openaiKey)) {
            return $this->embedOpenAI($text);
        }

        // Gemini — se for o provider ativo
        if ($this->provider === 'gemini') {
            return $this->embedGemini($text);
        }

        throw new \RuntimeException(
            'Nenhum provider de embedding configurado. '
                . 'Configure a chave Voyage AI (recomendado para Anthropic) ou OpenAI em Configurações → IA.'
        );
    }

    private function embedVoyage(string $text, string $apiKey): array
    {
        $response = \Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type'  => 'application/json',
        ])->timeout(30)->post('https://api.voyageai.com/v1/embeddings', [
            'model' => 'voyage-3',  // 1024 dims, disponível no plano gratuito
            'input' => $text,
        ])->throw()->json();

        return $response['data'][0]['embedding'];
    }

    // ─── OpenAI ────────────────────────────────────────────────────────────

    private function chatOpenAI(array $messages, array $options): AIResponse
    {
        $apiKey = $this->getSetting('openai_api_key', config('ai.providers.openai.api_key'));
        $model  = $options['model'] ?? $this->getSetting('openai_model', config('ai.providers.openai.model', 'gpt-4o-mini'));
        $client = \OpenAI::client($apiKey);

        $response = $client->chat()->create([
            'model'       => $model,
            'messages'    => $messages,
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens'  => $options['max_tokens'] ?? 2048,
        ]);

        return new AIResponse(
            content: $response->choices[0]->message->content,
            provider: 'openai',
            model: $response->model,
            tokensUsed: $response->usage->totalTokens,
            finishReason: $response->choices[0]->finishReason,
        );
    }

    private function embedOpenAI(string $text): array
    {
        $apiKey = $this->getSetting('openai_api_key', config('ai.providers.openai.api_key'));

        if (empty($apiKey)) {
            throw new \RuntimeException(
                'Chave OpenAI não configurada. O embedding requer OpenAI mesmo quando o chat usa Anthropic. '
                    . 'Configure a chave em Configurações → IA.'
            );
        }

        $client   = \OpenAI::client($apiKey);
        $response = $client->embeddings()->create([
            'model' => config('ai.providers.openai.embedding_model', 'text-embedding-3-small'),
            'input' => $text,
        ]);

        return $response->embeddings[0]->embedding;
    }

    // ─── Anthropic (Claude) ─────────────────────────────────────────────────

    private function chatAnthropic(array $messages, array $options): AIResponse
    {
        $apiKey = $this->getSetting('anthropic_api_key', config('ai.providers.anthropic.api_key'));
        $model  = $options['model'] ?? $this->getSetting('anthropic_model', config('ai.providers.anthropic.model', 'claude-sonnet-4-6'));

        $systemMessage = '';
        $chatMessages  = [];

        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $systemMessage = $msg['content'];
            } else {
                $chatMessages[] = $msg;
            }
        }

        $payload = [
            'model'      => $model,
            'max_tokens' => $options['max_tokens'] ?? 2048,
            'messages'   => $chatMessages,
        ];

        if ($systemMessage) {
            $payload['system'] = $systemMessage;
        }

        $response = \Http::withHeaders([
            'x-api-key'         => $apiKey,
            'anthropic-version' => '2023-06-01',
            'Content-Type'      => 'application/json',
        ])->timeout(120)->post('https://api.anthropic.com/v1/messages', $payload)->throw()->json();

        return new AIResponse(
            content: $response['content'][0]['text'],
            provider: 'anthropic',
            model: $response['model'],
            tokensUsed: $response['usage']['input_tokens'] + $response['usage']['output_tokens'],
            finishReason: $response['stop_reason'],
        );
    }

    // ─── Google Gemini ──────────────────────────────────────────────────────

    private function chatGemini(array $messages, array $options): AIResponse
    {
        $model    = $options['model'] ?? config('ai.providers.gemini.model', 'gemini-1.5-pro');
        $apiKey   = config('ai.providers.gemini.api_key');
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $contents = [];
        $systemInstruction = null;

        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $systemInstruction = ['parts' => [['text' => $msg['content']]]];
                continue;
            }
            $contents[] = [
                'role'  => $msg['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $msg['content']]],
            ];
        }

        $body = ['contents' => $contents];
        if ($systemInstruction) {
            $body['systemInstruction'] = $systemInstruction;
        }

        $response = \Http::post($endpoint, $body)->throw()->json();

        $text   = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $tokens = ($response['usageMetadata']['promptTokenCount'] ?? 0)
            + ($response['usageMetadata']['candidatesTokenCount'] ?? 0);

        return new AIResponse(
            content: $text,
            provider: 'gemini',
            model: $model,
            tokensUsed: $tokens,
            finishReason: $response['candidates'][0]['finishReason'] ?? 'STOP',
        );
    }

    private function embedGemini(string $text): array
    {
        $apiKey   = config('ai.providers.gemini.api_key');
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/text-embedding-004:embedContent?key={$apiKey}";

        $response = \Http::post($endpoint, [
            'model'   => 'models/text-embedding-004',
            'content' => ['parts' => [['text' => $text]]],
        ])->throw()->json();

        return $response['embedding']['values'];
    }

    // ─── Helper: ler do banco ou fallback para config ────────────────────────

    private function getSetting(string $key, mixed $default = null): mixed
    {
        try {
            return \App\Models\SystemSetting::get($key, $default);
        } catch (\Exception $e) {
            return $default;
        }
    }
}
