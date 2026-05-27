<?php

namespace App\Services\Projects;

use App\Enums\ResourceOpportunityStatus;
use App\Models\FederalProgramAlert;
use App\Models\Project;
use App\Models\User;
use App\Services\Radar\HybridRadarReadService;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProjectFundingMatchService
{
    public function analyze(Project $project, ?User $user = null): array
    {
        $project->loadMissing(['municipality', 'intakeQuestions', 'sections']);

        $federalMatches = $this->matchFederalPrograms($project);
        $stateMatches = $this->matchStatePrograms($project);
        $matches = collect(array_merge($federalMatches, $stateMatches))
            ->sortByDesc('score')
            ->values()
            ->take(8)
            ->all();

        $highestScore = (int) collect($matches)->max('score');
        $status = match (true) {
            $highestScore >= 75 => 'strong',
            $highestScore >= 55 => 'moderate',
            !empty($matches) => 'initial',
            default => 'none',
        };

        $metadata = is_array($project->metadata) ? $project->metadata : [];
        $metadata['funding_analysis'] = [
            'checked_at' => now()->toIso8601String(),
            'status' => $status,
            'highest_score' => $highestScore,
            'match_count' => count($matches),
            'matches' => $matches,
        ];
        $metadata['funding_match_status'] = $status;

        $project->forceFill([
            'metadata' => $metadata,
            'last_edited_by_user_id' => $user?->id ?: $project->last_edited_by_user_id,
            'last_edited_at' => now(),
        ])->save();

        if ($user) {
            $project->editHistory()->create([
                'user_id' => $user->id,
                'action' => 'project_funding_checked',
                'field_name' => 'funding_analysis',
                'metadata' => [
                    'status' => $status,
                    'highest_score' => $highestScore,
                    'match_count' => count($matches),
                ],
            ]);
        }

        return $metadata['funding_analysis'];
    }

    private function matchFederalPrograms(Project $project): array
    {
        $desiredAreas = $this->desiredAreas($project);
        $text = $this->projectCompositeText($project);
        $radarPrograms = app(HybridRadarReadService::class)
            ->topMunicipalityPrograms(
                municipality: $project->municipality,
                limit: 100,
                statuses: ResourceOpportunityStatus::actionableForProjects(),
                visibleOnly: false,
            );

        return $radarPrograms
            ->map(function (FederalProgramAlert $program) use ($project, $desiredAreas, $text) {
                $score = 0;
                $reasons = [];

                $programArea = Str::lower((string) $program->area);
                if (in_array($programArea, $desiredAreas, true)) {
                    $score += 40;
                    $reasons[] = 'Area aderente ao projeto';
                }

                if (filled($project->project_type) && $programArea === $project->project_type) {
                    $score += 20;
                    $reasons[] = 'Tipo do projeto combina com o programa';
                }

                $keywordScore = $this->keywordAffinityScore($text, implode(' ', array_filter([
                    $program->program_name,
                    $program->description,
                    $program->match_reason,
                    $program->eligibility_criteria ? implode(' ', Arr::wrap($program->eligibility_criteria)) : '',
                ])));

                if ($keywordScore > 0) {
                    $score += min(25, $keywordScore);
                    $reasons[] = 'Descrição e objetivo com proximidade temática';
                }

                if ($program->status === ResourceOpportunityStatus::Published->value) {
                    $score += 10;
                    $reasons[] = 'Oportunidade publicada';
                } elseif ($program->status === ResourceOpportunityStatus::ClosingSoon->value) {
                    $score += 5;
                    $reasons[] = 'Oportunidade em fase final de submissao';
                }

                if (filled($program->match_score)) {
                    $score += (int) round(min(10, ((float) $program->match_score) * 10));
                }

                $score = min(100, $score);

                if ($score < 35) {
                    return null;
                }

                return [
                    'source_type' => 'federal',
                    'title' => $program->program_name,
                    'subtitle' => $program->ministry ?: 'Programa federal',
                    'area' => $program->area ?: 'não informada',
                    'funding_type' => $program->funding_type ?: 'não informado',
                    'status' => $program->status,
                    'score' => $score,
                    'reasons' => array_values(array_unique($reasons)),
                    'source_url' => $program->source_url,
                    'program_code' => $program->program_code,
                    'source_platform' => $program->source_platform,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function matchStatePrograms(Project $project): array
    {
        $stateCode = Str::upper((string) ($project->municipality?->state_code ?? ''));
        $type = $project->project_type ?: 'institucional';
        $catalog = $this->stateProgramCatalog($stateCode);
        $items = array_merge($catalog['base'] ?? [], $catalog[$type] ?? []);
        $text = $this->projectCompositeText($project);

        return collect($items)
            ->map(function (array $item) use ($text, $type, $stateCode) {
                $score = 35;
                $reasons = ['Sugestao estadual compativel com o perfil do projeto'];
                $description = trim((string) ($item['description'] ?? ''));

                $keywordScore = $this->keywordAffinityScore($text, implode(' ', [
                    $item['title'],
                    $description,
                    implode(' ', $item['keywords'] ?? []),
                ]));

                $score += min(35, $keywordScore);

                if (($item['project_type'] ?? null) === $type) {
                    $score += 15;
                    $reasons[] = 'Linha estadual aderente ao tipo do projeto';
                }

                if (($item['state_code'] ?? $stateCode) === $stateCode) {
                    $score += 10;
                    $reasons[] = 'Fonte alinhada ao estado do município';
                }

                $score = min(95, $score);

                return [
                    'source_type' => 'state',
                    'title' => $item['title'],
                    'subtitle' => $item['agency'],
                    'area' => $item['area'],
                    'funding_type' => $item['funding_type'],
                    'status' => 'reference',
                    'score' => $score,
                    'reasons' => array_values(array_unique($reasons)),
                    'source_url' => $item['source_url'] ?? null,
                    'program_code' => $item['program_code'] ?? null,
                    'source_platform' => 'referencia_estadual',
                ];
            })
            ->filter(fn (array $item) => $item['score'] >= 45)
            ->values()
            ->all();
    }

    private function desiredAreas(Project $project): array
    {
        $map = [
            'infraestrutura' => ['infraestrutura', 'saneamento', 'habitacao', 'mobilidade'],
            'social' => ['social', 'saude', 'educacao', 'assistencia'],
            'ambiental' => ['ambiental', 'saneamento', 'meio_ambiente', 'infraestrutura'],
            'economico' => ['desenvolvimento_economico', 'turismo', 'agricultura', 'infraestrutura'],
            'institucional' => ['gestao', 'modernizacao', 'institucional', 'social'],
        ];

        return $map[$project->project_type ?: 'institucional'] ?? ['institucional'];
    }

    private function projectCompositeText(Project $project): string
    {
        return trim(implode(' ', array_filter([
            $project->title,
            $project->initial_idea,
            $project->responsible_secretariat,
            $project->intakeQuestions instanceof Collection ? $project->intakeQuestions->pluck('answer')->filter()->implode(' ') : '',
            $project->sections instanceof Collection ? $project->sections->pluck('content')->filter()->take(5)->implode(' ') : '',
        ])));
    }

    private function keywordAffinityScore(string $projectText, string $referenceText): int
    {
        $projectTokens = $this->tokenize($projectText);
        $referenceTokens = $this->tokenize($referenceText);

        if (empty($projectTokens) || empty($referenceTokens)) {
            return 0;
        }

        $common = array_intersect($projectTokens, $referenceTokens);

        return min(30, count($common) * 4);
    }

    private function tokenize(string $text): array
    {
        $normalized = Str::lower(Str::ascii($text));
        $normalized = preg_replace('/[^a-z0-9\s]/', ' ', $normalized) ?? '';
        $parts = preg_split('/\s+/', $normalized) ?: [];
        $stopwords = [
            'de', 'da', 'do', 'das', 'dos', 'para', 'com', 'sem', 'uma', 'um', 'que', 'este',
            'essa', 'esse', 'município', 'municipal', 'prefeitura', 'projeto', 'programa', 'mais',
            'como', 'sera', 'serao', 'entre', 'sobre', 'pela', 'pelas', 'pelo', 'pelos',
        ];

        return array_values(array_unique(array_filter($parts, function (string $part) use ($stopwords) {
            return strlen($part) >= 4 && !in_array($part, $stopwords, true);
        })));
    }

    private function stateProgramCatalog(string $stateCode): array
    {
        $base = [
            [
                'title' => 'Programa estadual de apoio a convenios municipais',
                'agency' => "Governo do Estado {$stateCode}",
                'area' => 'multissetorial',
                'funding_type' => 'convenio',
                'project_type' => 'institucional',
                'keywords' => ['convenio', 'municípios', 'investimento', 'apoio'],
            ],
        ];

        return [
            'base' => $base,
            'infraestrutura' => [
                [
                    'title' => 'Programa estadual de infraestrutura urbana e mobilidade local',
                    'agency' => "Secretaria de Infraestrutura {$stateCode}",
                    'area' => 'infraestrutura',
                    'funding_type' => 'convenio',
                    'project_type' => 'infraestrutura',
                    'keywords' => ['pavimentacao', 'drenagem', 'vias', 'praca', 'urbanizacao', 'mobilidade'],
                ],
            ],
            'social' => [
                [
                    'title' => 'Programa estadual de fortalecimento da rede social e equipamentos publicos',
                    'agency' => "Secretaria de Desenvolvimento Social {$stateCode}",
                    'area' => 'social',
                    'funding_type' => 'transferencia',
                    'project_type' => 'social',
                    'keywords' => ['cras', 'creas', 'assistencia', 'juventude', 'familias', 'vulnerabilidade'],
                ],
                [
                    'title' => 'Programa estadual de apoio a educacao integral e estrutura escolar',
                    'agency' => "Secretaria de Educacao {$stateCode}",
                    'area' => 'educacao',
                    'funding_type' => 'transferencia',
                    'project_type' => 'social',
                    'keywords' => ['escola', 'creche', 'educacao', 'tempo integral', 'quadra'],
                ],
            ],
            'ambiental' => [
                [
                    'title' => 'Programa estadual de saneamento, residuos e recuperacao ambiental',
                    'agency' => "Secretaria de Meio Ambiente {$stateCode}",
                    'area' => 'ambiental',
                    'funding_type' => 'convenio',
                    'project_type' => 'ambiental',
                    'keywords' => ['saneamento', 'esgoto', 'residuos', 'reciclagem', 'arborizacao', 'nascente'],
                ],
            ],
            'economico' => [
                [
                    'title' => 'Programa estadual de desenvolvimento economico local e turismo',
                    'agency' => "Secretaria de Desenvolvimento Economico {$stateCode}",
                    'area' => 'desenvolvimento_economico',
                    'funding_type' => 'convenio',
                    'project_type' => 'economico',
                    'keywords' => ['turismo', 'feira', 'comercio', 'empreendedorismo', 'cadeia produtiva'],
                ],
                [
                    'title' => 'Linha estadual de apoio a agricultura familiar e mercados locais',
                    'agency' => "Secretaria de Agricultura {$stateCode}",
                    'area' => 'agricultura',
                    'funding_type' => 'transferencia',
                    'project_type' => 'economico',
                    'keywords' => ['agricultura', 'produtores', 'cooperativa', 'mercado', 'rural'],
                ],
            ],
            'institucional' => [
                [
                    'title' => 'Programa estadual de modernizacao da gestao municipal',
                    'agency' => "Secretaria de Administracao {$stateCode}",
                    'area' => 'institucional',
                    'funding_type' => 'convenio',
                    'project_type' => 'institucional',
                    'keywords' => ['digitalizacao', 'sistema', 'gestao', 'planejamento', 'governanca', 'indicadores'],
                ],
            ],
        ];
    }
}
