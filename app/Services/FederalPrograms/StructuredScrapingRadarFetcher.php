<?php

namespace App\Services\FederalPrograms;

use App\Models\ResourceSource;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class StructuredScrapingRadarFetcher
{
    private const FETCH_TIMEOUT = 25;
    private const MIN_DOCUMENT_LENGTH = 200;
    private const MAX_CANDIDATES_PER_SOURCE = 8;
    private const PRIORITY_SOURCE_KEYS = ['fnde', 'fns', 'bndes'];

    /**
     * @var array<string, array<string, mixed>>
     */
    private const SOURCE_PROFILES = [
        'fnde' => [
            'entrypoints' => [
                'https://www.gov.br/fnde/pt-br',
                'https://www.gov.br/fnde/pt-br/acesso-a-informacao/acoes-e-programas',
                'https://www.gov.br/fnde/pt-br/acesso-a-informacao/acoes-e-programas/programas',
            ],
            'keywords' => [
                'fnde', 'educacao', 'creche', 'escola', 'escolar', 'transporte escolar',
                'par', 'obras', 'quadra', 'onibus escolar', 'alimentacao escolar',
            ],
            'ignore_terms' => [
                'ouvidoria', 'agenda', 'noticias', 'imprensa', 'licitacao', 'servidor',
                'seminario', 'evento', 'premio', 'webinario', 'podcast', 'galeria',
                'campanha', 'conferencia', 'cerimonia', 'agenda do presidente',
            ],
            'path_keywords' => ['acoes-e-programas', 'programas', 'editais', 'resolucoes'],
            'allowed_hosts' => ['www.gov.br', 'gov.br'],
            'required_terms' => [
                'educacao', 'escola', 'escolar', 'creche', 'transporte escolar',
                'onibus escolar', 'alimentacao escolar', 'quadra', 'programa', 'edital', 'resolucao',
            ],
            'title_terms' => [
                'educacao', 'escola', 'escolar', 'creche', 'transporte', 'onibus',
                'alimentacao', 'quadra', 'programa', 'edital', 'resolucao', 'chamada',
                'par', 'pnate', 'pnae', 'caminho da escola', 'brasil alfabetizado',
            ],
            'excluded_path_keywords' => [
                'noticias', 'agenda', 'acesso-a-informacao', 'comunicados', 'imprensa',
                'auditorias', 'ouvidoria', 'servidor', 'licitacoes', 'legislacao',
            ],
            'require_strong_signal' => true,
            'minimum_score' => 14,
        ],
        'fns' => [
            'entrypoints' => [
                'https://portalfns.saude.gov.br/',
                'https://portalfns.saude.gov.br/financiamento-da-saude/',
                'https://portalfns.saude.gov.br/transferencias-fundo-a-fundo/',
            ],
            'keywords' => [
                'fns', 'saude', 'sus', 'portaria', 'incentivo', 'custeio', 'investimento',
                'habilitacao', 'ubs', 'caps', 'atencao primaria', 'equipamento',
            ],
            'ignore_terms' => [
                'ouvidoria', 'acesso a informacao', 'transparencia', 'legislacao', 'noticias',
            ],
            'path_keywords' => ['financiamento', 'transferencias', 'incentivo', 'portaria', 'bloco'],
            'allowed_hosts' => ['portalfns.saude.gov.br'],
            'required_terms' => [
                'saude', 'sus', 'portaria', 'incentivo', 'custeio', 'investimento',
                'habilitacao', 'fundo a fundo', 'ubs', 'caps', 'atencao primaria',
            ],
            'title_terms' => [
                'saude', 'sus', 'portaria', 'incentivo', 'custeio',
                'investimento', 'habilitacao', 'ubs', 'caps',
            ],
            'minimum_score' => 12,
        ],
        'funasa' => [
            'keywords' => [
                'funasa', 'saneamento', 'agua', 'esgotamento', 'residuos', 'saude ambiental',
                'engenharia de saude publica', 'obra', 'convenio', 'sistemas de abastecimento',
            ],
            'ignore_terms' => [
                'ouvidoria', 'noticias', 'agenda', 'imprensa', 'licitacao', 'servidor',
                'concurso', 'evento', 'galeria', 'podcast', 'webinario', 'transparencia',
            ],
            'path_keywords' => ['editais', 'programas', 'saneamento', 'saude-ambiental', 'engenharia-de-saude-publica'],
            'allowed_hosts' => ['www.gov.br', 'gov.br'],
            'excluded_path_keywords' => [
                'noticias', 'agenda', 'acesso-a-informacao', 'imprensa', 'ouvidoria',
                'servidor', 'licitacoes', 'concursos', 'galeria',
            ],
            'required_terms' => [
                'saneamento', 'agua', 'esgotamento', 'residuos', 'saude ambiental',
                'engenharia de saude publica', 'obra', 'convenio', 'abastecimento',
            ],
            'title_terms' => [
                'saneamento', 'agua', 'esgotamento', 'residuos', 'ambiental',
                'engenharia', 'obra', 'edital', 'chamada', 'programa',
            ],
            'require_strong_signal' => true,
            'minimum_score' => 14,
        ],
        'fnas' => [
            'keywords' => [
                'fnas', 'suas', 'assistencia social', 'cofinanciamento', 'cras', 'creas',
                'acolhimento', 'beneficio eventual', 'protecao social', 'servico socioassistencial',
            ],
            'ignore_terms' => [
                'ouvidoria', 'noticias', 'agenda', 'imprensa', 'licitacao', 'servidor',
                'evento', 'galeria', 'podcast', 'webinario', 'transparencia',
            ],
            'path_keywords' => ['fnas', 'suas', 'cofinanciamento', 'portarias', 'protecao-social', 'assistencia-social'],
            'allowed_hosts' => ['www.gov.br', 'gov.br'],
            'excluded_path_keywords' => [
                'noticias', 'agenda', 'acesso-a-informacao', 'imprensa', 'ouvidoria',
                'servidor', 'licitacoes', 'galeria', 'eventos',
            ],
            'required_terms' => [
                'assistencia social', 'suas', 'cofinanciamento', 'cras', 'creas',
                'acolhimento', 'beneficio eventual', 'protecao social', 'servico',
            ],
            'title_terms' => [
                'fnas', 'suas', 'cofinanciamento', 'cras', 'creas',
                'acolhimento', 'portaria', 'beneficio', 'servico',
            ],
            'require_strong_signal' => true,
            'minimum_score' => 15,
        ],
        'bndes' => [
            'entrypoints' => [
                'https://www.bndes.gov.br/wps/portal/site/home/financiamento',
                'https://www.bndes.gov.br/wps/portal/site/home/financiamento/produto/',
                'https://www.bndes.gov.br/wps/portal/site/home/financiamento/guia/',
            ],
            'keywords' => [
                'bndes', 'financiamento', 'credito', 'setor publico', 'município',
                'saneamento', 'mobilidade', 'infraestrutura', 'garantia', 'prazo',
            ],
            'ignore_terms' => [
                'investidor', 'relacao com investidores', 'governanca', 'imprensa', 'carreiras',
            ],
            'path_keywords' => ['financiamento', 'produto', 'guia', 'setor-publico', 'infraestrutura'],
            'allowed_hosts' => ['www.bndes.gov.br', 'bndes.gov.br'],
            'minimum_score' => 10,
        ],
        'caixa' => [
            'entrypoints' => [
                'https://www.caixa.gov.br/poder-publico/Paginas/default.aspx',
                'https://www.caixa.gov.br/empresa/solucoes-financeiras/credito/Paginas/default.aspx',
            ],
            'keywords' => [
                'caixa', 'poder publico', 'financiamento', 'saneamento',
                'mobilidade', 'infraestrutura', 'contrapartida', 'setor publico',
                'prefeitura', 'município', 'ente publico',
            ],
            'ignore_terms' => [
                'ouvidoria', 'noticias', 'imprensa', 'carreiras', 'loterias', 'cartoes',
                'conta corrente', 'poupanca', 'seguros', 'fgts', 'beneficios', 'servidor',
                'pessoa fisica', 'cliente', 'imovel', 'apartamento', 'casa propria',
            ],
            'path_keywords' => ['poder-publico', 'credito', 'saneamento', 'infraestrutura', 'mobilidade'],
            'allowed_hosts' => ['www.caixa.gov.br', 'caixa.gov.br'],
            'excluded_path_keywords' => [
                'loterias', 'voce', 'atendimento', 'beneficios-sociais', 'cartoes',
                'seguranca', 'imprensa', 'ouvidoria', 'trabalhador', 'servidor',
                'habitacao', 'minha-casa-minha-vida', 'contas', 'beneficios',
            ],
            'required_terms' => [
                'poder publico', 'financiamento', 'saneamento',
                'mobilidade', 'infraestrutura', 'contrapartida',
                'município', 'prefeitura', 'ente publico',
            ],
            'required_term_groups' => [
                ['poder publico', 'setor publico', 'ente publico', 'prefeitura', 'município'],
                ['financiamento', 'credito', 'saneamento', 'mobilidade', 'infraestrutura', 'contrapartida'],
            ],
            'title_terms' => [
                'poder publico', 'financiamento', 'saneamento',
                'mobilidade', 'infraestrutura', 'credito',
                'prefeitura', 'município', 'ente publico',
            ],
            'require_strong_signal' => true,
            'minimum_score' => 17,
        ],
        'finep' => [
            'entrypoints' => [
                'https://www.finep.gov.br/chamadas-publicas',
                'https://www.finep.gov.br/apoio-e-financiamento-externa',
                'https://www.finep.gov.br/inovacao-e-pesquisa',
            ],
            'keywords' => [
                'finep', 'inovacao', 'chamada publica', 'edital', 'subvencao', 'tecnologia',
                'cidades inteligentes', 'pesquisa', 'financiamento', 'setor publico',
            ],
            'ignore_terms' => [
                'ouvidoria', 'noticias', 'imprensa', 'agenda', 'licitacao', 'servidor',
                'transparencia', 'concurso', 'galeria', 'podcast', 'webinario',
            ],
            'path_keywords' => ['chamadas-publicas', 'apoio-e-financiamento', 'inovacao', 'subvencao', 'tecnologia', 'chamadas', 'editais'],
            'allowed_hosts' => ['www.finep.gov.br', 'finep.gov.br'],
            'excluded_path_keywords' => [
                'noticias', 'agenda', 'acesso-a-informacao', 'imprensa', 'ouvidoria',
                'licitacoes', 'servidor', 'transparencia', 'galeria',
            ],
            'required_terms' => [
                'inovacao', 'edital', 'chamada', 'subvencao',
                'tecnologia', 'pesquisa', 'financiamento',
            ],
            'title_terms' => [
                'finep', 'inovacao', 'edital', 'chamada', 'subvencao',
                'tecnologia', 'pesquisa', 'cidades inteligentes',
            ],
            'require_strong_signal' => false,
            'minimum_score' => 11,
        ],
    ];

    /**
     * @var array<int, string>
     */
    private const BASE_KEYWORDS = [
        'edital',
        'chamada',
        'selecao',
        'programa',
        'linha',
        'financiamento',
        'credito',
        'apoio',
        'incentivo',
        'portaria',
        'repasse',
        'fundo',
        'proposta',
        'habilitacao',
        'município',
        'prefeitura',
        'ente publico',
        'governo local',
        'infraestrutura',
        'saneamento',
        'saude',
        'educacao',
        'assistencia',
        'inovacao',
    ];

    /**
     * @var array<int, string>
     */
    private const GENERIC_ANCHORS = [
        'saiba mais',
        'leia mais',
        'acesse',
        'clique aqui',
        'ver mais',
        'confira',
        'menu',
        'home',
        'voltar',
        'mais',
    ];

    public function fetch(ResourceSource $source): array
    {
        return $this->fetchWithMetrics($source)['items'];
    }

    public function resolveProfile(ResourceSource $source): array
    {
        return $this->sourceProfile($source);
    }

    public function fetchWithMetrics(ResourceSource $source): array
    {
        $profile = $this->sourceProfile($source);
        $entrypoints = $this->sourceEntrypoints($source, $profile);

        if ($entrypoints === []) {
            return [
                'items' => [],
                'metrics' => [
                    'entrypoints_total' => 0,
                    'entrypoints_visited' => 0,
                    'raw_candidates' => 0,
                    'filtered_candidates' => 0,
                    'selected_candidates' => 0,
                    'qualified_candidates' => 0,
                ],
            ];
        }

        $candidates = [];
        $visitedEntrypoints = [];
        $rawCandidates = 0;
        $filteredCandidates = 0;

        foreach ($entrypoints as $entrypointUrl) {
            if (isset($visitedEntrypoints[$entrypointUrl])) {
                continue;
            }

            $visitedEntrypoints[$entrypointUrl] = true;
            $document = $this->fetchDocument($entrypointUrl);
            $pageTitle = $this->extractTitle($document) ?: $source->name;
            $candidateExtraction = $this->extractCandidates($document, $entrypointUrl, $source, $profile);
            $entrypointCandidates = $candidateExtraction['items'];
            $rawCandidates += (int) ($candidateExtraction['raw_count'] ?? 0);
            $filteredCandidates += count($entrypointCandidates);

            if ($entrypointCandidates === []) {
                $pageText = $this->extractText($document);

                if ($this->looksRelevant($pageTitle . ' ' . $pageText, $source, $profile)) {
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
                $candidate['candidate_score'] = $this->scoreCandidate($candidate, $source, $profile);
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

        return [
            'items' => $items,
            'metrics' => [
                'entrypoints_total' => count($entrypoints),
                'entrypoints_visited' => count($visitedEntrypoints),
                'raw_candidates' => $rawCandidates,
                'filtered_candidates' => $filteredCandidates,
                'qualified_candidates' => $qualifiedCandidates->count(),
                'selected_candidates' => count($items),
            ],
        ];
    }

    private function fetchDocument(string $url): string
    {
        $html = $this->fetchDirectHtml($url);

        if ($html !== null) {
            return $html;
        }

        $markdown = $this->fetchJinaMarkdown($url);

        if ($markdown !== null) {
            return '<!-- JINA_MARKDOWN -->' . $markdown;
        }

        throw new \RuntimeException('Nao foi possivel acessar a fonte para scraping estruturado.');
    }

    private function fetchDirectHtml(string $url): ?string
    {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
            'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        ];

        foreach ($userAgents as $userAgent) {
            try {
                $response = Http::timeout(self::FETCH_TIMEOUT)
                    ->withHeaders([
                        'User-Agent' => $userAgent,
                        'Accept' => 'text/html,application/xhtml+xml,*/*;q=0.8',
                        'Accept-Language' => 'pt-BR,pt;q=0.9',
                    ])
                    ->get($url);

                if ($response->successful() && strlen($response->body()) >= self::MIN_DOCUMENT_LENGTH) {
                    return $response->body();
                }
            } catch (\Throwable) {
                continue;
            }
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

        if (preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)/i', $document, $matches)) {
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

    private function extractCandidates(string $document, string $baseUrl, ResourceSource $source, array $profile = []): array
    {
        $candidates = str_starts_with($document, '<!-- JINA_MARKDOWN -->')
            ? $this->extractMarkdownCandidates(substr($document, 22), $baseUrl)
            : $this->extractHtmlCandidates($document, $baseUrl);

        $rawCount = count($candidates);

        $items = collect($candidates)
            ->map(function (array $candidate) use ($source, $baseUrl) {
                $candidate['title'] = trim((string) ($candidate['title'] ?? ''));
                $candidate['url'] = trim((string) ($candidate['url'] ?? ''));
                $candidate['context'] = trim((string) ($candidate['context'] ?? ''));
                $candidate['entrypoint_url'] = $baseUrl;
                $candidate['deadline'] = $this->extractFirstDate(
                    $candidate['context'] . ' ' . $candidate['title'] . ' ' . $candidate['url']
                );

                return $candidate;
            })
            ->filter(function (array $candidate) use ($source, $profile) {
                if (($candidate['title'] ?? '') === '' || ($candidate['url'] ?? '') === '') {
                    return false;
                }

                if ($this->isGenericAnchorText($candidate['title'])) {
                    return false;
                }

                $combined = implode(' ', [
                    $candidate['title'],
                    $candidate['context'] ?? '',
                    $candidate['url'],
                ]);

                if ($this->matchesIgnoreTerms($combined, $profile)) {
                    return false;
                }

                if (!$this->passesUrlRules((string) $candidate['url'], $profile)) {
                    return false;
                }

                if (!$this->matchesRequiredTerms($combined, $profile)) {
                    return false;
                }

                if (!$this->matchesStrongSignal($candidate, $profile)) {
                    return false;
                }

                return $this->looksRelevant($combined, $source, $profile);
            })
            ->values()
            ->all();

        return [
            'items' => $items,
            'raw_count' => $rawCount,
        ];
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

        if (str_starts_with($href, '//')) {
            $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';

            return $scheme . ':' . $href;
        }

        if (preg_match('/^https?:\/\//i', $href)) {
            return $href;
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
        $normalized = $this->normalizeText($text);

        return in_array($normalized, self::GENERIC_ANCHORS, true);
    }

    private function looksRelevant(string $text, ResourceSource $source, array $profile = []): bool
    {
        $normalized = $this->normalizeText($text);

        foreach ($this->sourceKeywords($source, $profile) as $keyword) {
            if ($keyword !== '' && str_contains($normalized, $keyword)) {
                return true;
            }
        }

        return false;
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
            'program_code' => $source->key . ':scrape:' . substr(sha1(($candidate['url'] ?? '') . '|' . $title), 0, 24),
            'ministry' => $source->name,
            'description' => $description !== '' ? $description : $source->access_guide,
            'max_value' => null,
            'funding_type' => $this->fundingTypeFor($source),
            'deadline' => $candidate['deadline'] ?? null,
            'source_url' => $candidate['url'] ?? $source->source_url,
            'source_platform' => $source->key,
            'capture_method' => 'scraping',
            'resource_scope' => $source->resource_scope ?: 'federal',
            'status' => 'monitoring',
            'area' => FederalProgramSyncService::inferArea($areaText),
            'reference_year' => now()->year,
            'source_metadata' => [
                'pipeline_group' => 'group_b_scraping',
                'discovery_method' => 'structured_scraping',
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

    private function fundingTypeFor(ResourceSource $source): string
    {
        $scope = $this->normalizeText((string) $source->resource_scope);
        $tags = $this->normalizeText(implode(' ', (array) $source->operational_tags));

        if (str_contains($scope, 'financiamento') || str_contains($tags, 'credito') || str_contains($tags, 'financiamento')) {
            return 'financiamento';
        }

        return 'programa';
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

    /**
     * @return array<int, string>
     */
    private function sourceKeywords(ResourceSource $source, array $profile = []): array
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

    private function sourceProfile(ResourceSource $source): array
    {
        $metadata = is_array($source->source_metadata) ? $source->source_metadata : [];
        $defaults = self::SOURCE_PROFILES[$source->key] ?? [];
        $usesCustomProfile = (bool) ($metadata['uses_custom_scraping_profile'] ?? false);

        return [
            'entrypoints' => $this->profileListValue($metadata, $defaults, 'scraping_entrypoints', 'entrypoints', $usesCustomProfile),
            'keywords' => $this->profileListValue($metadata, $defaults, 'scraping_keywords', 'keywords', $usesCustomProfile),
            'ignore_terms' => $this->profileListValue($metadata, $defaults, 'scraping_ignore_terms', 'ignore_terms', $usesCustomProfile),
            'path_keywords' => $this->profileListValue($metadata, $defaults, 'scraping_path_keywords', 'path_keywords', $usesCustomProfile),
            'allowed_hosts' => $this->profileListValue($metadata, $defaults, 'scraping_allowed_hosts', 'allowed_hosts', $usesCustomProfile),
            'excluded_path_keywords' => $this->profileListValue($metadata, $defaults, 'scraping_excluded_path_keywords', 'excluded_path_keywords', $usesCustomProfile),
            'required_terms' => $this->profileListValue($metadata, $defaults, 'scraping_required_terms', 'required_terms', $usesCustomProfile),
            'required_term_groups' => $this->profileNestedListValue($metadata, $defaults, 'scraping_required_term_groups', 'required_term_groups', $usesCustomProfile),
            'title_terms' => $this->profileListValue($metadata, $defaults, 'scraping_title_terms', 'title_terms', $usesCustomProfile),
            'max_candidates' => $this->profileIntValue($metadata, $defaults, 'max_candidates', 'max_candidates', self::MAX_CANDIDATES_PER_SOURCE, $usesCustomProfile),
            'minimum_score' => $this->profileIntValue($metadata, $defaults, 'minimum_score', 'minimum_score', 0, $usesCustomProfile),
            'require_strong_signal' => $this->profileBoolValue($metadata, $defaults, 'require_strong_signal', 'require_strong_signal', false, $usesCustomProfile),
            'include_source_url_as_entrypoint' => $this->profileBoolValue($metadata, $defaults, 'include_source_url_as_entrypoint', 'include_source_url_as_entrypoint', false, $usesCustomProfile),
            'priority_focus' => in_array($source->key, self::PRIORITY_SOURCE_KEYS, true),
        ];
    }

    /**
     * @param array<string, mixed> $metadata
     * @param array<string, mixed> $defaults
     * @return array<int, string>
     */
    private function profileListValue(
        array $metadata,
        array $defaults,
        string $metadataKey,
        string $defaultKey,
        bool $usesCustomProfile,
    ): array {
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

    /**
     * @param array<string, mixed> $metadata
     * @param array<string, mixed> $defaults
     */
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

    /**
     * @param array<string, mixed> $metadata
     * @param array<string, mixed> $defaults
     */
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

    /**
     * @return array<int, string>
     */
    private function sourceEntrypoints(ResourceSource $source, array $profile): array
    {
        $entrypoints = (array) ($profile['entrypoints'] ?? []);

        if ($entrypoints === [] || (($profile['include_source_url_as_entrypoint'] ?? false) === true)) {
            array_unshift($entrypoints, $source->source_url);
        }

        return collect($entrypoints)
            ->map(fn (mixed $url) => trim((string) $url))
            ->filter(fn (string $url) => $url !== '' && preg_match('/^https?:\/\//i', $url))
            ->unique()
            ->values()
            ->all();
    }

    private function scoreCandidate(array $candidate, ResourceSource $source, array $profile): int
    {
        $combined = $this->normalizeText(implode(' ', [
            $candidate['title'] ?? '',
            $candidate['context'] ?? '',
            $candidate['url'] ?? '',
        ]));
        $title = $this->normalizeText((string) ($candidate['title'] ?? ''));
        $path = $this->normalizeText((string) parse_url((string) ($candidate['url'] ?? ''), PHP_URL_PATH));

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

        foreach ((array) ($profile['path_keywords'] ?? []) as $term) {
            $normalizedTerm = $this->normalizeText((string) $term);

            if ($normalizedTerm !== '' && str_contains($path, $normalizedTerm)) {
                $score += 2;
            }
        }

        if (($profile['priority_focus'] ?? false) === true) {
            $score += 2;
        }

        if (!empty($candidate['deadline'])) {
            $score += 1;
        }

        if (str_contains($combined, 'edital') || str_contains($combined, 'chamada') || str_contains($combined, 'portaria')) {
            $score += 2;
        }

        return $score;
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

        $excludedPathKeywords = collect((array) ($profile['excluded_path_keywords'] ?? []))
            ->map(fn (mixed $item) => $this->normalizeText((string) $item))
            ->filter()
            ->values()
            ->all();

        foreach ($excludedPathKeywords as $keyword) {
            if (str_contains($path, $keyword)) {
                return false;
            }
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

    private function normalizeText(string $text): string
    {
        return Str::of($text)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->value();
    }
}
