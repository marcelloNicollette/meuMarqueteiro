<?php

namespace App\Services\AI;

use App\Models\Conversation;
use App\Models\GeneratedContent;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Str;

class ConversationExportSuggestionService
{
    public function suggest(Conversation $conversation, Message $message): ?array
    {
        if ($message->role !== 'assistant') {
            return null;
        }

        $content = trim((string) $message->content);
        if ($content === '' || mb_strlen($content) < 180) {
            return null;
        }

        $tags = $conversation->auto_tags ?? [];
        $intent = $conversation->context['intent'] ?? null;
        $normalized = $this->normalize($content);

        $suggestion = match (true) {
            $this->looksLikeInterviewPrep($normalized) => $this->buildSuggestion('entrevista', 'Salvar como preparo de entrevista', 'A resposta ja esta em formato de preparo para entrevista.', 'alta'),
            $this->looksLikeCrisisGuidance($normalized, $tags, $intent) => $this->buildSuggestion('crise', 'Salvar como orientacao de crise', 'A resposta tem formato de orientacao de crise e pode virar rascunho interno.', 'alta'),
            $this->looksLikeSpeech($normalized) => $this->buildSuggestion('discurso', 'Salvar como discurso', 'A resposta ja pode ser aproveitada como base de fala ou pronunciamento.', 'media'),
            $this->looksLikeWhatsApp($normalized, $tags, $intent) => $this->buildSuggestion('post_whatsapp', 'Salvar como mensagem de WhatsApp', 'A resposta esta curta e direta para circulacao em WhatsApp.', 'media'),
            $this->looksLikeCommunicationPost($normalized, $tags, $intent) => $this->buildSuggestion('post_instagram', 'Salvar como post', 'A resposta ja tem cara de conteudo de comunicação e pode ir para o módulo de conteudos.', 'media'),
            $this->looksLikeStatement($normalized, $tags, $intent) => $this->buildSuggestion('comunicado', 'Salvar como comunicado', 'A resposta tem formato de posicionamento ou comunicado oficial.', 'media'),
            default => null,
        };

        if (!$suggestion) {
            return null;
        }

        $existingExport = $this->findExistingExport($conversation, $message);

        return array_merge($suggestion, [
            'title' => $this->generateTitle($conversation, $suggestion['type'], $content),
            'saved_content' => $existingExport ? $this->formatSavedContent($existingExport) : null,
        ]);
    }

    public function export(User $user, Conversation $conversation, Message $message, string $type): GeneratedContent
    {
        $existingExport = $this->findExistingExport($conversation, $message);
        if ($existingExport) {
            return $existingExport;
        }

        $content = trim((string) $message->content);
        $title = $this->generateTitle($conversation, $type, $content);

        return GeneratedContent::create([
            'municipality_id' => $user->municipality_id,
            'user_id' => $user->id,
            'conversation_id' => $conversation->id,
            'type' => $type,
            'channel' => $this->mapChannel($type),
            'tone' => $this->mapTone($conversation, $type),
            'title' => $title,
            'content' => $content,
            'variations' => [],
            'status' => 'draft',
            'tags' => $this->buildTags($conversation, $type),
            'metadata' => [
                'origin' => 'chat_export',
                'source_message_id' => $message->id,
                'intent' => $conversation->context['intent'] ?? null,
                'origin_module' => $conversation->origin_module,
                'auto_tags' => $conversation->auto_tags ?? [],
            ],
        ]);
    }

    public function isExportableType(string $type): bool
    {
        return in_array($type, ['post_instagram', 'post_facebook', 'post_whatsapp', 'discurso', 'comunicado', 'entrevista', 'crise'], true);
    }

    public function findExistingExport(Conversation $conversation, Message $message): ?GeneratedContent
    {
        return GeneratedContent::query()
            ->where('conversation_id', $conversation->id)
            ->orderByDesc('created_at')
            ->get()
            ->first(function (GeneratedContent $content) use ($message) {
                return ($content->metadata['origin'] ?? null) === 'chat_export'
                    && (int) ($content->metadata['source_message_id'] ?? 0) === (int) $message->id;
            });
    }

    public function buildExistingExportsMap(Conversation $conversation): array
    {
        return GeneratedContent::query()
            ->where('conversation_id', $conversation->id)
            ->orderByDesc('created_at')
            ->get()
            ->filter(fn(GeneratedContent $content) => ($content->metadata['origin'] ?? null) === 'chat_export')
            ->mapWithKeys(function (GeneratedContent $content) {
                $messageId = (int) ($content->metadata['source_message_id'] ?? 0);
                return $messageId > 0 ? [$messageId => $content] : [];
            })
            ->all();
    }

