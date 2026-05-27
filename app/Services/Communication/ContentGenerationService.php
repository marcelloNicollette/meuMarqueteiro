<?php

namespace App\Services\Communication;

use App\Models\Demand;
use App\Models\GeneratedContent;
use App\Models\Municipality;
use App\Models\User;
use App\Services\AI\AIProviderService;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

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
        array        $template = [],
        array        $playbook = [],
    ): GeneratedContent {
        $voiceProfile = $municipality->voice_profile ?? [];
        $mayorName    = $mayor->name;
        $munName      = $municipality->name;

        $channelInstructions = $this->getChannelInstructions($channel);
        $voiceInstructions   = $this->buildVoiceInstructions($voiceProfile);
        $templateInstructions = $this->buildTemplateInstructions($template);
        $playbookInstructions = $this->buildPlaybookInstructions($playbook);
        $historicalGuard = $this->buildHistoricalGuardrail($municipality, $theme);

        $tonesStr = implode(', ', $tones);

        $prompt = <<<PROMPT
        Você é o marqueteiro político do prefeito {$mayorName}, de {$munName}.

        Crie {$this->count($tones)} variações de post para o canal {$channel} sobre o seguinte tema:
        "{$theme}"

        Regras gerais:
        - NUNCA use palavras em inglês
        - Linguagem acessível, sem tecnicismos
        - Foco no impacto para o cidadão, não  no processo burocrático
        - Máximo de 2 hashtags por post (apenas se for Instagram/Facebook)
        - NÃO mencione dados que não  foram informados

        {$channelInstructions}
        {$voiceInstructions}
        {$templateInstructions}
        {$playbookInstructions}
        {$historicalGuard['prompt']}

        Gere as seguintes variações de tom: {$tonesStr}

        Responda APENAS em JSON com este formato:
        {
          "title": "título interno para identificação",
          "historical_check": {
            "status": "ok ou attention",
            "summary": "resumo curto da checagem histórica"
          },
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
        $historicalCheck = Arr::only(Arr::wrap($data['historical_check'] ?? []), ['status', 'summary']);

        if (empty($historicalCheck['status'])) {
            $historicalCheck['status'] = !empty($historicalGuard['references']) ? 'attention' : 'ok';
        }

        if (empty($historicalCheck['summary'])) {
            $historicalCheck['summary'] = !empty($historicalGuard['references'])
                ? 'Tema comparado com referências recentes do município antes da geração.'
                : 'Sem referência histórica relevante localizada na checagem prévia.';
        }

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
                'template' => !empty($template) ? Arr::only($template, ['id', 'name', 'kind', 'channel', 'format']) : null,
                'playbook' => !empty($playbook) ? Arr::only($playbook, ['id', 'name', 'situation_label', 'target_tab', 'target_tab_label', 'description', 'instruction', 'suggested_channel', 'suggested_format', 'checklist', 'workflow']) : null,
                'historical_check' => [
                    'status' => $historicalCheck['status'],
                    'summary' => $historicalCheck['summary'],
                    'reference_count' => count($historicalGuard['references']),
                ],
                'historical_references' => $historicalGuard['references'],
            ],
        ]);
    }

    public function generateDemandCompletionContent(
        Demand       $demand,
        string       $channel,
        Municipality $municipality,
        User         $mayor,
        array        $tones = ['celebratorio', 'tecnico', 'empatico'],
    ): GeneratedContent {
        $theme = $this->buildDemandTheme($demand);

        $content = $this->generateSocialPost(
            theme: $theme,
            channel: $channel,
            municipality: $municipality,
            mayor: $mayor,
            tones: $tones,
        );

        $metadata = is_array($content->metadata) ? $content->metadata : [];
        $metadata['origin_module'] = 'resolve_ai';
        $metadata['origin_type'] = 'demand_completion';
        $metadata['demand'] = [
            'id' => $demand->id,
            'status' => $demand->status,
            'priority' => $demand->priority,
            'locality' => $demand->locality,
            'contact_area' => $demand->contactArea?->name ?? $demand->area,
            'resolved_at' => optional($demand->resolved_at ?? $demand->confirmed_at)->toIso8601String(),
        ];

        $content->update([
            'title' => 'Resolve ai - ' . ($content->title ?: Str::limit($demand->title ?: $demand->raw_input, 70)),
            'tags' => collect($content->tags ?? [])
                ->push('resolve_ai')
                ->push('demanda_concluida')
                ->unique()
                ->values()
                ->all(),
            'metadata' => $metadata,
        ]);

        return $content->fresh();
    }

    private function buildTemplateInstructions(array $template): string
    {
        if (empty($template)) {
            return '';
        }

        $parts = ['Template editorial ativo: ' . ($template['name'] ?? 'Template sem nome') . '.'];

        if (!empty($template['channel'])) {
            $parts[] = 'Canal de referencia: ' . $template['channel'] . '.';
        }

        if (!empty($template['format'])) {
            $parts[] = 'Formato editorial: ' . $template['format'] . '.';
        }

        if (!empty($template['description'])) {
            $parts[] = 'Descrição do template: ' . $template['description'] . '.';
        }

        if (!empty($template['instruction'])) {
            $parts[] = 'Instrucoes obrigatórias do template: ' . $template['instruction'];
        }

        return implode("\n", $parts);
    }

    private function buildPlaybookInstructions(array $playbook): string
    {
        if (empty($playbook)) {
            return '';
        }

        $parts = ['Playbook editorial por situacao ativo: ' . ($playbook['name'] ?? 'Playbook sem nome') . '.'];

        if (!empty($playbook['situation_label'])) {
            $parts[] = 'Situacao operacional: ' . $playbook['situation_label'] . '.';
        }

        if (!empty($playbook['description'])) {
            $parts[] = 'Objetivo do playbook: ' . $playbook['description'] . '.';
        }

        if (!empty($playbook['instruction'])) {
            $parts[] = 'Guia fixo do playbook: ' . $playbook['instruction'];
        }

        $checklist = collect(Arr::wrap($playbook['checklist'] ?? []))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();

        if (!empty($checklist)) {
            $parts[] = "Checklist operacional:\n- " . implode("\n- ", $checklist);
        }

        $workflow = collect(Arr::wrap($playbook['workflow'] ?? []))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();

        if (!empty($workflow)) {
            $parts[] = "Fluxo sugerido:\n- " . implode("\n- ", $workflow);
        }

        return implode("\n", $parts);
    }

    /**
     * Prepara o prefeito para uma entrevista ou sabatina.
     */
    public function prepareInterview(
        string       $context,
        Municipality $municipality,
        User         $mayor,
        array        $playbook = [],
    ): string {
        $playbookInstructions = $this->buildPlaybookInstructions($playbook);
        $prompt = <<<PROMPT
        Você é um preparador de crise e entrevistas para o prefeito {$mayor->name}, de {$municipality->name}.

        Contexto da entrevista/evento:
        {$context}

        {$playbookInstructions}

        Gere:
        1. As 5 perguntas mais difíceis que podem ser feitas
        2. Para cada pergunta: a resposta recomendada, alinhada com o histórico do mandato
        3. Alertas de temas sensíveis a evitar ou tratar com cuidado

        Use linguagem direta, sem rodeios. O prefeito precisa de orientações práticas.
        PROMPT;

        return $this->ai->chat([
            ['role' => 'user', 'content' => $prompt],
        ], [
            'temperature' => 0.6,
            'timeout' => 45,
            'retry_attempts' => 2,
            'max_tokens' => 2200,
        ])->content;
    }

    /**
     * Gera orientação de resposta a crise de comunicação.
     */
    public function crisisResponse(
        string       $crisisDescription,
        Municipality $municipality,
        User         $mayor,
        array        $playbook = [],
    ): array {
        $playbookInstructions = $this->buildPlaybookInstructions($playbook);
        $voiceInstructions = $this->buildVoiceInstructions($municipality->voice_profile ?? []);
        $historicalGuard = $this->buildHistoricalGuardrail($municipality, $crisisDescription);
        $prompt = <<<PROMPT
        SITUAÇÃO DE CRISE — URGENTE

        Prefeito: {$mayor->name} | Município: {$municipality->name}

        Descrição da crise:
        {$crisisDescription}

        {$playbookInstructions}
        {$voiceInstructions}
        {$historicalGuard['prompt']}

        Monte um roteiro de crise prático, objetivo e pronto para evoluir ao longo do dia.
        Seja direto. Esta é uma situação real e urgente.

        Responda APENAS em JSON com este formato:
        {
          "summary": "resumo executivo da crise em 1 ou 2 frases",
          "historical_check": {
            "status": "ok ou attention",
            "summary": "resumo curto da checagem histórica"
          },
          "sections": {
            "severity_analysis": "gravidade, leitura do risco e justificativa",
            "positioning": "o que dizer, o que não dizer, linha oficial da resposta",
            "timing": "quando responder, por qual canal e em qual cadencia",
            "official_note": "minuta de nota oficial ou posicionamento publico",
            "next_steps": "passos objetivos nas próximas 24 horas"
          }
        }
        PROMPT;

        $response = $this->ai->chat([
            ['role' => 'user', 'content' => $prompt],
        ], [
            'temperature' => 0.5,
            'timeout' => 60,
            'retry_attempts' => 2,
            'max_tokens' => 2600,
        ]);

        $data = $this->decodeJsonPayload($response->content);
        $sections = $this->normalizeCrisisSections(Arr::wrap($data['sections'] ?? []));
        $historicalCheck = Arr::only(Arr::wrap($data['historical_check'] ?? []), ['status', 'summary']);

        if (empty($historicalCheck['status'])) {
            $historicalCheck['status'] = !empty($historicalGuard['references']) ? 'attention' : 'ok';
        }

        if (empty($historicalCheck['summary'])) {
            $historicalCheck['summary'] = !empty($historicalGuard['references'])
                ? 'Crise comparada com referências recentes do município antes da montagem do roteiro.'
                : 'Sem referência histórica relevante localizada na checagem prévia da crise.';
        }

        return [
            'summary' => trim((string) ($data['summary'] ?? 'Roteiro inicial de resposta à crise gerado para uso imediato da equipe.')),
            'content' => $this->renderCrisisPlanContent($sections, $data['summary'] ?? null),
            'sections' => $sections,
            'provider' => $response->provider,
            'historical_check' => $historicalCheck,
            'historical_references' => $historicalGuard['references'],
        ];
    }

    public function evolveCrisisResponse(
        GeneratedContent $content,
        Municipality $municipality,
        User $mayor,
        string $updateContext,
        array $affectedSections = [],
        array $playbook = [],
    ): array {
        $existingPlan = $this->resolveExistingCrisisPlan($content);
        $availableSections = array_keys($this->crisisSectionDefinitions());
        $affectedSections = collect($affectedSections)
            ->map(fn ($section) => trim((string) $section))
            ->filter(fn ($section) => in_array($section, $availableSections, true))
            ->unique()
            ->values()
            ->all();

        if (empty($affectedSections)) {
            $affectedSections = $availableSections;
        }

        $voiceInstructions = $this->buildVoiceInstructions($municipality->voice_profile ?? []);
        $playbookInstructions = $this->buildPlaybookInstructions($playbook);
        $historicalGuard = $this->buildHistoricalGuardrail($municipality, $updateContext . "\n" . $content->content, $content);

        $sectionContext = collect($existingPlan['sections'])
            ->map(function (string $text, string $key) {
                $label = $this->crisisSectionDefinitions()[$key] ?? Str::headline($key);

                return '- ' . $label . ': ' . $text;
            })
            ->implode("\n");

        $affectedLabels = collect($affectedSections)
            ->map(fn (string $key) => $this->crisisSectionDefinitions()[$key] ?? Str::headline($key))
            ->implode(', ');

        $prompt = <<<PROMPT
        EVOLUÇÃO DE ROTEIRO DE CRISE — ATUALIZE SOMENTE AS SEÇÕES IMPACTADAS

        Prefeito: {$mayor->name} | Município: {$municipality->name}

        Roteiro atual:
        {$sectionContext}

        Novo fato, atualização ou mudança de cenário:
        {$updateContext}

        Seções impactadas para reescrever:
        {$affectedLabels}

        {$voiceInstructions}
        {$playbookInstructions}
        {$historicalGuard['prompt']}

        Regras:
        - reescreva SOMENTE as seções impactadas
        - preserve coerência com as seções não  impactadas
        - não  invente fatos
        - se a mudança alterar o grau de risco, deixe isso claro
        - mantenha o texto pronto para uso real pela equipe

        Responda APENAS em JSON com este formato:
        {
          "summary": "resumo curto do que mudou nesta evolução",
          "historical_check": {
            "status": "ok ou attention",
            "summary": "resumo curto da checagem histórica"
          },
          "updated_sections": {
            "severity_analysis": "novo texto somente se essa seção foi impactada",
            "positioning": "novo texto somente se essa seção foi impactada",
            "timing": "novo texto somente se essa seção foi impactada",
            "official_note": "novo texto somente se essa seção foi impactada",
            "next_steps": "novo texto somente se essa seção foi impactada"
          }
        }
        PROMPT;

        $response = $this->ai->chat([
            ['role' => 'user', 'content' => $prompt],
        ], [
            'temperature' => 0.45,
            'timeout' => 60,
            'retry_attempts' => 2,
            'max_tokens' => 2200,
        ]);

        $data = $this->decodeJsonPayload($response->content);
        $updatedSections = collect(Arr::wrap($data['updated_sections'] ?? []))
            ->mapWithKeys(function ($value, $key) use ($availableSections) {
                if (!in_array($key, $availableSections, true)) {
                    return [];
                }

                return [$key => trim((string) $value)];
            })
            ->filter()
            ->all();

        $mergedSections = $existingPlan['sections'];
        foreach ($affectedSections as $sectionKey) {
            if (!empty($updatedSections[$sectionKey])) {
                $mergedSections[$sectionKey] = $updatedSections[$sectionKey];
            }
        }

        $mergedSections = $this->normalizeCrisisSections($mergedSections);
        $historicalCheck = Arr::only(Arr::wrap($data['historical_check'] ?? []), ['status', 'summary']);

        if (empty($historicalCheck['status'])) {
            $historicalCheck['status'] = !empty($historicalGuard['references']) ? 'attention' : 'ok';
        }

        if (empty($historicalCheck['summary'])) {
            $historicalCheck['summary'] = !empty($historicalGuard['references'])
                ? 'Evolução da crise comparada com referências históricas e com o roteiro anterior.'
                : 'Evolução aplicada sem referência histórica adicional além do roteiro anterior.';
        }

        return [
            'summary' => trim((string) ($data['summary'] ?? 'Roteiro de crise atualizado com base no novo fato informado.')),
            'content' => $this->renderCrisisPlanContent($mergedSections, $data['summary'] ?? null),
            'sections' => $mergedSections,
            'provider' => $response->provider,
            'affected_sections' => $affectedSections,
            'historical_check' => $historicalCheck,
            'historical_references' => $historicalGuard['references'],
        ];
    }

    public function refineContent(
        GeneratedContent $content,
        Municipality $municipality,
        User $mayor,
        string $instruction,
        ?string $selectedText = null,
        ?string $targetTone = null,
        ?string $targetChannel = null,
    ): array {
        $baseText = trim((string) ($selectedText ?: $content->content));
        $channel = $targetChannel ?: ($content->channel ?: 'instagram');
        $tone = $targetTone ?: ($content->tone ?: 'informativo');
        $voiceInstructions = $this->buildVoiceInstructions($municipality->voice_profile ?? []);
        $channelInstructions = $this->getChannelInstructions($channel);
        $historicalGuard = $this->buildHistoricalGuardrail($municipality, $baseText, $content);

        $prompt = <<<PROMPT
        Você é o marqueteiro político do prefeito {$mayor->name}, de {$municipality->name}.

        Reescreva e refine o texto abaixo sem perder o fato principal, mas obedecendo a orientação editorial.

        Conteúdo atual:
        "{$baseText}"

        Orientação editorial:
        "{$instruction}"

        Contexto da peça:
        - Tipo: {$content->type}
        - Canal alvo: {$channel}
        - Tom alvo: {$tone}

        Regras:
        - Nunca invente dado novo
        - Preserve o sentido central da entrega, agenda ou posicionamento
        - Remova burocratês e deixe o texto mais publicável
        - Se houver promessa vaga, transforme em prestação de contas concreta
        - Use português do Brasil

        {$channelInstructions}
        {$voiceInstructions}
        {$historicalGuard['prompt']}

        Responda APENAS em JSON com este formato:
        {
          "title": "titulo interno curto",
          "content": "texto refinado final",
          "tone": "{$tone}",
          "notes": ["ajuste 1", "ajuste 2"]
        }
        PROMPT;

        $response = $this->ai->chat([
            ['role' => 'user', 'content' => $prompt],
        ], ['temperature' => 0.7]);

        $data = $this->decodeJsonPayload($response->content);

        return [
            'title' => (string) ($data['title'] ?? $content->title),
            'content' => trim((string) ($data['content'] ?? $baseText)),
            'tone' => (string) ($data['tone'] ?? $tone),
            'notes' => array_values(array_filter(Arr::wrap($data['notes'] ?? []))),
            'provider' => $response->provider,
        ];
    }

    public function generateAssistedVariations(
        GeneratedContent $content,
        Municipality $municipality,
        User $mayor,
        string $instruction,
        array $tones = ['celebratorio', 'tecnico', 'empatico'],
        ?string $baseText = null,
        ?string $targetChannel = null,
    ): array {
        $seedText = trim((string) ($baseText ?: $content->content));
        $channel = $targetChannel ?: ($content->channel ?: 'instagram');
        $voiceInstructions = $this->buildVoiceInstructions($municipality->voice_profile ?? []);
        $channelInstructions = $this->getChannelInstructions($channel);
        $tones = array_values(array_unique(array_filter($tones))) ?: ['celebratorio', 'tecnico', 'empatico'];
        $tonesStr = implode(', ', $tones);
        $historicalGuard = $this->buildHistoricalGuardrail($municipality, $seedText, $content);

        $prompt = <<<PROMPT
        Você é o marqueteiro político do prefeito {$mayor->name}, de {$municipality->name}.

        Gere novas variações editoriais para o conteúdo abaixo.

        Texto-base:
        "{$seedText}"

        Orientação editorial complementar:
        "{$instruction}"

        Canal alvo: {$channel}
        Tons desejados: {$tonesStr}

        Regras:
        - Nunca invente fatos ou números
        - Mantenha o núcleo da mensagem
        - Faça cada variação soar de fato diferente
        - Escreva para publicação real
        - Use português do Brasil

        {$channelInstructions}
        {$voiceInstructions}
        {$historicalGuard['prompt']}

        Responda APENAS em JSON com este formato:
        {
          "title": "titulo interno atualizado",
          "variations": [
            { "tone": "celebratorio", "content": "..." },
            { "tone": "tecnico", "content": "..." },
            { "tone": "empatico", "content": "..." }
          ]
        }
        PROMPT;

        $response = $this->ai->chat([
            ['role' => 'user', 'content' => $prompt],
        ], ['temperature' => 0.85]);

        $data = $this->decodeJsonPayload($response->content);
        $variations = collect(Arr::wrap($data['variations'] ?? []))
            ->map(function ($variation) {
                return [
                    'tone' => trim((string) data_get($variation, 'tone', 'geral')),
                    'content' => trim((string) data_get($variation, 'content', '')),
                ];
            })
            ->filter(fn (array $variation) => $variation['content'] !== '')
            ->values()
            ->all();

        return [
            'title' => (string) ($data['title'] ?? $content->title),
            'variations' => $variations,
            'provider' => $response->provider,
        ];
    }

    // ─── Helpers ─────────────────────────────────────────────────────────

    private function getChannelInstructions(string $channel): string
    {
        return match ($channel) {
            'instagram' => "Para Instagram: máximo 2.200 caracteres, use emojis com moderação, 1-2 hashtags relevantes, linguagem visual.",
            'facebook'  => "Para Facebook: pode ser mais longo, inclua contexto para o leitor que não  acompanha tudo, 1-2 hashtags.",
            'whatsapp'  => "Para WhatsApp: texto curto (máximo 300 palavras), sem hashtags, tom de mensagem direta entre pessoas.",
            'discurso'  => "Para discurso: linguagem oral, frases curtas, pausas estratégicas, emotivo mas com dados.",
            default     => "Adapte para o melhor formato possível.",
        };
    }

    private function buildVoiceInstructions(array $profile): string
    {
        if (empty($profile)) return '';

        $parts = [];

        if (!empty($profile['tone']) || !empty($profile['style'])) {
            $parts[] = 'Perfil de voz do prefeito: tom ' . ($profile['tone'] ?? 'institucional') . ', estilo ' . ($profile['style'] ?? 'claro') . '.';
        }

        if (!empty($profile['vocabulary'])) {
            $parts[] = 'Vocabulário preferencial: ' . trim((string) $profile['vocabulary']) . '.';
        }

        if (!empty($profile['priority_themes'])) {
            $parts[] = 'Temas prioritários da comunicação: ' . trim((string) $profile['priority_themes']) . '.';
        }

        if (!empty($profile['avoid'])) {
            $parts[] = 'Evite especialmente: ' . trim((string) $profile['avoid']) . '.';
        }

        return implode(' ', $parts);
    }

    private function crisisSectionDefinitions(): array
    {
        return [
            'severity_analysis' => 'Gravidade e diagnóstico',
            'positioning' => 'Posicionamento recomendado',
            'timing' => 'Timing e canais',
            'official_note' => 'Minuta de nota oficial',
            'next_steps' => 'Próximos passos em 24h',
        ];
    }

    private function normalizeCrisisSections(array $sections): array
    {
        return collect($this->crisisSectionDefinitions())
            ->mapWithKeys(function (string $label, string $key) use ($sections) {
                $value = trim((string) ($sections[$key] ?? ''));

                if ($value === '') {
                    $value = 'Seção ainda não  detalhada nesta etapa.';
                }

                return [$key => $value];
            })
            ->all();
    }

    private function renderCrisisPlanContent(array $sections, ?string $summary = null): string
    {
        $parts = [];

        if (!empty($summary)) {
            $parts[] = 'Resumo executivo';
            $parts[] = trim((string) $summary);
        }

        foreach ($this->crisisSectionDefinitions() as $key => $label) {
            $parts[] = $label;
            $parts[] = trim((string) ($sections[$key] ?? 'Seção ainda não  detalhada nesta etapa.'));
        }

        return implode("\n\n", $parts);
    }

    private function resolveExistingCrisisPlan(GeneratedContent $content): array
    {
        $storedSections = data_get($content->metadata, 'crisis.sections');

        if (is_array($storedSections) && !empty($storedSections)) {
            return [
                'sections' => $this->normalizeCrisisSections($storedSections),
            ];
        }

        return [
            'sections' => $this->normalizeCrisisSections([
                'severity_analysis' => trim((string) $content->content),
            ]),
        ];
    }

    private function buildHistoricalGuardrail(Municipality $municipality, string $subject, ?GeneratedContent $ignoreContent = null): array
    {
        $keywords = $this->extractHistoricalKeywords($subject);

        if (empty($keywords)) {
            return [
                'prompt' => "Checagem histórica prévia: não  houve referência suficiente para comparação detalhada. Ainda assim, não  invente fatos, datas, números ou status de entrega.",
                'references' => [],
            ];
        }

        $references = GeneratedContent::query()
            ->where('municipality_id', $municipality->id)
            ->when($ignoreContent?->id, fn ($query) => $query->where('id', '!=', $ignoreContent->id))
            ->latest('updated_at')
            ->limit(40)
            ->get()
            ->map(function (GeneratedContent $content) use ($keywords) {
                $haystack = Str::lower(implode(' ', array_filter([
                    (string) $content->title,
                    (string) data_get($content->metadata, 'theme'),
                    (string) $content->content,
                    (string) data_get($content->metadata, 'archive.reference_note'),
                    (string) data_get($content->metadata, 'archive.outcome_note'),
                ])));

                $matchedKeywords = collect($keywords)
                    ->filter(fn (string $keyword) => str_contains($haystack, $keyword))
                    ->values()
                    ->all();

                if (empty($matchedKeywords)) {
                    return null;
                }

                return [
                    'id' => $content->id,
                    'title' => (string) ($content->title ?: 'Peça anterior'),
                    'status' => (string) ($content->status ?: 'draft'),
                    'channel' => (string) ($content->channel ?: 'interno'),
                    'updated_at' => optional($content->updated_at)->format('d/m/Y H:i'),
                    'matched_keywords' => $matchedKeywords,
                    'summary' => Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $content->content))), 200),
                ];
            })
            ->filter()
            ->sortByDesc(fn (array $reference) => count($reference['matched_keywords']))
            ->take(3)
            ->values()
            ->all();

        if (empty($references)) {
            return [
                'prompt' => "Checagem histórica prévia: nenhuma peça recente com alta aderência ao tema foi localizada. Ainda assim, não  invente fatos, datas, números ou status de entrega.",
                'references' => [],
            ];
        }

        $referenceLines = collect($references)->map(function (array $reference) {
            return '- [' . ($reference['updated_at'] ?: 'sem data') . '] ' . $reference['title'] .
                ' | canal: ' . $reference['channel'] .
                ' | status: ' . $reference['status'] .
                ' | termos em comum: ' . implode(', ', $reference['matched_keywords']) .
                ' | resumo: ' . $reference['summary'];
        })->implode("\n");

        return [
            'prompt' => "Checagem histórica obrigatória antes de escrever:\nCompare o tema atual com as referências abaixo. Se houver risco de contradição de prazo, local, número, estágio da entrega, autoria ou posicionamento, ajuste o texto para manter coerência e registre isso em `historical_check` com status `attention`. Se não  houver conflito relevante, use status `ok`.\nReferências recentes:\n{$referenceLines}",
            'references' => $references,
        ];
    }

    private function extractHistoricalKeywords(string $text): array
    {
        $normalized = Str::lower(Str::ascii($text));
        $tokens = preg_split('/[^a-z0-9]+/', $normalized) ?: [];
        $stopwords = [
            'para', 'com', 'sem', 'uma', 'das', 'dos', 'que', 'por', 'pra', 'nos', 'nas', 'uma', 'sobre', 'entre',
            'mais', 'menos', 'muito', 'muita', 'novo', 'nova', 'prefeito', 'prefeitura', 'município', 'cidade',
            'acao', 'acoes', 'governo', 'gestao', 'entrega', 'programa', 'projeto',
        ];

        return collect($tokens)
            ->map(fn ($token) => trim((string) $token))
            ->filter(fn ($token) => strlen($token) >= 4)
            ->reject(fn ($token) => in_array($token, $stopwords, true))
            ->unique()
            ->take(8)
            ->values()
            ->all();
    }

    private function count(array $arr): string
    {
        return (string) count($arr);
    }

    private function buildDemandTheme(Demand $demand): string
    {
        $parts = [];
        $parts[] = 'Transforme esta demanda concluida em comunicação publica com foco em entrega realizada para a populacao.';
        $parts[] = 'Demanda: ' . trim((string) ($demand->title ?: $demand->raw_input));

        if ($demand->locality) {
            $parts[] = 'Localidade: ' . $demand->locality;
        }

        if ($demand->contactArea?->name || $demand->area) {
            $parts[] = 'Secretaria responsável: ' . ($demand->contactArea?->name ?? $demand->area);
        }

        if ($demand->completion_note) {
            $parts[] = 'Entrega executada: ' . trim($demand->completion_note);
        }

        if ($demand->address) {
            $parts[] = 'Endereco complementar: ' . $demand->address;
        }

        $parts[] = 'Evite promessas vagas e escreva como prestacao de contas concreta.';

        return implode(' ', $parts);
    }

    private function decodeJsonPayload(string $content): array
    {
        $clean = preg_replace("/^\x60{3}[a-z]*\n?|\n?\x60{3}$/m", "", trim($content));

        return json_decode(trim((string) $clean), true) ?? [];
    }
}
