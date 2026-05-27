<?php

namespace App\Services\RAG;

use App\Models\DocumentEmbedding;
use App\Models\MonitoredUrl;
use App\Services\AI\AIProviderService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Serviço que visita URLs, extrai texto limpo e indexa como embeddings.
 * O conteúdo fica disponível automaticamente para o assistente via RAG.
 */
class UrlIndexerService
{
    private int $chunkSize = 600;   // palavras por chunk (menor que docs — HTML tem mais ruído)
    private int $chunkOverlap = 80;

    public function __construct(private AIProviderService $ai) {}

    /**
     * Indexar uma URL específica.
     */
    public function index(MonitoredUrl $url): array
    {
        $url->update(['fetch_status' => 'fetching', 'fetch_error' => null]);

        try {
            // 1. Fetch do HTML bruto (sempre — para extração de links antes do Jina)
            $rawHtml = $this->fetchRawHtml($url->url);

            // 2. Extrair links internos ANTES de processar conteúdo
            //    (precisa do HTML bruto — o Jina devolve texto sem <a href>)
            $subLinks = $url->index_subpages
                ? $this->extractInternalLinks($rawHtml, $url->url)
                : [];

            // 3. Extrair texto da página principal (usa Jina se fetch direto falhar)
            $html      = $this->fetchUrl($url->url);
            $pageTitle = $this->extractTitle($html);
            if ($pageTitle) {
                $url->update(['page_title' => $pageTitle]);
            }

            $text = $this->extractText($html, $url->url);

            if (str_word_count(trim($text)) < 10) {
                throw new \Exception('Conteúdo muito curto ou vazio. O site pode bloquear bots ou usar JavaScript pesado.');
            }

            // 4. Buscar subpáginas com os links já extraídos do HTML bruto
            $allText = $text;
            if ($url->index_subpages && !empty($subLinks)) {
                $subTexts = $this->fetchSubpages($subLinks, $url->url);
                $allText .= "\n\n" . implode("\n\n", $subTexts);
                Log::info("UrlIndexer: {$url->url} — " . count($subLinks) . " links, " . count($subTexts) . " subpáginas indexadas");
            }

            // 5. Dividir em chunks
            $chunks = $this->splitIntoChunks($allText);

            // 6. Remover embeddings antigos desta URL
            DocumentEmbedding::where('municipality_id', $url->municipality_id)
                ->where('layer', 'url_monitor')
                ->where('source', $url->url)
                ->delete();

            // 7. Gerar e salvar embeddings
            $count = 0;
            $source = $url->display_title . ' — ' . parse_url($url->url, PHP_URL_HOST);

            foreach ($chunks as $index => $chunk) {
                if (empty(trim($chunk))) continue;

                $vectorArray = $this->ai->embed($chunk);
                $vectorStr   = '[' . implode(',', $vectorArray) . ']';

                DocumentEmbedding::create([
                    'municipality_id' => $url->municipality_id,
                    'document_id'     => null,
                    'layer'           => 'url_monitor',
                    'category'        => $url->category,
                    'source'          => $url->url,
                    'chunk_index'     => $index,
                    'content'         => $chunk,
                    'embedding'       => $vectorStr,
                    'metadata'        => [
                        'url'        => $url->url,
                        'title'      => $source,
                        'category'   => $url->category,
                        'fetched_at' => now()->toIso8601String(),
                    ],
                    'token_count'     => str_word_count($chunk),
                ]);

                $count++;
            }

            $url->update([
                'fetch_status'    => 'indexed',
                'last_fetched_at' => now(),
                'last_indexed_at' => now(),
                'chunks_count'    => $count,
                'fetch_error'     => null,
            ]);

            return ['ok' => true, 'chunks' => $count, 'title' => $pageTitle];
        } catch (\Exception $e) {
            Log::warning("UrlIndexer: falha em {$url->url}: " . $e->getMessage());

            $url->update([
                'fetch_status' => 'failed',
                'fetch_error'  => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Fetch via Jina Reader (primeira opção — retorna texto limpo sem JS/scripts).
     * Fallback para fetch direto + extração HTML se Jina falhar.
     */
    private function fetchUrl(string $url): string
    {
        // Estratégia 1: Jina Reader — extrai texto limpo, executa JS, ignora analytics
        try {
            $jinaUrl  = 'https://r.jina.ai/' . $url;
            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept'          => 'text/plain',
                    'Accept-Language' => 'pt-BR,pt;q=0.9',
                    'X-Return-Format' => 'markdown',
                ])
                ->get($jinaUrl);

            if ($response->successful() && strlen($response->body()) > 200) {
                return '<!-- JINA_TEXT -->' . $response->body();
            }
        } catch (\Exception $e) {
            Log::debug("UrlIndexer: Jina falhou para {$url}: " . $e->getMessage());
        }

        // Estratégia 2: fetch direto + limpeza HTML
        $lastError = null;
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
            'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        ];

        foreach ($userAgents as $ua) {
            try {
                $response = Http::timeout(20)
                    ->withHeaders([
                        'User-Agent'      => $ua,
                        'Accept'          => 'text/html,application/xhtml+xml,*/*;q=0.8',
                        'Accept-Language' => 'pt-BR,pt;q=0.9',
                    ])
                    ->get($url);

                if ($response->successful() && strlen($response->body()) > 200) {
                    return $response->body();
                }
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
            }
        }

        throw new \Exception("Não foi possível acessar a URL. Último erro: {$lastError}");
    }

    /**
     * Extrair título da página HTML ou do texto Jina.
     */
    private function extractTitle(string $html): ?string
    {
        // Se veio do Jina — formato "Title: Programa Cidades Sustentáveis"
        if (str_starts_with($html, '<!-- JINA_TEXT -->')) {
            $text = substr($html, 18);
            if (preg_match('/^Title:\s*(.+)$/m', $text, $m)) {
                return trim($m[1]);
            }
            // Fallback: primeira linha não  vazia
            foreach (explode("\n", $text) as $line) {
                $line = trim($line);
                if (!empty($line)) return $line;
            }
            return null;
        }

        if (preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $m)) {
            return trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
        }

        if (preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)/i', $html, $m)) {
            return trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
        }

        return null;
    }

