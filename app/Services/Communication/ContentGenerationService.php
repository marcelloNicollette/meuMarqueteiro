<?php

namespace App\Services\Communication;

use App\Models\GeneratedContent;
use App\Models\Municipality;
use App\Models\User;
use App\Services\AI\AIProviderService;

/**
 * Módulo 2 — Comunicação e Marketing Político.
 * Gera conteúdo para redes sociais, discursos, comunicados e resposta a crises.
 */
class ContentGenerationService
{
    public function __construct(private AIProviderService $ai) {}

    /**
     * Gera variações de post para redes sociais a partir de uma ação de governo.
     */
    public function generateSocialPost(
        string       $theme,
        string       $channel,      // instagram | facebook | whatsapp | todos
        Municipality $municipality,
        User         $mayor,
        array        $tones = ['celebratorio', 'tecnico', 'empatico'],
    ): GeneratedContent {
        $voiceProfile = $municipality->voice_profile ?? [];
        $mayorName    = $mayor->name;
        $munName      = $municipality->name;

        $channelInstructions = $this->getChannelInstructions($channel);
        $voiceInstructions   = $this->buildVoiceInstructions($voiceProfile);

        $tonesStr = implode(', ', $tones);

        $prompt = <<<PROMPT
        Você é o marqueteiro político do prefeito {$mayorName}, de {$munName}.

        Crie {$this->count($tones)} variações de post para o canal {$channel} sobre o seguinte tema:
        "{$theme}"

        Regras gerais:
        - NUNCA use palavras em inglês
        - Linguagem acessível, sem tecnicismos
        - Foco no impacto para o cidadão, não no processo burocrático
        - Máximo de 2 hashtags por post (apenas se for Instagram/Facebook)
        - NÃO mencione dados que não foram informados

        {$channelInstructions}
        {$voiceInstructions}

        Gere as seguintes variações de tom: {$tonesStr}

        Responda APENAS em JSON com este formato:
        {
          "title": "título interno para identificação",
          "variations": [
            { "tone": "celebratorio", "content": "..." },
            { "tone": "tecnico", "content": "..." },
            { "tone": "empatico", "content": "..." }
          ]
        }
        PROMPT;

        $response = $this->ai->chat([
            ['role' => 'user', 'content' => $prompt],
        ], ['temperature' => 0.8]);

        $clean = preg_replace("/^\x60{3}[a-z]*\n?|\n?\x60{3}$/m", "", trim($response->content));
        $data = json_decode(trim($clean), true) ?? [];

        return GeneratedContent::create([
            'municipality_id' => $municipality->id,
            'user_id'         => $mayor->id,
            'type'            => "post_{$channel}",
            'channel'         => $channel,
            'title'           => $data['title'] ?? $theme,
            'content'         => $data['variations'][0]['content'] ?? '',
            'variations'      => $data['variations'] ?? [],
            'tone'            => $tones[0],
            'status'          => 'draft',
            'tags'            => [$channel, 'gerado_ia'],
            'metadata'        => [
                'theme'    => $theme,
                'provider' => $response->provider,
            ],
        ]);
    }

    /**
     * Prepara o prefeito para uma entrevista ou sabatina.
     */
    public function prepareInterview(
        string       $context,
        Municipality $municipality,
        User         $mayor,
    ): string {
        $prompt = <<<PROMPT
        Você é um preparador de crise e entrevistas para o prefeito {$mayor->name}, de {$municipality->name}.

        Contexto da entrevista/evento:
        {$context}

        Gere:
        1. As 5 perguntas mais difíceis que podem ser feitas
        2. Para cada pergunta: a resposta recomendada, alinhada com o histórico do mandato
        3. Alertas de temas sensíveis a evitar ou tratar com cuidado

        Use linguagem direta, sem rodeios. O prefeito precisa de orientações práticas.
        PROMPT;

        return $this->ai->chat([
            ['role' => 'user', 'content' => $prompt],
        ], ['temperature' => 0.6])->content;
    }

    /**
     * Gera orientação de resposta a crise de comunicação.
     */
    public function crisisResponse(
        string       $crisisDescription,
        Municipality $municipality,
        User         $mayor,
    ): string {
        $prompt = <<<PROMPT
        SITUAÇÃO DE CRISE — URGENTE

        Prefeito: {$mayor->name} | Município: {$municipality->name}

        Descrição da crise:
        {$crisisDescription}

        Forneça:
        1. Análise rápida da gravidade (baixa/média/alta) e justificativa
        2. Posicionamento recomendado (o que dizer e o que NÃO dizer)
        3. Timing: quando responder e por qual canal
        4. Minuta de nota oficial (se necessário)
        5. Próximos passos nas próximas 24 horas

        Seja direto. Esta é uma situação real e urgente.
        PROMPT;

        return $this->ai->chat([
            ['role' => 'user', 'content' => $prompt],
        ], ['temperature' => 0.5])->content;
    }

    // ─── Helpers ─────────────────────────────────────────────────────────

    private function getChannelInstructions(string $channel): string
    {
        return match ($channel) {
            'instagram' => "Para Instagram: máximo 2.200 caracteres, use emojis com moderação, 1-2 hashtags relevantes, linguagem visual.",
            'facebook'  => "Para Facebook: pode ser mais longo, inclua contexto para o leitor que não acompanha tudo, 1-2 hashtags.",
            'whatsapp'  => "Para WhatsApp: texto curto (máximo 300 palavras), sem hashtags, tom de mensagem direta entre pessoas.",
            'discurso'  => "Para discurso: linguagem oral, frases curtas, pausas estratégicas, emotivo mas com dados.",
            default     => "Adapte para o melhor formato possível.",
        };
    }

    private function buildVoiceInstructions(array $profile): string
    {
        if (empty($profile)) return '';

        return "Perfil de voz do prefeito: tom {$profile['tone']}, estilo {$profile['style']}.";
    }

    private function count(array $arr): string
    {
        return (string) count($arr);
    }
}
