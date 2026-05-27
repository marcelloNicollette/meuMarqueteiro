<?php

namespace App\Services\FederalPrograms;

use App\Models\Municipality;
use App\Models\ResourceSource;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DiaryMonitorRadarFetcher
{
    private const FETCH_TIMEOUT = 20;
    private const MIN_DOCUMENT_LENGTH = 300;
    private const MAX_CANDIDATES_PER_SOURCE = 8;

    private const BASE_KEYWORDS = [
        'edital',
        'chamada',
        'portaria',
        'consulta publica',
        'credenciamento',
        'programa',
        'convenio',
        'repasse',
        'selecao',
        'prefeitura',
        'município',
    ];

    private const GENERIC_ANCHORS = [
        'clique aqui',
        'saiba mais',
        'leia mais',
        'acesse',
        'abrir',
        'ver mais',
        'detalhes',
        'home',
        'menu',
    ];

    private const SOURCE_PROFILES = [
        'diario_oficial_uniao' => [
            'entrypoints' => [
                'https://www.in.gov.br/consulta/-/buscar/dou?q=edital',
                'https://www.in.gov.br/consulta/-/buscar/dou?q=chamada',
                'https://www.in.gov.br/consulta/-/buscar/dou?q=convenio',
            ],
            'keywords' => [
                'dou',
                'diario oficial',
                'edital',
                'chamada publica',
                'consulta publica',
                'credenciamento',
                'repasse',
                'convenio',
                'fomento',
                'transferencia',
                'prefeitura',
                'município',
            ],
            'ignore_terms' => [
                'nomeacao',
                'exoneracao',
                'aposentadoria',
                'ferias',
                'pessoal',
                'servidor',
                'homologacao de concurso',
                'designacao',
                'substituicao',
                'extrato',
                'resultado de julgamento',
                'homologacao',
                'adjudicacao',
                'pregao',
                'concorrencia',
                'dispensa de licitacao',
                'inexigibilidade',
                'retificacao',
            ],
            'allowed_hosts' => ['www.in.gov.br', 'in.gov.br'],
            'path_keywords' => ['buscar', 'dou'],
            'required_terms' => ['edital', 'chamada', 'credenciamento', 'selecao', 'consulta publica', 'convenio', 'repasse', 'fomento', 'transferencia'],
            'required_term_groups' => [
                ['edital', 'chamada', 'credenciamento', 'selecao', 'consulta publica', 'convenio'],
                ['repasse', 'transferencia', 'fomento', 'proposta'],
            ],
            'title_terms' => ['edital', 'chamada', 'credenciamento', 'convenio', 'repasse', 'fomento', 'selecao', 'consulta publica', 'transferencia'],
            'minimum_score' => 12,
            'require_strong_signal' => true,
            'max_candidates' => self::MAX_CANDIDATES_PER_SOURCE,
        ],
        'programas_estaduais' => [
            'entrypoints' => [],
            'keywords' => [
                'doe',
                'diario oficial',
                'programa estadual',
                'secretaria',
                'edital',
                'chamada',
                'portaria',
                'convenio',
                'repasse',
                'prefeitura',
                'município',
            ],
            'ignore_terms' => [
                'nomeacao',
                'exoneracao',
                'aposentadoria',
                'ferias',
                'pessoal',
                'servidor',
                'diaria',
                'passagem',
                'designacao',
            ],
            'allowed_hosts' => [],
            'path_keywords' => [],
            'required_terms' => ['edital', 'chamada', 'portaria', 'programa', 'secretaria', 'convenio', 'repasse'],
            'title_terms' => ['edital', 'chamada', 'programa', 'secretaria', 'governo do estado', 'convenio'],
            'minimum_score' => 9,
            'require_strong_signal' => true,
            'max_candidates' => self::MAX_CANDIDATES_PER_SOURCE,
            'state_profiles' => [
                'BA' => [
                    'entrypoints' => [
                        'https://do.ba.gov.br/',
                        'https://www.ba.gov.br/car/editais',
                    ],
                    'keywords' => [
                        'bahia',
                        'estado da bahia',
                        'governo da bahia',
                        'car',
                        'agricultura familiar',
                        'desenvolvimento rural',
                        'habitacao rural',
                    ],
                    'allowed_hosts' => ['do.ba.gov.br', 'www.ba.gov.br', 'ba.gov.br'],
                    'required_terms' => [
                        'edital',
                        'chamada',
                        'chamamento publico',
                        'processo seletivo',
                        'credenciamento',
                        'convocacao',
                        'programa',
                        'convenio',
                        'repasse',
                        'fomento',
                    ],
                    'title_terms' => [
                        'edital',
                        'chamada',
                        'chamamento',
                        'processo seletivo',
                        'credenciamento',
                        'programa',
                        'convocacao',
                        'fomento',
                        'bahia',
                    ],
                    'ignore_terms' => [
                        'licitacao',
                        'pregao',
                        'concorrencia',
                        'registro de precos',
                        'tomada de precos',
                        'aviso de licitacao',
                        'modo de disputa',
                    ],
                    'minimum_score' => 11,
                    'require_strong_signal' => true,
                ],
                'SP' => [
                    'entrypoints' => [
                        'https://www.saude.sp.gov.br/ses/perfil/cidadao/licitacoes-cgaobras/chamamento-publico',
                        'https://www.cultura.sp.gov.br/sec_cultura/Arquivo_de_Editais/Editais_Fomento_Cultsp/Fomento_Cultsp_2025/realizacao_de_projetos_culturais_em_municípios_de_ate_50_mil_habitantes/',
                    ],
                    'keywords' => [
                        'sao paulo',
                        'estado de sao paulo',
                        'governo de sao paulo',
                        'secretaria da saude',
                        'secretaria da cultura',
                        'hospital regional',
                        'credenciamento',
                        'chamamento publico',
                        'convenio',
                        'proac',
                        'municípios de ate 50 mil habitantes',
                    ],
                    'allowed_hosts' => ['www.saude.sp.gov.br', 'saude.sp.gov.br', 'www.cultura.sp.gov.br', 'cultura.sp.gov.br', 'storageproac.blob.core.windows.net'],
                    'required_terms' => [
                        'edital',
                        'chamamento publico',
                        'credenciamento',
                        'convocacao',
                        'convenio',
                        'fomento',
                        'programa',
                        'consulta publica',
                        'proac',
                        'município',
                    ],
                    'title_terms' => [
                        'edital',
                        'chamamento',
                        'credenciamento',
                        'convenio',
                        'fomento',
                        'programa',
                        'proac',
                        'cultura',
                        'sao paulo',
                    ],
                    'ignore_terms' => [
                        'licitacao',
                        'pregao',
                        'concorrencia',
                        'registro de precos',
                        'tomada de precos',
                        'aviso de licitacao',
                        'extrato de contrato',
                        'concurso publico',
                        'despacho',
                        'portaria',
                    ],
                    'minimum_score' => 12,
                    'require_strong_signal' => true,
                ],
            ],
        ],
    ];

    public function fetch(ResourceSource $source, Municipality $municipality): array
    {
        return $this->fetchWithMetrics($source, $municipality)['items'];
    }

    public function resolveProfile(ResourceSource $source): array
    {
        return $this->sourceProfile($source);
    }

    public function fetchWithMetrics(ResourceSource $source, Municipality $municipality): array
    {
        $profile = $this->sourceProfile($source, $municipality);
        $entrypoints = $this->sourceEntrypoints($source, $profile);

        if ($entrypoints === []) {
            return [
                'items' => [],
                'metrics' => [
                    'entrypoints_total' => 0,
                    'entrypoints_visited' => 0,
                    'raw_candidates' => 0,
                    'filtered_candidates' => 0,
                    'qualified_candidates' => 0,
                    'selected_candidates' => 0,
                ],
            ];
        }

        $visitedEntrypoints = [];
        $rawCandidates = 0;
        $filteredCandidates = 0;
        $candidates = [];
        $debug = [
            'rejected_samples' => [],
            'passed_filter_samples' => [],
            'qualified_samples' => [],
        ];

        foreach ($entrypoints as $entrypointUrl) {
            if (isset($visitedEntrypoints[$entrypointUrl])) {
                continue;
            }

            $visitedEntrypoints[$entrypointUrl] = true;
            try {
                $document = $this->fetchDocument($entrypointUrl);
            } catch (\Throwable $e) {
                $debug['rejected_samples'] = $this->mergeDebugSamples(
                    $debug['rejected_samples'],
                    [[
                        'title' => 'Entrypoint indisponível',
                        'url' => $entrypointUrl,
                        'reason' => 'entrypoint_unavailable',
                        'context' => Str::limit($e->getMessage(), 180),
                        'candidate_score' => 0,
                    ]]
                );

                continue;
            }

            $pageTitle = $this->extractTitle($document) ?: $source->name;
            $candidateExtraction = $this->extractCandidates($document, $entrypointUrl, $source, $profile);
            $entrypointCandidates = $candidateExtraction['items'];
            $rawCandidates += (int) ($candidateExtraction['raw_count'] ?? 0);
            $filteredCandidates += count($entrypointCandidates);
            $debug['rejected_samples'] = $this->mergeDebugSamples(
                $debug['rejected_samples'],
                (array) ($candidateExtraction['debug']['rejected_samples'] ?? [])
            );
            $debug['passed_filter_samples'] = $this->mergeDebugSamples(
                $debug['passed_filter_samples'],
                (array) ($candidateExtraction['debug']['passed_filter_samples'] ?? [])
            );

            if ($entrypointCandidates === []) {
                $pageText = $this->extractText($document);

                if ($this->shouldUseEntrypointFallback($pageTitle, $pageText, $entrypointUrl, $source, $profile)) {
                    $entrypointCandidates[] = [
                        'title' => $pageTitle,
                        'url' => $entrypointUrl,
                        'context' => Str::limit($pageText, 420),
                        'deadline' => $this->extractFirstDate($pageText),
                        'entrypoint_url' => $entrypointUrl,
                    ];
                }
            }

            foreach ($entrypointCandidates as $candidate) {
                $candidate['entrypoint_url'] = $candidate['entrypoint_url'] ?? $entrypointUrl;
                $candidate['page_title'] = $candidate['page_title'] ?? $pageTitle;
                $candidate['candidate_score'] = $this->scoreCandidate($candidate, $source, $profile, $municipality);
                $candidates[] = $candidate;
            }
        }

        $qualifiedCandidates = collect($candidates)
            ->unique(fn (array $candidate) => ($candidate['url'] ?? '') . '|' . ($candidate['title'] ?? ''))
            ->sortByDesc('candidate_score')
            ->filter(fn (array $candidate) => (int) ($candidate['candidate_score'] ?? 0) >= (int) ($profile['minimum_score'] ?? 0))
            ->values();

        $items = $qualifiedCandidates
            ->take((int) ($profile['max_candidates'] ?? self::MAX_CANDIDATES_PER_SOURCE))
            ->map(fn (array $candidate) => $this->buildRawItem($source, $candidate, (string) ($candidate['page_title'] ?? $source->name)))
            ->values()
            ->all();

        $debug['qualified_samples'] = $qualifiedCandidates
            ->take(5)
            ->map(fn (array $candidate) => $this->debugCandidateSample($candidate, 'qualified'))
            ->values()
            ->all();

        return [
            'items' => $items,
            'metrics' => [
                'entrypoints_total' => count($entrypoints),
                'entrypoints_visited' => count($visitedEntrypoints),
                'raw_candidates' => $rawCandidates,
                'filtered_candidates' => $filteredCandidates,
                'qualified_candidates' => $qualifiedCandidates->count(),
                'selected_candidates' => count($items),
                'debug' => $debug,
            ],
        ];
    }

    private function fetchDocument(string $url): string
    {
        $direct = $this->fetchDirectHtml($url);

        if ($direct !== null) {
            return $direct;
        }

        $markdown = $this->fetchJinaMarkdown($url);

        if ($markdown !== null) {
            return '<!-- JINA_MARKDOWN -->' . $markdown;
        }

        throw new \RuntimeException('Nao foi possivel acessar a fonte para monitoramento de diario oficial.');
    }

    private function fetchDirectHtml(string $url): ?string
    {
        try {
            $response = Http::timeout(self::FETCH_TIMEOUT)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; MeuMarqueteiroRadarBot/1.0)',
                    'Accept' => 'text/html,application/xhtml+xml,*/*;q=0.8',
                    'Accept-Language' => 'pt-BR,pt;q=0.9',
                ])
                ->get($url);

            if ($response->successful() && strlen($response->body()) >= self::MIN_DOCUMENT_LENGTH) {
                return $response->body();
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function fetchJinaMarkdown(string $url): ?string
    {
        try {
            $response = Http::timeout(self::FETCH_TIMEOUT)
                ->withHeaders([
                    'Accept' => 'text/plain',
                    'Accept-Language' => 'pt-BR,pt;q=0.9',
                    'X-Return-Format' => 'markdown',
                ])
                ->get('https://r.jina.ai/' . $url);

            if ($response->successful() && strlen($response->body()) >= self::MIN_DOCUMENT_LENGTH) {
                return $response->body();
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function extractTitle(string $document): ?string
    {
        if (str_starts_with($document, '<!-- JINA_MARKDOWN -->')) {
            $markdown = substr($document, 22);

            if (preg_match('/^Title:\s*(.+)$/m', $markdown, $matches)) {
                return trim($matches[1]);
            }

            if (preg_match('/^#\s+(.+)$/m', $markdown, $matches)) {
                return trim($matches[1]);
            }

            return null;
        }

        if (preg_match('/<title[^>]*>([^<]+)<\/title>/i', $document, $matches)) {
            return trim(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
        }

        return null;
    }

    private function extractText(string $document): string
    {
        if (str_starts_with($document, '<!-- JINA_MARKDOWN -->')) {
            $markdown = substr($document, 22);
            $markdown = preg_replace('/^(Title|URL Source|Published Time|Markdown Content):.*$/m', '', $markdown);
            $markdown = preg_replace('/!\[[^\]]*\]\([^\)]*\)/', '', $markdown);
            $markdown = preg_replace('/\[[^\]]+\]\(([^)]+)\)/', '$1', $markdown);

            return trim(preg_replace('/\s+/u', ' ', $markdown) ?? '');
        }

        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $document);
        $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $html ?? '');
        $text = strip_tags($html ?? '');
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    private function extractCandidates(string $document, string $baseUrl, ResourceSource $source, array $profile): array
    {
        $candidates = str_starts_with($document, '<!-- JINA_MARKDOWN -->')
            ? $this->extractMarkdownCandidates(substr($document, 22), $baseUrl)
            : $this->extractHtmlCandidates($document, $baseUrl);

        $rawCount = count($candidates);
        $rejectedSamples = [];
        $passedFilterSamples = [];
        $items = [];

        foreach ($candidates as $candidate) {
            $candidate['title'] = trim((string) ($candidate['title'] ?? ''));
            $candidate['url'] = trim((string) ($candidate['url'] ?? ''));
            $candidate['context'] = trim((string) ($candidate['context'] ?? ''));
            $candidate['entrypoint_url'] = $baseUrl;
            $candidate['deadline'] = $this->extractFirstDate(
                implode(' ', [
                    $candidate['title'],
                    $candidate['context'] ?? '',
                    $candidate['url'],
                ])
            );

            $evaluation = $this->evaluateCandidate($candidate, $source, $profile);

            if (!($evaluation['accepted'] ?? false)) {
                if (count($rejectedSamples) < 5) {
                    $rejectedSamples[] = $this->debugCandidateSample($candidate, (string) ($evaluation['reason'] ?? 'rejected'));
                }

                continue;
            }

            $items[] = $candidate;

            if (count($passedFilterSamples) < 5) {
                $passedFilterSamples[] = $this->debugCandidateSample($candidate, 'passed_filter');
            }
        }

        return [
            'items' => $items,
            'raw_count' => $rawCount,
            'debug' => [
                'rejected_samples' => $rejectedSamples,
                'passed_filter_samples' => $passedFilterSamples,
            ],
        ];
    }

    private function shouldUseEntrypointFallback(
        string $pageTitle,
        string $pageText,
        string $entrypointUrl,
        ResourceSource $source,
        array $profile,
    ): bool {
        $combined = trim($pageTitle . ' ' . $pageText . ' ' . $entrypointUrl);

        if (!$this->looksRelevant($combined, $source, $profile)) {
            return false;
        }

        if (!$this->matchesRequiredTerms($combined, $profile)) {
            return false;
        }

        $fallbackCandidate = [
            'title' => $pageTitle,
            'url' => $entrypointUrl,
            'context' => Str::limit($pageText, 420),
        ];

        if (!$this->matchesStrongSignal($fallbackCandidate, $profile)) {
            return false;
        }

        $normalizedTitle = $this->normalizeText($pageTitle);
        $genericTitles = [
            'diario oficial da uniao imprensa nacional',
            'diario oficial da uniao',
            'imprensa nacional',
        ];

        if (in_array($normalizedTitle, $genericTitles, true)) {
            return false;
        }

        return true;
    }

    private function evaluateCandidate(array $candidate, ResourceSource $source, array $profile): array
    {
        if (($candidate['title'] ?? '') === '' || ($candidate['url'] ?? '') === '') {
            return ['accepted' => false, 'reason' => 'missing_title_or_url'];
        }

        if ($this->isGenericAnchorText((string) $candidate['title'])) {
            return ['accepted' => false, 'reason' => 'generic_anchor'];
        }

        $combined = implode(' ', [
            $candidate['title'],
            $candidate['context'] ?? '',
            $candidate['url'],
        ]);

        if ($this->matchesIgnoreTerms($combined, $profile)) {
            return ['accepted' => false, 'reason' => 'ignore_terms'];
        }

        if (!$this->passesUrlRules((string) ($candidate['url'] ?? ''), $profile)) {
            return ['accepted' => false, 'reason' => 'url_rules'];
        }

        if (!$this->matchesRequiredTerms($combined, $profile)) {
            return ['accepted' => false, 'reason' => 'required_terms'];
        }

        if (!$this->matchesStrongSignal($candidate, $profile)) {
            return ['accepted' => false, 'reason' => 'strong_signal'];
        }

        if (!$this->looksRelevant($combined, $source, $profile)) {
            return ['accepted' => false, 'reason' => 'relevance'];
        }

        return ['accepted' => true, 'reason' => 'accepted'];
    }

    private function extractHtmlCandidates(string $html, string $baseUrl): array
    {
        preg_match_all('/<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER);
        $candidates = [];

        foreach ($matches as $match) {
            $url = $this->resolveUrl($baseUrl, $match[1] ?? '');

            if ($url === null || $this->isIgnoredLink($url)) {
                continue;
            }

            $title = trim(strip_tags((string) ($match[2] ?? '')));
            $title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
            $title = preg_replace('/\s+/u', ' ', $title ?? '') ?? '';

            if (mb_strlen($title) < 8) {
                continue;
            }

            $candidates[] = [
                'title' => $title,
                'url' => $url,
                'context' => $title,
            ];
        }

        return $candidates;
    }

    private function extractMarkdownCandidates(string $markdown, string $baseUrl): array
    {
        preg_match_all('/\[([^\]]+)\]\(([^)]+)\)/', $markdown, $matches, PREG_SET_ORDER);
        $candidates = [];

        foreach ($matches as $match) {
            $url = $this->resolveUrl($baseUrl, $match[2] ?? '');

            if ($url === null || $this->isIgnoredLink($url)) {
                continue;
            }

            $title = trim((string) ($match[1] ?? ''));

            if (mb_strlen($title) < 8) {
                continue;
            }

            $candidates[] = [
                'title' => $title,
                'url' => $url,
                'context' => $title,
            ];
        }

        return $candidates;
    }

    private function resolveUrl(string $baseUrl, string $href): ?string
    {
        $href = trim($href);

        if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'javascript:')) {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $href)) {
            return $href;
        }

        if (str_starts_with($href, '//')) {
            $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';

            return $scheme . ':' . $href;
        }

        $base = parse_url($baseUrl);

        if (!$base || empty($base['host'])) {
            return null;
        }

        $scheme = $base['scheme'] ?? 'https';
        $host = $base['host'];

        if (str_starts_with($href, '/')) {
            return "{$scheme}://{$host}{$href}";
        }

        $path = $base['path'] ?? '/';
        $directory = rtrim(str_replace('\\', '/', dirname($path)), '/');
        $directory = $directory === '.' ? '' : $directory;

        return "{$scheme}://{$host}{$directory}/{$href}";
    }

    private function isIgnoredLink(string $url): bool
    {
        return (bool) preg_match('/\.(png|jpg|jpeg|gif|webp|svg|css|js|ico|xml|rss)$/i', $url);
    }

    private function isGenericAnchorText(string $text): bool
    {
        return in_array($this->normalizeText($text), self::GENERIC_ANCHORS, true);
    }

    private function buildRawItem(ResourceSource $source, array $candidate, string $pageTitle): array
    {
        $title = trim((string) ($candidate['title'] ?? $pageTitle));
        $context = trim((string) ($candidate['context'] ?? ''));
        $description = Str::limit(trim($context . ' ' . $source->access_guide), 420);
        $areaText = implode(' ', [
            $source->name,
            $title,
            $description,
            implode(' ', (array) $source->operational_tags),
        ]);

        return [
            'source' => $source->key,
            'source_key' => $source->key,
            'program_name' => Str::limit($title, 255, ''),
            'program_code' => $source->key . ':diary:' . substr(sha1(($candidate['url'] ?? '') . '|' . $title), 0, 24),
            'ministry' => $source->name,
            'description' => $description !== '' ? $description : $source->access_guide,
            'max_value' => null,
            'funding_type' => 'publicacao_oficial',
            'deadline' => $candidate['deadline'] ?? null,
            'source_url' => $candidate['url'] ?? $source->source_url,
            'source_platform' => $source->key,
            'capture_method' => 'diary_monitor',
            'resource_scope' => $source->resource_scope ?: 'federal',
            'status' => 'monitoring',
            'area' => FederalProgramSyncService::inferArea($areaText),
            'reference_year' => now()->year,
            'source_metadata' => [
                'pipeline_group' => 'group_c_diary_monitor',
                'discovery_method' => 'diary_monitor',
                'landing_page' => (string) ($candidate['entrypoint_url'] ?? $source->source_url),
                'page_title' => $pageTitle,
                'operational_tags' => array_values((array) $source->operational_tags),
            ],
            '_raw' => [
                'candidate' => $candidate,
                'source_id' => $source->id,
            ],
        ];
    }

    private function sourceEntrypoints(ResourceSource $source, array $profile): array
    {
        $entrypoints = (array) ($profile['entrypoints'] ?? []);

        if ($entrypoints === [] && filled($source->source_url)) {
            $entrypoints[] = $source->source_url;
        }

        return collect($entrypoints)
            ->map(fn (mixed $url) => trim((string) $url))
            ->filter(fn (string $url) => $url !== '' && preg_match('/^https?:\/\//i', $url))
            ->unique()
            ->values()
            ->all();
    }

    private function sourceProfile(ResourceSource $source, ?Municipality $municipality = null): array
    {
        $metadata = is_array($source->source_metadata) ? $source->source_metadata : [];
        $defaults = self::SOURCE_PROFILES[$source->key] ?? [];
        $usesCustomProfile = (bool) ($metadata['uses_custom_diary_profile'] ?? false);

        $profile = [
            'entrypoints' => $this->profileListValue($metadata, $defaults, 'diary_entrypoints', 'entrypoints', $usesCustomProfile),
            'keywords' => $this->profileListValue($metadata, $defaults, 'diary_keywords', 'keywords', $usesCustomProfile),
            'ignore_terms' => $this->profileListValue($metadata, $defaults, 'diary_ignore_terms', 'ignore_terms', $usesCustomProfile),
            'allowed_hosts' => $this->profileListValue($metadata, $defaults, 'diary_allowed_hosts', 'allowed_hosts', $usesCustomProfile),
            'path_keywords' => $this->profileListValue($metadata, $defaults, 'diary_path_keywords', 'path_keywords', $usesCustomProfile),
            'required_terms' => $this->profileListValue($metadata, $defaults, 'diary_required_terms', 'required_terms', $usesCustomProfile),
            'required_term_groups' => $this->profileNestedListValue($metadata, $defaults, 'diary_required_term_groups', 'required_term_groups', $usesCustomProfile),
            'title_terms' => $this->profileListValue($metadata, $defaults, 'diary_title_terms', 'title_terms', $usesCustomProfile),
            'minimum_score' => $this->profileIntValue($metadata, $defaults, 'diary_minimum_score', 'minimum_score', 0, $usesCustomProfile),
            'require_strong_signal' => $this->profileBoolValue($metadata, $defaults, 'diary_require_strong_signal', 'require_strong_signal', false, $usesCustomProfile),
            'max_candidates' => $this->profileIntValue($metadata, $defaults, 'diary_max_candidates', 'max_candidates', self::MAX_CANDIDATES_PER_SOURCE, $usesCustomProfile),
        ];

        return $this->applyStateProfileOverrides($profile, $metadata, $defaults, $municipality);
    }

    private function profileListValue(array $metadata, array $defaults, string $metadataKey, string $defaultKey, bool $usesCustomProfile): array
    {
        $defaultValues = array_values(array_filter((array) ($defaults[$defaultKey] ?? [])));

        if (!$usesCustomProfile) {
            return $defaultValues;
        }

        $metadataValues = array_values(array_filter((array) ($metadata[$metadataKey] ?? [])));

        return $metadataValues !== [] ? $metadataValues : $defaultValues;
    }

    /**
     * @param array<string, mixed> $metadata
     * @param array<string, mixed> $defaults
     * @return array<int, array<int, string>>
     */
    private function profileNestedListValue(
        array $metadata,
        array $defaults,
        string $metadataKey,
        string $defaultKey,
        bool $usesCustomProfile,
    ): array {
        $defaultValues = $this->normalizeNestedList((array) ($defaults[$defaultKey] ?? []));

        if (!$usesCustomProfile) {
            return $defaultValues;
        }

        $metadataValues = $this->normalizeNestedList((array) ($metadata[$metadataKey] ?? []));

        return $metadataValues !== [] ? $metadataValues : $defaultValues;
    }

    /**
     * @param array<int|string, mixed> $values
     * @return array<int, array<int, string>>
     */
    private function normalizeNestedList(array $values): array
    {
        return collect($values)
            ->map(function (mixed $group) {
                return collect((array) $group)
                    ->map(fn (mixed $item) => trim((string) $item))
                    ->filter()
                    ->values()
                    ->all();
            })
            ->filter(fn (array $group) => $group !== [])
            ->values()
            ->all();
    }

    private function applyStateProfileOverrides(
        array $profile,
        array $metadata,
        array $defaults,
        ?Municipality $municipality,
    ): array {
        $stateCode = mb_strtoupper(trim((string) ($municipality?->state_code ?? '')));

        if ($stateCode === '') {
            return $profile;
        }

        $defaultStateProfiles = is_array($defaults['state_profiles'] ?? null) ? $defaults['state_profiles'] : [];
        $metadataStateProfiles = is_array($metadata['diary_state_profiles'] ?? null) ? $metadata['diary_state_profiles'] : [];
        $stateProfile = $metadataStateProfiles[$stateCode] ?? $defaultStateProfiles[$stateCode] ?? null;

        if (!is_array($stateProfile)) {
            return $profile;
        }

        $profile['entrypoints'] = $this->stateProfileListValue($stateProfile, 'entrypoints', (array) ($profile['entrypoints'] ?? []), false);
        $profile['keywords'] = $this->stateProfileListValue($stateProfile, 'keywords', (array) ($profile['keywords'] ?? []), true);
        $profile['ignore_terms'] = $this->stateProfileListValue($stateProfile, 'ignore_terms', (array) ($profile['ignore_terms'] ?? []), true);
        $profile['allowed_hosts'] = $this->stateProfileListValue($stateProfile, 'allowed_hosts', (array) ($profile['allowed_hosts'] ?? []), false);
        $profile['path_keywords'] = $this->stateProfileListValue($stateProfile, 'path_keywords', (array) ($profile['path_keywords'] ?? []), false);
        $profile['required_terms'] = $this->stateProfileListValue($stateProfile, 'required_terms', (array) ($profile['required_terms'] ?? []), true);
        $profile['required_term_groups'] = $this->stateProfileNestedListValue($stateProfile, 'required_term_groups', (array) ($profile['required_term_groups'] ?? []), true);
        $profile['title_terms'] = $this->stateProfileListValue($stateProfile, 'title_terms', (array) ($profile['title_terms'] ?? []), true);

        if (array_key_exists('minimum_score', $stateProfile)) {
            $profile['minimum_score'] = (int) $stateProfile['minimum_score'];
        }

        if (array_key_exists('require_strong_signal', $stateProfile)) {
            $profile['require_strong_signal'] = (bool) $stateProfile['require_strong_signal'];
        }

        if (array_key_exists('max_candidates', $stateProfile)) {
            $profile['max_candidates'] = (int) $stateProfile['max_candidates'];
        }

        return $profile;
    }

    private function stateProfileListValue(array $stateProfile, string $key, array $base, bool $mergeWithBase): array
    {
        $stateValues = array_values(array_filter((array) ($stateProfile[$key] ?? [])));

        if ($stateValues === []) {
            return $base;
        }

        if (!$mergeWithBase) {
            return $stateValues;
        }

        return collect(array_merge($base, $stateValues))
            ->map(fn (mixed $item) => trim((string) $item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function stateProfileNestedListValue(array $stateProfile, string $key, array $base, bool $mergeWithBase): array
    {
        $stateValues = $this->normalizeNestedList((array) ($stateProfile[$key] ?? []));

        if ($stateValues === []) {
            return $base;
        }

        if (!$mergeWithBase) {
            return $stateValues;
        }

        return collect(array_merge($base, $stateValues))
            ->filter(fn (mixed $group) => is_array($group) && $group !== [])
            ->unique(fn (array $group) => implode('|', $group))
            ->values()
            ->all();
    }

    private function profileIntValue(
        array $metadata,
        array $defaults,
        string $metadataKey,
        string $defaultKey,
        int $fallback,
        bool $usesCustomProfile,
    ): int {
        if ($usesCustomProfile && array_key_exists($metadataKey, $metadata)) {
            return (int) $metadata[$metadataKey];
        }

        if (array_key_exists($defaultKey, $defaults)) {
            return (int) $defaults[$defaultKey];
        }

        return $fallback;
    }

    private function profileBoolValue(
        array $metadata,
        array $defaults,
        string $metadataKey,
        string $defaultKey,
        bool $fallback,
        bool $usesCustomProfile,
    ): bool {
        if ($usesCustomProfile && array_key_exists($metadataKey, $metadata)) {
            return (bool) $metadata[$metadataKey];
        }

        if (array_key_exists($defaultKey, $defaults)) {
            return (bool) $defaults[$defaultKey];
        }

        return $fallback;
    }

    private function sourceKeywords(ResourceSource $source, array $profile): array
    {
        $keywords = array_merge(
            self::BASE_KEYWORDS,
            (array) $source->operational_tags,
            (array) $source->index_fields,
            [$source->key, $source->name],
            (array) ($profile['keywords'] ?? []),
        );

        return collect($keywords)
            ->map(fn (mixed $keyword) => $this->normalizeText((string) $keyword))
            ->filter(fn (string $keyword) => $keyword !== '' && mb_strlen($keyword) >= 3)
            ->unique()
            ->values()
            ->all();
    }

    private function looksRelevant(string $text, ResourceSource $source, array $profile): bool
    {
        $normalized = $this->normalizeText($text);

        foreach ($this->sourceKeywords($source, $profile) as $keyword) {
            if ($keyword !== '' && str_contains($normalized, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function matchesIgnoreTerms(string $text, array $profile): bool
    {
        $normalized = $this->normalizeText($text);

        foreach ((array) ($profile['ignore_terms'] ?? []) as $term) {
            $normalizedTerm = $this->normalizeText((string) $term);

            if ($normalizedTerm !== '' && str_contains($normalized, $normalizedTerm)) {
                return true;
            }
        }

        return false;
    }

    private function matchesRequiredTerms(string $text, array $profile): bool
    {
        $requiredTerms = collect((array) ($profile['required_terms'] ?? []))
            ->map(fn (mixed $item) => $this->normalizeText((string) $item))
            ->filter()
            ->values()
            ->all();

        $requiredTermGroups = collect((array) ($profile['required_term_groups'] ?? []))
            ->map(function (mixed $group) {
                return collect((array) $group)
                    ->map(fn (mixed $item) => $this->normalizeText((string) $item))
                    ->filter()
                    ->values()
                    ->all();
            })
            ->filter(fn (array $group) => $group !== [])
            ->values()
            ->all();

        if ($requiredTerms === [] && $requiredTermGroups === []) {
            return true;
        }

        $normalized = $this->normalizeText($text);

        foreach ($requiredTermGroups as $group) {
            $groupMatched = false;

            foreach ($group as $term) {
                if (str_contains($normalized, $term)) {
                    $groupMatched = true;
                    break;
                }
            }

            if (!$groupMatched) {
                return false;
            }
        }

        if ($requiredTerms === []) {
            return true;
        }

        foreach ($requiredTerms as $term) {
            if (str_contains($normalized, $term)) {
                return true;
            }
        }

        return false;
    }

    private function passesUrlRules(string $url, array $profile): bool
    {
        $host = (string) parse_url($url, PHP_URL_HOST);
        $path = $this->normalizeText((string) parse_url($url, PHP_URL_PATH));

        $allowedHosts = collect((array) ($profile['allowed_hosts'] ?? []))
            ->map(fn (mixed $item) => mb_strtolower(trim((string) $item)))
            ->filter()
            ->values()
            ->all();

        if ($allowedHosts !== [] && !in_array(mb_strtolower($host), $allowedHosts, true)) {
            return false;
        }

        $pathKeywords = collect((array) ($profile['path_keywords'] ?? []))
            ->map(fn (mixed $item) => $this->normalizeText((string) $item))
            ->filter()
            ->values()
            ->all();

        if ($pathKeywords === []) {
            return true;
        }

        foreach ($pathKeywords as $keyword) {
            if (str_contains($path, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function matchesStrongSignal(array $candidate, array $profile): bool
    {
        if (($profile['require_strong_signal'] ?? false) !== true) {
            return true;
        }

        $title = $this->normalizeText((string) ($candidate['title'] ?? ''));
        $path = $this->normalizeText((string) parse_url((string) ($candidate['url'] ?? ''), PHP_URL_PATH));

        foreach ((array) ($profile['title_terms'] ?? []) as $term) {
            $normalizedTerm = $this->normalizeText((string) $term);

            if ($normalizedTerm !== '' && str_contains($title, $normalizedTerm)) {
                return true;
            }
        }

        foreach ((array) ($profile['path_keywords'] ?? []) as $term) {
            $normalizedTerm = $this->normalizeText((string) $term);

            if ($normalizedTerm !== '' && str_contains($path, $normalizedTerm)) {
                return true;
            }
        }

        return false;
    }

    private function scoreCandidate(array $candidate, ResourceSource $source, array $profile, Municipality $municipality): int
    {
        $combined = $this->normalizeText(implode(' ', [
            $candidate['title'] ?? '',
            $candidate['context'] ?? '',
            $candidate['url'] ?? '',
        ]));
        $title = $this->normalizeText((string) ($candidate['title'] ?? ''));
        $score = 0;

        foreach ($this->sourceKeywords($source, $profile) as $keyword) {
            if ($keyword !== '' && str_contains($combined, $keyword)) {
                $score += 3;
            }
        }

        foreach ((array) ($profile['title_terms'] ?? []) as $term) {
            $normalizedTerm = $this->normalizeText((string) $term);

            if ($normalizedTerm !== '' && str_contains($title, $normalizedTerm)) {
                $score += 4;
            }
        }

        if (str_contains($combined, $this->normalizeText($municipality->name))) {
            $score += 2;
        }

        if (filled($municipality->state_code) && str_contains($combined, $this->normalizeText((string) $municipality->state_code))) {
            $score += 1;
        }

        if (!empty($candidate['deadline'])) {
            $score += 1;
        }

        if (str_contains($combined, 'edital') || str_contains($combined, 'chamada') || str_contains($combined, 'credenciamento')) {
            $score += 2;
        }

        return $score;
    }

    private function extractFirstDate(string $text): ?string
    {
        if (!preg_match('/\b(\d{1,2}\/\d{1,2}\/\d{4})\b/', $text, $matches)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('d/m/Y', $matches[1])->startOfDay()->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeText(string $text): string
    {
        return Str::of($text)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->value();
    }

    private function mergeDebugSamples(array $current, array $incoming, int $limit = 5): array
    {
        return collect(array_merge($current, $incoming))
            ->filter(fn ($item) => is_array($item))
            ->unique(fn (array $item) => (($item['url'] ?? '') . '|' . ($item['title'] ?? '') . '|' . ($item['reason'] ?? '')))
            ->take($limit)
            ->values()
            ->all();
    }

    private function debugCandidateSample(array $candidate, string $reason): array
    {
        return [
            'title' => Str::limit((string) ($candidate['title'] ?? ''), 120),
            'url' => (string) ($candidate['url'] ?? ''),
            'reason' => $reason,
            'context' => Str::limit((string) ($candidate['context'] ?? ''), 180),
            'candidate_score' => (int) ($candidate['candidate_score'] ?? 0),
        ];
    }
}