    /**
     * Extrair texto limpo do HTML ou do conteúdo Jina.
     */
    private function extractText(string $html, string $baseUrl): string
    {
        // Se veio do Jina Reader — já é texto limpo, só remover cabeçalho de metadados
        if (str_starts_with($html, '<!-- JINA_TEXT -->')) {
            $text = substr($html, 18);

            // Remover cabeçalho de metadados do Jina
            $text = preg_replace('/^(Title|URL Source|Published Time|Markdown Content):.*$/m', '', $text);

            // Remover imagens markdown: ![alt](url)
            $text = preg_replace('/!\[[^\]]*\]\([^\)]*\)/', '', $text);

            // Remover links com texto vazio: [](url)
            $text = preg_replace('/\[\s*\]\([^\)]*\)/', '', $text);

            // Converter links com texto para só o texto: [texto](url) → texto
            $text = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $text);

            // Remover headers markdown
            $text = preg_replace('/^#{1,6}\s*/m', '', $text);

            // Remover bold/italic markdown
            $text = preg_replace('/\*{1,3}([^*\n]+)\*{1,3}/', '$1', $text);

            // Remover bullets
            $text = preg_replace('/^[\*\-\+]\s+/m', '', $text);

            // Remover linhas que só têm separadores markdown
            $text = preg_replace('/^\s*[-\*]{3,}\s*$/m', '', $text);

            // Remover linhas de crédito de foto/imagem (padrão comum)
            $text = preg_replace('/^(Foto:|Imagem:|Crédito:|Photo:|Image:|©|Reprodução|Original text|Rate this|Your feedback).*/mi', '', $text);

            // Remover linhas de menu/navegação (padrão: texto curto com expand_more, ›, «, »)
            $lines = explode("\n", $text);
            $lines = array_filter($lines, function ($line) {
                $line = trim($line);
                if (empty($line)) return true; // manter linhas vazias para espaçamento
                // Remover linhas de navegação típicas
                if (preg_match('/expand_more|expand_less|chevron|›|»|«|keyboard_arrow/i', $line)) return false;
                // Remover linhas muito curtas que são lixo de navegação (menos de 3 palavras)
                if (str_word_count($line) < 3 && strlen($line) < 30) return false;
                return true;
            });
            $text = implode("\n", $lines);

            // Limpar espaços excessivos
            $text = preg_replace('/\n{3,}/', "\n\n", $text);

            return $this->sanitizeText($text);
        }

