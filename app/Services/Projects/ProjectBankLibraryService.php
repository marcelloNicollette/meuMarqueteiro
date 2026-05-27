<?php

namespace App\Services\Projects;

use App\Models\FederalProgramAlert;
use App\Models\GovernmentCommitment;
use App\Models\Municipality;
use App\Models\MunicipalityDocument;
use App\Models\ProjectThesis;
use App\Models\ProjectThesisTemplate;
use App\Services\AI\AIProviderService;
use App\Services\Documents\MunicipalityDocumentTextExtractor;
use App\Services\Radar\HybridRadarReadService;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProjectBankLibraryService
{
    public function __construct(
        private readonly AIProviderService $ai,
        private readonly MunicipalityDocumentTextExtractor $extractor,
        private readonly HybridRadarReadService $radarRead,
    ) {}

    public function ensureLibraryForMunicipality(
        Municipality $municipality,
        bool $force = false,
        int $targetCount = 10,
        string $reason = 'manual'
    ): Collection
    {
        $this->ensureBaseTemplates();

        $existing = ProjectThesis::query()
            ->where('municipality_id', $municipality->id)
            ->with('template')
            ->orderByRaw("
                CASE urgency
                    WHEN 'alta' THEN 1
                    WHEN 'media' THEN 2
                    ELSE 3
                END
            ")
            ->orderByDesc('updated_at')
            ->get();

        if (!$force && $existing->count() >= $targetCount) {
            $this->syncLibraryMetadata($municipality, $existing->count(), false, $reason);

            return $existing;
        }

        $templates = $this->selectTemplatesForMunicipality($municipality, $targetCount);
        $context = $this->buildMunicipalityContext($municipality);
        $personalized = $this->personalizeTemplates($municipality, $templates, $context);

        foreach ($templates as $template) {
            $matchedProgram = $this->matchProgramForTemplate($template, $context['programs']);
            $generated = Arr::get($personalized, $template->slug, []);
            $fields = $this->buildPersonalizedFields($municipality, $template, $matchedProgram, $context, $generated);

            ProjectThesis::query()->updateOrCreate(
                [
                    'municipality_id' => $municipality->id,
                    'project_thesis_template_id' => $template->id,
                ],
                [
                    'title' => $template->title,
                    'category' => $template->category,
                    'justification' => $fields['justification'],
                    'potential_impact' => $fields['potential_impact'],
                    'funding_source' => $fields['funding_source'],
                    'estimated_size' => $template->estimated_size,
                    'urgency' => $fields['urgency'],
                    'execution_complexity' => $template->execution_complexity,
                    'reference_municipalities' => $fields['reference_municipalities'],
                    'government_alignment' => $fields['government_alignment'],
                    'resource_deadline' => $fields['resource_deadline'],
                    'metadata' => [
                        'template_slug' => $template->slug,
                        'fixed_fields' => [
                            'title' => $template->title,
                            'category' => $template->category,
                            'estimated_size' => $template->estimated_size,
                            'execution_complexity' => $template->execution_complexity,
                            'keywords' => $template->keywords ?? [],
                            'profile_rules' => $template->profile_rules ?? [],
                        ],
                        'variable_fields' => [
                            'justification' => $fields['justification'],
                            'potential_impact' => $fields['potential_impact'],
                            'funding_source' => $fields['funding_source'],
                            'urgency' => $fields['urgency'],
                            'government_alignment' => $fields['government_alignment'],
                            'reference_municipalities' => $fields['reference_municipalities'],
                            'resource_deadline' => optional($fields['resource_deadline'])->toDateString(),
                        ],
                        'matched_program_id' => $matchedProgram?->id,
                        'matched_program_name' => $matchedProgram?->program_name,
                        'personalized_at' => now()->toIso8601String(),
                        'personalization_mode' => !empty($generated) ? 'ai_or_hybrid' : 'fallback',
                    ],
                ]
            );
        }

        $library = ProjectThesis::query()
            ->where('municipality_id', $municipality->id)
            ->with('template')
            ->orderByRaw("
                CASE urgency
                    WHEN 'alta' THEN 1
                    WHEN 'media' THEN 2
                    ELSE 3
                END
            ")
            ->orderByDesc('updated_at')
            ->get();

        $this->syncLibraryMetadata($municipality, $library->count(), true, $reason);

        return $library;
    }

    public function markRefreshRecommended(Municipality $municipality, string $reason): Municipality
    {
        $settings = (array) ($municipality->settings ?? []);
        $projectBank = (array) ($settings['project_bank'] ?? []);

        $projectBank['needs_refresh'] = true;
        $projectBank['refresh_recommended_at'] = now()->toIso8601String();
        $projectBank['refresh_recommended_reason'] = $reason;
        $projectBank['last_source_updated_at'] = $this->sourceUpdatedAt($municipality)?->toIso8601String();

        $settings['project_bank'] = $projectBank;

        $municipality->forceFill(['settings' => $settings])->save();

        return $municipality->refresh();
    }

    public function needsPeriodicRefresh(Municipality $municipality, int $staleDays = 7): bool
    {
        if (!$municipality->subscription_active || $municipality->onboarding_status !== 'completed') {
            return false;
        }

        $settings = (array) ($municipality->settings ?? []);
        $projectBank = (array) ($settings['project_bank'] ?? []);

        if (($projectBank['needs_refresh'] ?? false) === true) {
            return true;
        }

        $lastCuratedAt = data_get($projectBank, 'last_curated_at');
        if (!$lastCuratedAt) {
            return true;
        }

        try {
            $lastCuratedAt = \Illuminate\Support\Carbon::parse($lastCuratedAt);
        } catch (\Throwable) {
            return true;
        }

        return $lastCuratedAt->lte(now()->subDays(max($staleDays, 1)));
    }

    public function ensureBaseTemplates(): void
    {
        foreach ($this->baseTemplates() as $template) {
            ProjectThesisTemplate::query()->updateOrCreate(
                ['slug' => $template['slug']],
                Arr::except($template, ['slug'])
            );
        }
    }

    private function selectTemplatesForMunicipality(Municipality $municipality, int $targetCount): Collection
    {
        $templates = ProjectThesisTemplate::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $selected = $templates
            ->filter(fn (ProjectThesisTemplate $template) => $this->matchesMunicipalityProfile($template, $municipality))
            ->values();

        if ($selected->count() < $targetCount) {
            $selected = $selected
                ->merge($templates->reject(fn (ProjectThesisTemplate $template) => $selected->contains('id', $template->id)))
                ->unique('id')
                ->values();
        }

        return $selected->take($targetCount)->values();
    }

    private function matchesMunicipalityProfile(ProjectThesisTemplate $template, Municipality $municipality): bool
    {
        $rules = is_array($template->profile_rules) ? $template->profile_rules : [];
        $population = (int) ($municipality->population ?? 0);
        $idhm = (float) ($municipality->idhm ?? 0);
        $region = Str::lower((string) ($municipality->region ?? ''));

        if (($min = (int) ($rules['population_min'] ?? 0)) > 0 && $population < $min) {
            return false;
        }
        if (($max = (int) ($rules['population_max'] ?? 0)) > 0 && $population > $max) {
            return false;
        }
        if (($idhmMin = (float) ($rules['idhm_min'] ?? 0)) > 0 && $idhm < $idhmMin) {
            return false;
        }
        if (($idhmMax = (float) ($rules['idhm_max'] ?? 0)) > 0 && $idhm > $idhmMax) {
            return false;
        }

        $regions = collect(Arr::wrap($rules['regions'] ?? []))
            ->map(fn ($item) => Str::lower((string) $item))
            ->filter()
            ->values();

        if ($regions->isNotEmpty() && !$regions->contains($region)) {
            return false;
        }

        return true;
    }

    private function buildMunicipalityContext(Municipality $municipality): array
    {
        $municipality->loadMissing('governmentCommitments');

        $commitments = $municipality->governmentCommitments
            ->sortByDesc(fn (GovernmentCommitment $commitment) => match ((string) $commitment->priority) {
                'alta' => 3,
                'media' => 2,
                default => 1,
            })
            ->take(8)
            ->values();

        $documents = MunicipalityDocument::query()
            ->where('municipality_id', $municipality->id)
            ->latest('updated_at')
            ->take(3)
            ->get();

        $documentExcerpts = $documents
            ->map(function (MunicipalityDocument $document) {
                try {
                    $text = $this->extractor->extract($document);
                    if (trim($text) === '') {
                        return null;
                    }

                    return [
                        'name' => $document->name,
                        'type' => $document->type,
                        'excerpt' => Str::limit($text, 1200),
                    ];
                } catch (\Throwable $e) {
                    Log::warning('Banco de Projetos: falha ao extrair documento para contexto.', [
                        'document_id' => $document->id,
                        'exception' => $e,
                    ]);

                    return null;
                }
            })
            ->filter()
            ->values();

        $programs = $this->radarRead
            ->topMunicipalityPrograms($municipality, limit: 25, visibleOnly: false)
            ->values();

        return [
            'summary' => $this->municipalitySummary($municipality, $commitments, $documentExcerpts, $programs),
            'commitments' => $commitments,
            'documents' => $documentExcerpts,
            'programs' => $programs,
        ];
    }

    private function municipalitySummary(
        Municipality $municipality,
        Collection $commitments,
        Collection $documentExcerpts,
        Collection $programs,
    ): string {
        $lines = [
            "Municipio: {$municipality->name} / {$municipality->state_code}",
            'Populacao: ' . ($municipality->population ? number_format((int) $municipality->population, 0, ',', '.') . ' habitantes' : 'não informada'),
            'Regiao: ' . ($municipality->region ?: 'não informada'),
            'IDHM: ' . ($municipality->idhm ?: 'não informado'),
            'PIB: ' . ($municipality->gdp ? 'R$ ' . number_format((float) $municipality->gdp, 2, ',', '.') : 'não informado'),
        ];

        $settings = is_array($municipality->settings) ? $municipality->settings : [];
        if (!empty($settings['resumo_programa'])) {
            $lines[] = 'Resumo do programa de governo: ' . $settings['resumo_programa'];
        }
        if (!empty($settings['lista_projetos'])) {
            $lines[] = 'Projetos priorizados no onboarding: ' . $settings['lista_projetos'];
        }
        if (!empty($settings['sensibilidades'])) {
            $lines[] = 'Sensibilidades locais: ' . $settings['sensibilidades'];
        }

        if ($commitments->isNotEmpty()) {
            $lines[] = 'Compromissos de governo mais relevantes:';
            foreach ($commitments as $commitment) {
                $lines[] = '- ' . $commitment->title . ' | area: ' . ($commitment->area ?: 'outros') . ' | prioridade: ' . ($commitment->priority ?: 'media');
            }
        }

        if ($programs->isNotEmpty()) {
            $lines[] = 'Fontes de recurso mais aderentes do Radar:';
            foreach ($programs->take(8) as $program) {
                $deadline = $program->deadline?->format('d/m/Y');
                $lines[] = '- ' . $program->program_name
                    . ' | area: ' . ($program->area ?: 'não informada')
                    . ' | match: ' . round(((float) $program->match_score) * 100) . '%'
                    . ($deadline ? ' | prazo: ' . $deadline : '');
            }
        }

        if ($documentExcerpts->isNotEmpty()) {
            $lines[] = 'Trechos de documentos do município:';
            foreach ($documentExcerpts as $excerpt) {
                $lines[] = '- ' . $excerpt['name'] . ': ' . $excerpt['excerpt'];
            }
        }

        return implode("\n", $lines);
    }

    private function personalizeTemplates(Municipality $municipality, Collection $templates, array $context): array
    {
        if ($templates->isEmpty()) {
            return [];
        }

        try {
            $response = $this->ai->chat([
                [
                    'role' => 'system',
                    'content' => "Voce personaliza teses do Banco de Projetos para um município brasileiro.\n"
                        . "Cada item tem campos fixos (vindos do template) e você deve preencher somente os campos variaveis com base no contexto real do município.\n"
                        . "Seja especifico, sem texto generico, e respeite estes limites:\n"
                        . "- justificativa: ate 4 linhas\n"
                        . "- potential_impact: ate 4 linhas\n"
                        . "- funding_source: citar programa, orgao e prazo quando houver\n"
                        . "- government_alignment: citar diretriz/compromisso aderente\n"
                        . "Responda APENAS em JSON no formato {\"items\":[{\"slug\":\"\",\"justification\":\"\",\"potential_impact\":\"\",\"funding_source\":\"\",\"urgency\":\"alta|media|baixa\",\"reference_municipalities\":\"\",\"government_alignment\":\"\",\"resource_deadline\":\"YYYY-MM-DD ou null\"}]}",
                ],
                [
                    'role' => 'user',
                    'content' => "Contexto do município:\n{$context['summary']}\n\n"
                        . "Templates selecionados:\n"
                        . $templates->map(function (ProjectThesisTemplate $template) {
                            return json_encode([
                                'slug' => $template->slug,
                                'title' => $template->title,
                                'category' => $template->category,
                                'estimated_size' => $template->estimated_size,
                                'default_urgency' => $template->default_urgency,
                                'execution_complexity' => $template->execution_complexity,
                                'base_justification_template' => $template->base_justification_template,
                                'base_impact_template' => $template->base_impact_template,
                                'base_funding_template' => $template->base_funding_template,
                                'reference_municipalities_template' => $template->reference_municipalities_template,
                                'government_alignment_template' => $template->government_alignment_template,
                                'keywords' => $template->keywords ?? [],
                            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        })->implode("\n"),
                ],
            ], [
                'temperature' => 0.3,
                'max_tokens' => 4000,
                'timeout' => 45,
            ]);

            $decoded = $this->decodeJsonPayload($response->content);

            return collect(Arr::wrap($decoded['items'] ?? []))
                ->filter(fn ($item) => is_array($item) && !empty($item['slug']))
                ->keyBy('slug')
                ->all();
        } catch (\Throwable $e) {
            Log::warning('Banco de Projetos: personalizacao por IA falhou, aplicando fallback.', [
                'municipality_id' => $municipality->id,
                'exception' => $e,
            ]);

            return [];
        }
    }

    private function buildPersonalizedFields(
        Municipality $municipality,
        ProjectThesisTemplate $template,
        ?FederalProgramAlert $matchedProgram,
        array $context,
        array $generated,
    ): array {
        $commitment = $this->bestCommitmentForTemplate($template, $context['commitments']);
        $settings = is_array($municipality->settings) ? $municipality->settings : [];
        $population = max(1, (int) ($municipality->population ?? 0));
        $beneficiaries = match ($template->estimated_size) {
            'pequeno' => max(1200, (int) round($population * 0.08)),
            'medio' => max(2500, (int) round($population * 0.18)),
            default => max(4000, (int) round($population * 0.32)),
        };

        $justification = trim((string) ($generated['justification'] ?? ''));
        if ($justification === '') {
            $justification = strtr($template->base_justification_template, [
                ':município' => $municipality->name,
                ':uf' => $municipality->state_code,
                ':populacao' => $municipality->population ? number_format($municipality->population, 0, ',', '.') : 'não informada',
                ':idhm' => $municipality->idhm ?: 'não informado',
                ':regiao' => $municipality->region ?: 'não informada',
                ':compromisso' => $commitment?->title ?: ($settings['resumo_programa'] ?? 'prioridades do governo municipal'),
            ]);
        }

        $impact = trim((string) ($generated['potential_impact'] ?? ''));
        if ($impact === '') {
            $impact = strtr($template->base_impact_template, [
                ':beneficiarios' => number_format($beneficiaries, 0, ',', '.'),
                ':município' => $municipality->name,
                ':populacao' => $municipality->population ? number_format($municipality->population, 0, ',', '.') : 'não informada',
            ]);
        }

        $funding = trim((string) ($generated['funding_source'] ?? ''));
        if ($funding === '') {
            $funding = $matchedProgram
                ? $this->formatFundingProgram($matchedProgram)
                : $template->base_funding_template;
        }

        $alignment = trim((string) ($generated['government_alignment'] ?? ''));
        if ($alignment === '') {
            $alignment = $commitment?->title
                ? 'Conecta a tese ao compromisso "' . $commitment->title . '" do programa de governo.'
                : trim((string) ($template->government_alignment_template ?: ($settings['resumo_programa'] ?? 'Prioridades gerais do programa de governo.')));
        }

        $reference = trim((string) ($generated['reference_municipalities'] ?? ''));
        if ($reference === '') {
            $reference = strtr($template->reference_municipalities_template, [
                ':uf' => $municipality->state_code,
            ]);
        }

        $urgency = Str::lower(trim((string) ($generated['urgency'] ?? $template->default_urgency)));
        if (!in_array($urgency, ['alta', 'media', 'baixa'], true)) {
            $urgency = $template->default_urgency;
        }
        if ($matchedProgram?->deadline && now()->diffInDays($matchedProgram->deadline, false) <= 60) {
            $urgency = 'alta';
        }

        $resourceDeadline = null;
        $rawDeadline = trim((string) ($generated['resource_deadline'] ?? ''));
        if ($matchedProgram?->deadline) {
            $resourceDeadline = $matchedProgram->deadline;
        } elseif ($rawDeadline !== '' && strtolower($rawDeadline) !== 'null') {
            try {
                $resourceDeadline = \Illuminate\Support\Carbon::parse($rawDeadline);
            } catch (\Throwable) {
                $resourceDeadline = null;
            }
        }

        return [
            'justification' => Str::limit($justification, 900, ''),
            'potential_impact' => Str::limit($impact, 900, ''),
            'funding_source' => Str::limit($funding, 900, ''),
            'urgency' => $urgency,
            'reference_municipalities' => Str::limit($reference, 500, ''),
            'government_alignment' => Str::limit($alignment, 500, ''),
            'resource_deadline' => $resourceDeadline,
        ];
    }

    private function bestCommitmentForTemplate(ProjectThesisTemplate $template, Collection $commitments): ?GovernmentCommitment
    {
        $keywords = collect(Arr::wrap($template->keywords ?? []))
            ->map(fn ($item) => Str::lower(Str::ascii((string) $item)))
            ->filter()
            ->values();

        return $commitments
            ->sortByDesc(function (GovernmentCommitment $commitment) use ($template, $keywords) {
                $score = 0;
                if (Str::lower((string) $commitment->area) === Str::lower((string) $template->category)) {
                    $score += 40;
                }

                $text = Str::lower(Str::ascii(trim($commitment->title . ' ' . $commitment->description)));
                foreach ($keywords as $keyword) {
                    if (str_contains($text, $keyword)) {
                        $score += 12;
                    }
                }

                return $score + match ((string) $commitment->priority) {
                    'alta' => 8,
                    'media' => 4,
                    default => 1,
                };
            })
            ->first();
    }

    private function matchProgramForTemplate(ProjectThesisTemplate $template, Collection $programs): ?FederalProgramAlert
    {
        $keywords = collect(Arr::wrap($template->keywords ?? []))
            ->map(fn ($item) => Str::lower(Str::ascii((string) $item)))
            ->filter()
            ->values();

        return $programs
            ->sortByDesc(function (FederalProgramAlert $program) use ($template, $keywords) {
                $score = (int) round(((float) $program->match_score) * 100);
                if (Str::lower((string) $program->area) === Str::lower((string) $template->category)) {
                    $score += 30;
                }

                $text = Str::lower(Str::ascii(implode(' ', array_filter([
                    $program->program_name,
                    $program->description,
                    $program->ministry,
                    $program->match_reason,
                ]))));

                foreach ($keywords as $keyword) {
                    if (str_contains($text, $keyword)) {
                        $score += 8;
                    }
                }

                if ($program->deadline && now()->diffInDays($program->deadline, false) <= 60) {
                    $score += 12;
                }

                return $score;
            })
            ->first();
    }

    private function formatFundingProgram(FederalProgramAlert $program): string
    {
        $parts = [
            trim($program->program_name),
            $program->ministry ? "({$program->ministry})" : null,
            $program->funding_type ? ' - ' . $program->funding_type : null,
        ];

        $text = trim(implode('', array_filter($parts)));

        if ($program->deadline) {
            $text .= ' | prazo ' . $program->deadline->format('d/m/Y');
        } elseif ($program->open_date) {
            $text .= ' | abertura em ' . $program->open_date->format('d/m/Y');
        }

        if ($program->counterpart_percentage !== null) {
            $text .= ' | contrapartida mínima de ' . number_format((float) $program->counterpart_percentage, 0, ',', '.') . '%';
        }

        return $text;
    }

    private function decodeJsonPayload(string $content): array
    {
        $content = trim($content);
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return ['items' => []];
    }

    private function syncLibraryMetadata(Municipality $municipality, int $count, bool $refreshed, string $reason): void
    {
        $settings = (array) ($municipality->settings ?? []);
        $projectBank = (array) ($settings['project_bank'] ?? []);
        $nowIso = now()->toIso8601String();

        $projectBank['library_size'] = $count;
        $projectBank['last_seen_at'] = $nowIso;
        $projectBank['last_source_updated_at'] = $this->sourceUpdatedAt($municipality)?->toIso8601String();

        if (empty($projectBank['bootstrapped_at'])) {
            $projectBank['bootstrapped_at'] = $nowIso;
        }

        if ($refreshed) {
            $projectBank['last_curated_at'] = $nowIso;
            $projectBank['last_refresh_reason'] = $reason;
            $projectBank['needs_refresh'] = false;
            $projectBank['refresh_recommended_at'] = null;
            $projectBank['refresh_recommended_reason'] = null;
        }

        $settings['project_bank'] = $projectBank;

        $municipality->forceFill(['settings' => $settings])->save();
    }

    private function sourceUpdatedAt(Municipality $municipality): ?\Illuminate\Support\Carbon
    {
        $candidates = [
            GovernmentCommitment::query()
                ->where('municipality_id', $municipality->id)
                ->max('updated_at'),
            MunicipalityDocument::query()
                ->where('municipality_id', $municipality->id)
                ->max('updated_at'),
            FederalProgramAlert::query()
                ->where('municipality_id', $municipality->id)
                ->max('updated_at'),
        ];

        return collect($candidates)
            ->filter()
            ->map(function ($value) {
                try {
                    return \Illuminate\Support\Carbon::parse($value);
                } catch (\Throwable) {
                    return null;
                }
            })
            ->filter()
            ->sortDesc()
            ->first();
    }

    private function baseTemplates(): array
    {
        return [
            [
                'slug' => 'pavimentacao-drenagem-bairros-criticos',
                'title' => 'Pavimentação e drenagem de vias em bairros críticos',
                'category' => 'infraestrutura',
                'estimated_size' => 'medio',
                'default_urgency' => 'alta',
                'execution_complexity' => 'baixa',
                'base_justification_template' => 'Em :município, com população de :populacao habitantes, persistem gargalos de mobilidade e drenagem urbana que afetam serviços essenciais. A tese conversa com :compromisso e ganha relevância pelo contexto regional de :regiao.',
                'base_impact_template' => 'Pode beneficiar diretamente cerca de :beneficiarios moradores, reduzindo tempo de deslocamento, custo de manutenção da frota e ocorrências associadas a alagamento em vias urbanas.',
                'base_funding_template' => 'Linha de infraestrutura urbana com foco em pavimentação, drenagem e mobilidade local, priorizando municípios com déficit de urbanização.',
                'reference_municipalities_template' => 'Municípios de porte similar do :uf já executaram frentes de urbanização e drenagem com ganho operacional e melhoria do acesso a serviços.',
                'government_alignment_template' => 'Conecta a tese a entregas de infraestrutura urbana e mobilidade presentes no programa de governo.',
                'keywords' => ['pavimentacao', 'drenagem', 'vias', 'alagamento', 'mobilidade'],
                'profile_rules' => ['population_min' => 8000],
                'sort_order' => 10,
                'is_active' => true,
            ],
            [
                'slug' => 'requalificacao-ubs-atencao-primaria',
                'title' => 'Requalificação de UBS e ampliação da atenção primária',
                'category' => 'saude',
                'estimated_size' => 'medio',
                'default_urgency' => 'alta',
                'execution_complexity' => 'media',
                'base_justification_template' => 'A estrutura da atenção primária em :município precisa responder melhor ao tamanho da população e aos compromissos assumidos em :compromisso. A tese ajuda a reduzir pressão por atendimentos de maior complexidade.',
                'base_impact_template' => 'Pode elevar a resolutividade da rede local, beneficiar aproximadamente :beneficiarios pessoas e reduzir deslocamentos para atendimento básico e preventivo.',
                'base_funding_template' => 'Linha de saúde para qualificação da atenção primária, reforma de unidades e melhoria de infraestrutura assistencial municipal.',
                'reference_municipalities_template' => 'Municípios de referência no :uf utilizaram programas de qualificação da rede básica para ampliar atendimento e reduzir filas.',
                'government_alignment_template' => 'Aderente a compromissos de fortalecimento da atenção básica e melhoria do acesso à saúde.',
                'keywords' => ['ubs', 'saude', 'atencao primaria', 'unidade basica', 'reforma'],
                'profile_rules' => ['population_min' => 5000],
                'sort_order' => 20,
                'is_active' => true,
            ],
            [
                'slug' => 'creche-educacao-infantil',
                'title' => 'Expansão de vagas em creche e educação infantil',
                'category' => 'educacao',
                'estimated_size' => 'medio',
                'default_urgency' => 'media',
                'execution_complexity' => 'media',
                'base_justification_template' => 'A pressão por vagas na educação infantil em :município interfere na renda das famílias e no acesso das crianças à primeira infância. A tese reforça :compromisso como eixo concreto de política pública.',
                'base_impact_template' => 'Pode abrir capacidade para cerca de :beneficiarios beneficiários entre crianças e responsáveis, com efeito sobre permanência escolar e inserção produtiva das famílias.',
                'base_funding_template' => 'Linha de educação voltada à expansão de infraestrutura e qualificação da educação infantil, com possibilidade de obra, ampliação ou equipagem.',
                'reference_municipalities_template' => 'Municípios similares do :uf combinaram investimento em creches e equipagem escolar para reduzir fila por vagas e ampliar cobertura.',
                'government_alignment_template' => 'Alinha a tese à prioridade de ampliar acesso à educação infantil e apoiar famílias trabalhadoras.',
                'keywords' => ['creche', 'educacao infantil', 'escola', 'primeira infancia'],
                'profile_rules' => ['population_min' => 7000],
                'sort_order' => 30,
                'is_active' => true,
            ],
            [
                'slug' => 'escola-tempo-integral',
                'title' => 'Ampliação de escola em tempo integral',
                'category' => 'educacao',
                'estimated_size' => 'medio',
                'default_urgency' => 'media',
                'execution_complexity' => 'media',
                'base_justification_template' => 'A ampliação da jornada escolar em :município responde a metas de aprendizagem e proteção social, especialmente em territórios com maior vulnerabilidade. A tese conversa com :compromisso.',
                'base_impact_template' => 'Pode beneficiar diretamente cerca de :beneficiarios estudantes, ampliar permanência na escola e melhorar indicadores educacionais e de proteção social.',
                'base_funding_template' => 'Linha educacional com foco em infraestrutura e apoio a modelos de ensino em tempo integral.',
                'reference_municipalities_template' => 'Municípios equivalentes no :uf já estruturaram tempo integral com ganho de desempenho e redução de evasão.',
                'government_alignment_template' => 'Aderente a metas de aprendizagem, proteção de jovens e ampliação da oferta educacional.',
                'keywords' => ['tempo integral', 'escola', 'educacao', 'aluno', 'jornada escolar'],
                'profile_rules' => ['population_min' => 12000],
                'sort_order' => 40,
                'is_active' => true,
            ],
            [
                'slug' => 'residuos-solidos-reciclagem',
                'title' => 'Estruturação de resíduos sólidos e reciclagem',
                'category' => 'meio ambiente',
                'estimated_size' => 'medio',
                'default_urgency' => 'media',
                'execution_complexity' => 'media',
                'base_justification_template' => 'A gestão de resíduos em :município pressiona orçamento, limpeza urbana e conformidade ambiental. A tese ajuda a atacar passivos que dialogam com :compromisso.',
                'base_impact_template' => 'Pode melhorar a qualidade ambiental para cerca de :beneficiarios pessoas, reduzir descarte irregular e organizar cadeia local de reciclagem e triagem.',
                'base_funding_template' => 'Linha ambiental para saneamento, resíduos sólidos, galpões de triagem, equipamentos e melhoria operacional da coleta.',
                'reference_municipalities_template' => 'Municípios de perfil semelhante do :uf já combinaram saneamento e reciclagem com redução de custos e melhoria urbana.',
                'government_alignment_template' => 'Conecta a tese à agenda de limpeza urbana, sustentabilidade e saúde ambiental.',
                'keywords' => ['residuos', 'reciclagem', 'coleta', 'saneamento', 'triagem'],
                'profile_rules' => ['population_min' => 10000],
                'sort_order' => 50,
                'is_active' => true,
            ],
            [
                'slug' => 'mobilidade-calcadas-acessibilidade',
                'title' => 'Mobilidade urbana com foco em calçadas e acessibilidade',
                'category' => 'mobilidade',
                'estimated_size' => 'pequeno',
                'default_urgency' => 'media',
                'execution_complexity' => 'baixa',
                'base_justification_template' => 'Em :município, a mobilidade cotidiana depende de percursos seguros para pedestres e usuários de serviços públicos. A tese transforma :compromisso em pacote de intervenção visível e rápida.',
                'base_impact_template' => 'Pode melhorar o deslocamento diário de cerca de :beneficiarios pessoas, reduzindo barreiras de acessibilidade e riscos em áreas de maior circulação.',
                'base_funding_template' => 'Linha de mobilidade e qualificação urbana para acessibilidade, rotas seguras e requalificação do espaço público.',
                'reference_municipalities_template' => 'Municípios similares do :uf executaram intervenções de pequena escala com alto impacto político e urbano.',
                'government_alignment_template' => 'Aderente a compromissos de mobilidade segura, acessibilidade e cuidado com o espaço público.',
                'keywords' => ['calcadas', 'acessibilidade', 'mobilidade', 'pedestres', 'rotas seguras'],
                'profile_rules' => ['population_min' => 6000],
                'sort_order' => 60,
                'is_active' => true,
            ],
            [
                'slug' => 'fortalecimento-cras-servicos-sociais',
                'title' => 'Fortalecimento de CRAS e serviços socioassistenciais',
                'category' => 'assistencia social',
                'estimated_size' => 'pequeno',
                'default_urgency' => 'media',
                'execution_complexity' => 'baixa',
                'base_justification_template' => 'A rede de proteção social em :município precisa aumentar capacidade de atendimento e presença territorial. A tese ajuda a materializar :compromisso com foco em famílias vulneráveis.',
                'base_impact_template' => 'Pode ampliar a cobertura de atendimento para cerca de :beneficiarios pessoas, melhorar encaminhamentos e qualificar a porta de entrada da assistência social.',
                'base_funding_template' => 'Linha de assistência social para estruturação de equipamentos, qualificação de serviços e apoio à rede socioassistencial.',
                'reference_municipalities_template' => 'Municípios de porte semelhante do :uf fortaleceram CRAS e equipes volantes com ganhos de cobertura e resolutividade.',
                'government_alignment_template' => 'Conecta a tese ao compromisso de proteção social, cuidado com famílias e redução de vulnerabilidades.',
                'keywords' => ['cras', 'assistencia social', 'familias', 'vulnerabilidade', 'proteção social'],
                'profile_rules' => ['idhm_max' => 0.76],
                'sort_order' => 70,
                'is_active' => true,
            ],
            [
                'slug' => 'mercado-produtor-agricultura-local',
                'title' => 'Estrutura de apoio à agricultura familiar e mercado do produtor',
                'category' => 'desenvolvimento econômico',
                'estimated_size' => 'pequeno',
                'default_urgency' => 'media',
                'execution_complexity' => 'media',
                'base_justification_template' => 'A economia local de :município pode capturar mais valor da produção rural e do comércio regional com infraestrutura adequada. A tese se conecta a :compromisso com foco em renda e abastecimento.',
                'base_impact_template' => 'Pode beneficiar diretamente cerca de :beneficiarios pessoas entre produtores, feirantes e consumidores, fortalecendo renda local e circuitos curtos de comercialização.',
                'base_funding_template' => 'Linha de desenvolvimento econômico e agricultura para feiras, mercados do produtor, beneficiamento e logística local.',
                'reference_municipalities_template' => 'Municípios comparáveis do :uf já utilizaram estruturas de comercialização para fortalecer agricultura familiar e economia local.',
                'government_alignment_template' => 'Aderente a compromissos de geração de renda, apoio ao produtor local e dinamização econômica.',
                'keywords' => ['agricultura', 'feira', 'produtor', 'mercado', 'desenvolvimento economico'],
                'profile_rules' => ['population_max' => 120000],
                'sort_order' => 80,
                'is_active' => true,
            ],
            [
                'slug' => 'modernizacao-gestao-digital',
                'title' => 'Modernização da gestão pública e digitalização de serviços',
                'category' => 'institucional',
                'estimated_size' => 'pequeno',
                'default_urgency' => 'media',
                'execution_complexity' => 'media',
                'base_justification_template' => 'A prefeitura de :município precisa ganhar produtividade, rastreabilidade e qualidade no atendimento ao cidadão. A tese converte :compromisso em agenda concreta de modernização institucional.',
                'base_impact_template' => 'Pode reduzir tempo de resposta em serviços municipais, beneficiar cerca de :beneficiarios usuários por ano e elevar a eficiência administrativa da prefeitura.',
                'base_funding_template' => 'Linha institucional para modernização administrativa, transformação digital, equipamentos e melhoria de processos.',
                'reference_municipalities_template' => 'Municípios de porte similar do :uf já avançaram em digitalização de serviços com ganhos de eficiência e transparência.',
                'government_alignment_template' => 'Conecta a tese à agenda de governo digital, eficiência e transparência administrativa.',
                'keywords' => ['digitalizacao', 'gestao', 'servicos', 'tecnologia', 'modernizacao'],
                'profile_rules' => [],
                'sort_order' => 90,
                'is_active' => true,
            ],
            [
                'slug' => 'iluminacao-publica-led',
                'title' => 'Eficiência energética com iluminação pública em LED',
                'category' => 'infraestrutura',
                'estimated_size' => 'medio',
                'default_urgency' => 'media',
                'execution_complexity' => 'baixa',
                'base_justification_template' => 'A melhoria da iluminação pública em :município tem efeito direto sobre segurança, mobilidade noturna e custo operacional. A tese dialoga com :compromisso e pode gerar resultado perceptível rápidamente.',
                'base_impact_template' => 'Pode beneficiar aproximadamente :beneficiarios moradores, melhorar percepção de segurança e reduzir despesa de energia da rede de iluminação.',
                'base_funding_template' => 'Linha de eficiência energética e qualificação urbana para modernização da iluminação pública municipal.',
                'reference_municipalities_template' => 'Municípios equivalentes do :uf já executaram modernização de iluminação com redução de custo e ganho urbano perceptível.',
                'government_alignment_template' => 'Aderente a metas de segurança urbana, eficiência e melhoria do espaço público.',
                'keywords' => ['iluminacao', 'led', 'energia', 'seguranca', 'eficiencia'],
                'profile_rules' => ['population_min' => 7000],
                'sort_order' => 100,
                'is_active' => true,
            ],
            [
                'slug' => 'equipamentos-culturais-circulacao',
                'title' => 'Requalificação de equipamentos culturais e circulação local',
                'category' => 'cultura',
                'estimated_size' => 'pequeno',
                'default_urgency' => 'baixa',
                'execution_complexity' => 'media',
                'base_justification_template' => 'A agenda cultural de :município pode ganhar escala e regularidade com equipamentos mais adequados e programação estruturada. A tese ajuda a ativar :compromisso sob uma chave de identidade local.',
                'base_impact_template' => 'Pode envolver cerca de :beneficiarios pessoas entre público, artistas e produtores, fortalecendo identidade cultural e dinamizando economia criativa local.',
                'base_funding_template' => 'Linha de cultura para requalificação de espaços, programação, formação e fortalecimento de circuitos culturais municipais.',
                'reference_municipalities_template' => 'Municípios similares do :uf reativaram equipamentos culturais e eventos com impacto econômico e simbólico relevante.',
                'government_alignment_template' => 'Conecta a tese à valorização cultural, identidade local e dinamização de territórios.',
                'keywords' => ['cultura', 'equipamento cultural', 'biblioteca', 'centro cultural', 'evento'],
                'profile_rules' => [],
                'sort_order' => 110,
                'is_active' => true,
            ],
            [
                'slug' => 'saneamento-drenagem-rural-urbana',
                'title' => 'Melhoria de saneamento e drenagem em áreas vulneráveis',
                'category' => 'meio ambiente',
                'estimated_size' => 'grande',
                'default_urgency' => 'alta',
                'execution_complexity' => 'alta',
                'base_justification_template' => 'Em :município, déficits de saneamento e drenagem agravam riscos sanitários e custos recorrentes de manutenção urbana. A tese se alinha a :compromisso e pode captar investimento estruturante.',
                'base_impact_template' => 'Pode beneficiar diretamente cerca de :beneficiarios pessoas, reduzir risco sanitário e atacar passivos de infraestrutura com efeito duradouro sobre qualidade de vida.',
                'base_funding_template' => 'Linha estruturante para saneamento, drenagem e mitigação de vulnerabilidades urbanas e ambientais.',
                'reference_municipalities_template' => 'Municípios de referência do :uf combinaram saneamento e drenagem com melhoria sanitária e urbana mensurável.',
                'government_alignment_template' => 'Aderente a compromissos de saúde ambiental, urbanização e melhoria estrutural do território.',
                'keywords' => ['saneamento', 'drenagem', 'alagamento', 'esgoto', 'infraestrutura'],
                'profile_rules' => ['population_min' => 15000],
                'sort_order' => 120,
                'is_active' => true,
            ],
        ];
    }
}