    public function formatSavedContent(GeneratedContent $content): array
    {
        return [
            'content_id' => $content->id,
            'title' => $content->title,
            'redirect_url' => route('mayor.content.index', [
                'tab' => $this->mapContentTypeToTab($content->type),
                'content' => $content->id,
            ]),
        ];
    }

    private function buildSuggestion(string $type, string $label, string $reason, string $confidence): array
    {
        return [
            'type' => $type,
            'label' => $label,
            'reason' => $reason,
            'confidence' => $confidence,
        ];
    }

    private function looksLikeInterviewPrep(string $normalized): bool
    {
        return Str::contains($normalized, ['perguntas mais dificeis', 'resposta recomendada', 'temas sensiveis', 'entrevista', 'sabatina']);
    }

    private function looksLikeCrisisGuidance(string $normalized, array $tags, ?string $intent): bool
    {
        return $intent === 'gestao_de_crise'
            || in_array('crise', $tags, true)
            || Str::contains($normalized, ['nota oficial', 'gestao de crise', 'o que dizer', 'o que não dizer', 'proximos passos nas próximas 24 horas']);
    }

    private function looksLikeSpeech(string $normalized): bool
    {
        return Str::contains($normalized, ['pronunciamento', 'fala do prefeito', 'senhoras e senhores', 'boa noite', 'bom dia a todos', 'discurso']);
    }

    private function looksLikeWhatsApp(string $normalized, array $tags, ?string $intent): bool
    {
        return ($intent === 'comunicação' || in_array('comunicação', $tags, true))
            && mb_strlen($normalized) <= 700
            && Str::contains($normalized, ['whatsapp', 'mensagem curta', 'texto curto']);
    }

    private function looksLikeCommunicationPost(string $normalized, array $tags, ?string $intent): bool
    {
        return $intent === 'comunicação'
            || in_array('comunicação', $tags, true)
            || Str::contains($normalized, ['instagram', 'legenda', 'post', 'hashtags', 'obra entregue', 'comunicado para redes']);
    }

    private function looksLikeStatement(string $normalized, array $tags, ?string $intent): bool
    {
        return $intent === 'planejamento'
            ? false
            : Str::contains($normalized, ['comunicado', 'nota publica', 'nota oficial', 'posicionamento']) || in_array('política', $tags, true);
    }

    private function generateTitle(Conversation $conversation, string $type, string $content): string
    {
        $prefix = match ($type) {
            'post_instagram' => 'Post Instagram',
            'post_facebook' => 'Post Facebook',
            'post_whatsapp' => 'Mensagem WhatsApp',
            'discurso' => 'Discurso',
            'comunicado' => 'Comunicado',
            'entrevista' => 'Preparacao de Entrevista',
            'crise' => 'Orientacao de Crise',
            default => 'Conteudo do Chat',
        };

        $base = $conversation->title && $conversation->title !== 'Nova conversa'
            ? $conversation->title
            : Str::limit(trim(preg_replace('/\s+/', ' ', $content) ?? $content), 60, '...');

        return Str::limit($prefix . ' - ' . $base, 100, '...');
    }

    private function mapChannel(string $type): string
    {
        return match ($type) {
            'post_instagram' => 'instagram',
            'post_facebook' => 'facebook',
            'post_whatsapp' => 'whatsapp',
            'discurso' => 'evento',
            default => 'interno',
        };
    }

    private function mapContentTypeToTab(string $type): string
    {
        return match ($type) {
            'entrevista' => 'interview',
            'crise' => 'crisis',
            'imagem_instagram' => 'image',
            default => 'post',
        };
    }

    private function mapTone(Conversation $conversation, string $type): string
    {
        if ($type === 'crise') {
            return 'tecnico';
        }

        return match ($conversation->context['intent'] ?? null) {
            'comunicação' => 'informativo',
            'gestao_de_crise' => 'tecnico',
            default => 'informativo',
        };
    }

    private function buildTags(Conversation $conversation, string $type): array
    {
        $tags = array_values(array_unique(array_filter([
            ...($conversation->auto_tags ?? []),
            'chat_export',
            $type,
            'gerado_ia',
        ])));

        return array_slice($tags, 0, 8);
    }

    private function normalize(string $text): string
    {
        $text = Str::lower(Str::ascii($text));
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text) ?? $text;
        return preg_replace('/\s+/', ' ', trim($text)) ?? trim($text);
    }
}