        // HTML direto: extrair conteúdo principal
        $text = $this->extractMainContent($html);

        if (str_word_count($text) < 30) {
            $text = $this->extractAllVisibleText($html);
        }

        if (str_word_count($text) < 15) {
            preg_match_all('/[a-záéíóúàâãêôçA-ZÁÉÍÓÚÀÂÃÊÔÇ][^\n<>]{20,}/u', $html, $m);
            $text = implode("\n", $m[0] ?? []);
        }

        return $this->sanitizeText($text);
    }

    /**
     * Extrair conteúdo principal priorizando <main>, <article>, role="main".
     */
    private function extractMainContent(string $html): string
    {
        // Remover blocos de ruído primeiro
        // Remover scripts, estilos e conteúdo inline de analytics/tracking
        $html = preg_replace('/<(script|style|noscript|iframe)[^>]*>.*?<\/\1>/si', '', $html);
        // Remover atributos de evento inline (onclick, onload, etc.)
        // atributos on* sao removidos junto com os blocos script acima
        $html = preg_replace('/<!--.*?-->/s', '', $html);

        // Tentar extrair <main> ou <article> — conteúdo principal
        foreach (['<main[^>]*>(.*?)<\/main>', '<article[^>]*>(.*?)<\/article>'] as $pattern) {
            if (preg_match('/' . $pattern . '/si', $html, $m)) {
                $text = $this->htmlToText($m[1]);
                if (str_word_count($text) > 30) {
                    return $text;
                }
            }
        }

        // Tentar role="main"
        if (preg_match('/<[^>]+role=["\']main["\'][^>]*>(.*?)<\/[a-z]+>/si', $html, $m)) {
            $text = $this->htmlToText($m[1]);
            if (str_word_count($text) > 30) return $text;
        }

        // Fallback: corpo inteiro sem nav/header/footer/aside
        $html = preg_replace('/<(nav|header|footer|aside|menu)[^>]*>.*?<\/\1>/si', '', $html);
        return $this->htmlToText($html);
    }

    /**
     * Extração agressiva de todo texto visível (para sites simples).
     */
    private function extractAllVisibleText(string $html): string
    {
        $html = preg_replace('/<(script|style|noscript|nav|header|footer)[^>]*>.*?<\/\1>/si', '', $html);
        return $this->htmlToText($html);
    }

    /**
     * Converter HTML em texto plano.
     */
    private function htmlToText(string $html): string
    {
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $html = preg_replace('/<\/(p|div|li|h[1-6]|tr|td|th)>/i', "\n", $html);
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        return trim($text);
    }

    /**
     * Sanitizar texto final — UTF-8 válido, sem caracteres de controle.
     */
    private function sanitizeText(string $text): string
    {
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1, Windows-1252, UTF-8');
        }
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
        return trim($text);
    }

    /**
     * Buscar links internos da mesma origem e extrair texto.
     */
    /**
     * Fetch do HTML bruto sem Jina — para extrair links <a href>.
     * Se HTML não  tiver links suficientes (site com JS), extrai do Jina.
     */
    private function fetchRawHtml(string $url): string
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/121.0.0.0 Safari/537.36',
                    'Accept'          => 'text/html,*/*;q=0.8',
                    'Accept-Language' => 'pt-BR,pt;q=0.9',
                ])
                ->get($url);

            if ($response->successful()) {
                $html = $response->body();

                // Verificar se tem links internos suficientes
                $parsedBase = parse_url($url);
                preg_match_all('/href=["\']([^"\'>\s]+)["\']/i', $html, $m);
                $internalCount = count(array_filter(
                    $m[1] ?? [],
                    fn($l) =>
                    str_starts_with($l, '/') && !preg_match('/\.(png|jpg|ico|css|js)$/i', $l)
                ));

                // Se tem links internos suficientes, usar HTML direto
                if ($internalCount >= 3) return $html;

                // Site com JS — buscar links do Jina (que executa JS)
                try {
                    $jinaUrl  = 'https://r.jina.ai/' . $url;
                    $jinaResp = Http::timeout(30)
                        ->withHeaders(['Accept' => 'text/plain', 'X-Return-Format' => 'markdown'])
                        ->get($jinaUrl);

                    if ($jinaResp->successful()) {
                        // Retornar como marcador especial para extractInternalLinks saber que é Jina
                        return '<!-- JINA_LINKS -->' . $jinaResp->body();
                    }
                } catch (\Exception $e) {
                }

                return $html;
            }
        } catch (\Exception $e) {
        }

        return '';
    }

    /**
     * Extrair links internos do HTML bruto ou do markdown do Jina.
     */
    private function extractInternalLinks(string $html, string $baseUrl): array
    {
        if (empty($html)) return [];

        $parsedBase = parse_url($baseUrl);
        $baseOrigin = $parsedBase['scheme'] . '://' . $parsedBase['host'];

        // Se veio do Jina — extrair URLs absolutas do markdown
        if (str_starts_with($html, '<!-- JINA_LINKS -->')) {
            $markdown = substr($html, 19);
            // Extrair URLs absolutas do mesmo domínio
            preg_match_all('#https?://' . preg_quote($parsedBase['host'], '#') . '[^\s\)\]"\'>,]+#i', $markdown, $m);
            $links = array_unique($m[0] ?? []);

            $result = [];
            foreach ($links as $link) {
                $clean = strtok($link, '?#'); // remover query e fragmento
                if ($clean === $baseUrl) continue;
                if (preg_match('/\.(pdf|jpg|jpeg|png|gif|zip|css|js|xml|rss|ico)$/i', $clean)) continue;
                $result[] = rtrim($clean, '/');
            }
            return array_unique(array_values($result));
        }

        // HTML direto — extrair hrefs
        preg_match_all("/href=[\"'](http[^\"'\\s>]+|\\/?[a-zA-Z0-9][^\"'\\s>]*)[\"'\\s]/i", $html, $matches);
        $rawLinks = array_unique($matches[1] ?? []);

        $links = [];
        foreach ($rawLinks as $link) {
            $link = trim($link);
            if (str_starts_with($link, '/')) {
                $link = $baseOrigin . $link;
            } elseif (!str_starts_with($link, 'http')) {
                continue;
            }

            $parsed = parse_url($link);
            if (($parsed['host'] ?? '') !== $parsedBase['host']) continue;

            $clean = $parsed['scheme'] . '://' . $parsed['host'] . ($parsed['path'] ?? '/');
            if ($clean === $baseUrl) continue;
            if (preg_match('/\.(pdf|jpg|jpeg|png|gif|zip|doc|xls|css|js|xml|rss|ico)$/i', $clean)) continue;

            $links[] = rtrim($clean, '/');
        }

        return array_unique(array_values($links));
    }

    /**
     * Buscar subpáginas a partir de lista de links.
     */
    private function fetchSubpages(array $links, string $baseUrl): array
    {
        $maxSubs  = 10;
        $subTexts = [];
        $fetched  = 0;
        $visited  = [rtrim($baseUrl, '/')];

        foreach ($links as $link) {
            if ($fetched >= $maxSubs) break;
            if (in_array($link, $visited)) continue;
            $visited[] = $link;

            try {
                $subHtml = $this->fetchUrl($link);
                $subText = $this->extractText($subHtml, $link);

                if (str_word_count(trim($subText)) >= 15) {
                    $subTitle   = $this->extractTitle($subHtml) ?? $link;
                    $subTexts[] = "=== {$subTitle} ({$link}) ===\n{$subText}";
                    $fetched++;
                }
            } catch (\Exception $e) {
                Log::debug("UrlIndexer subpágina {$link}: " . $e->getMessage());
            }
        }

        return $subTexts;
    }

    /**
     * Dividir texto em chunks com overlap.
     */
    private function splitIntoChunks(string $text): array
    {
        $words   = preg_split('/\s+/', trim($text));
        $total   = count($words);
        $chunks  = [];
        $i       = 0;

        while ($i < $total) {
            $slice    = array_slice($words, $i, $this->chunkSize);
            $chunks[] = implode(' ', $slice);
            $i       += ($this->chunkSize - $this->chunkOverlap);
        }

        return array_filter($chunks, fn($c) => str_word_count($c) >= 15);
    }
}
