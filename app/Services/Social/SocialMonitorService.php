<?php

namespace App\Services\Social;

use App\Models\MentionKeyword;
use App\Models\Municipality;
use App\Models\SocialMention;
use App\Models\User;
use App\Services\WebPushService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Monitora menções do município em fontes públicas gratuitas:
 * - Google News (via RSS)
 * - Twitter/X público (via instâncias Nitter)
 * - Portais locais configurados (via RSS/feeds públicos)
 */
class SocialMonitorService
{
    // Instâncias Nitter públicas (fallback em ordem)
    private array $nitterInstances = [
        'https://nitter.privacydev.net',
        'https://nitter.poast.org',
        'https://nitter.mint.lgbt',
    ];

    public function __construct(
        private WebPushService $push,
    ) {}

    /**
     * Buscar todas as menções de um município.
     * Retorna contagem de novas menções encontradas.
     */
    public function monitor(Municipality $municipality): array
    {
        $definitions = $this->monitoringDefinitions($municipality);

        if ($definitions->isEmpty()) {
            return ['found' => 0, 'new' => 0, 'errors' => []];
        }

        $found  = 0;
        $new    = 0;
        $errors = [];

        foreach ($definitions as $definition) {
            $term = (string) ($definition['term'] ?? '');
            if ($term === '') {
                continue;
            }

            // Google News RSS
            try {
                $mentions = $this->fetchGoogleNews($term, $municipality);
                foreach ($mentions as $mention) {
                    $mention['keyword'] = $term;
                    if ($this->saveMention($mention, $municipality)) {
                        $new++;
                    }
                    $found++;
                }
            } catch (\Exception $e) {
                $errors[] = "Google News ({$term}): " . $e->getMessage();
                Log::warning("SocialMonitor Google News erro: " . $e->getMessage());
            }

            // Nitter (Twitter público)
            if (!empty($definition['allow_social'])) {
                try {
                    $mentions = $this->fetchNitter($term, $municipality);
                    foreach ($mentions as $mention) {
                        $mention['keyword'] = $term;
                        if ($this->saveMention($mention, $municipality)) {
                            $new++;
                        }
                        $found++;
                    }
                } catch (\Exception $e) {
                    // Nitter pode estar fora — não é crítico
                    Log::debug("SocialMonitor Nitter erro: " . $e->getMessage());
                }
            }
        }

        foreach ($this->configuredPortalUrls($municipality) as $portalUrl) {
            try {
                $mentions = $this->fetchPortalMentions($portalUrl, $definitions, $municipality);
                foreach ($mentions as $mention) {
                    if ($this->saveMention($mention, $municipality)) {
                        $new++;
                    }
                    $found++;
                }
            } catch (\Exception $e) {
                $errors[] = "Portal configurado ({$portalUrl}): " . $e->getMessage();
                Log::warning("SocialMonitor portal RSS erro: " . $e->getMessage());
            }
        }

        // Analisar sentimento das novas menções (em batch)
        if ($new > 0) {
            $this->analyzeSentimentBatch($municipality);
        }

        return ['found' => $found, 'new' => $new, 'errors' => $errors];
    }

    public function getScanTargets(Municipality $municipality, $keywords): array
    {
        $targetsByKeywordId = [];
        $settings = is_array($municipality->settings) ? $municipality->settings : [];
        $configuredPortals = collect($this->configuredPortalUrls($municipality));
        $activeChannels = collect((array) data_get($settings, 'communication.channels', []))
            ->filter(fn ($channel) => !empty($channel['active']))
            ->keys()
            ->values();

        foreach ($keywords as $kw) {
            $targets = [];

            $state     = $municipality->state ?? '';
            $query     = urlencode('"' . $kw->keyword . '"');
            $fullQuery = $state ? urlencode('"' . $kw->keyword . '" ' . $state) : $query;

            $targets[] = [
                'source' => 'Google News (RSS)',
                'url'    => "https://news.google.com/rss/search?q={$fullQuery}&hl=pt-BR&gl=BR&ceid=BR:pt-419",
            ];

            if ($kw->type !== 'topic') {
                $q = urlencode($kw->keyword . ' lang:pt');
                foreach ($this->nitterInstances as $instance) {
                    $targets[] = [
                        'source' => 'Twitter/X (Nitter RSS)',
                        'url'    => "{$instance}/search/rss?q={$q}&f=tweets",
                    ];
                }
            }

            foreach ($configuredPortals as $portal) {
                $portalHost = parse_url(str_starts_with($portal, 'http') ? $portal : 'https://' . $portal, PHP_URL_HOST) ?: $portal;
                $targets[] = [
                    'source' => 'Portal local configurado',
                    'url' => 'https://' . ltrim((string) $portalHost, '/'),
                ];
            }

            foreach ($activeChannels as $channel) {
                $channelUrl = (string) data_get($settings, "communication.channels.{$channel}.url", '');
                if ($channelUrl !== '') {
                    $targets[] = [
                        'source' => 'Canal oficial configurado',
                        'url' => $channelUrl,
                    ];
                }
            }

            $targetsByKeywordId[$kw->id] = $targets;
        }

        return $targetsByKeywordId;
    }

    public function hasMonitoringCoverage(Municipality $municipality): bool
    {
        return $this->monitoringDefinitions($municipality)->isNotEmpty();
    }

    /**
     * Buscar notícias no Google News via RSS (gratuito, sem API key).
     */
    private function fetchGoogleNews(string $keyword, Municipality $municipality): array
    {
        $query    = urlencode('"' . $keyword . '"');
        $state    = $municipality->state ?? '';
        $fullQuery = $state ? urlencode('"' . $keyword . '" ' . $state) : $query;

        $rssUrl = "https://news.google.com/rss/search?q={$fullQuery}&hl=pt-BR&gl=BR&ceid=BR:pt-419";

        $response = Http::timeout(15)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; MeuMarqueteiro/1.0)'])
            ->get($rssUrl);

        if (!$response->successful()) {
            throw new \Exception("HTTP {$response->status()}");
        }

        $xml = simplexml_load_string($response->body());
        if (!$xml) {
            throw new \Exception("XML inválido");
        }

        $mentions = [];
        $items    = $xml->channel->item ?? [];
        $limit    = 20;
        $count    = 0;

        foreach ($items as $item) {
            if ($count++ >= $limit) break;

            $pubDate = isset($item->pubDate) ? strtotime((string)$item->pubDate) : null;

            // Só últimas 24h para monitoramento diário
            if ($pubDate && (time() - $pubDate) > 86400 * 3) continue;

            $mentions[] = [
                'source'       => 'google_news',
                'platform'     => 'news',
                'title'        => $this->cleanText((string)($item->title ?? '')),
                'content'      => $this->cleanText((string)($item->description ?? '')),
                'url'          => $this->normalizeUrl((string)($item->link ?? '')),
                'author'       => $this->extractPublisher((string)($item->source ?? '')),
                'published_at' => $pubDate ? date('Y-m-d H:i:s', $pubDate) : null,
                'external_id'  => md5((string)($item->link ?? '') . $municipality->id),
            ];
        }

        return $mentions;
    }

    /**
     * Buscar tweets no Nitter (interface pública do Twitter — sem API key).
     */
    private function fetchNitter(string $keyword, Municipality $municipality): array
    {
        $query = urlencode($keyword . ' lang:pt');

        foreach ($this->nitterInstances as $instance) {
            try {
                $rssUrl   = "{$instance}/search/rss?q={$query}&f=tweets";
                $response = Http::timeout(10)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                    ->get($rssUrl);

                if (!$response->successful()) continue;

                $xml = simplexml_load_string($response->body());
                if (!$xml) continue;

                $mentions = [];
                $items    = $xml->channel->item ?? [];
                $limit    = 15;
                $count    = 0;

                foreach ($items as $item) {
                    if ($count++ >= $limit) break;

                    $pubDate = isset($item->pubDate) ? strtotime((string)$item->pubDate) : null;
                    if ($pubDate && (time() - $pubDate) > 86400 * 3) continue;

                    $link    = (string)($item->link ?? '');
                    $content = $this->cleanText(strip_tags((string)($item->description ?? '')));

                    $mentions[] = [
                        'source'       => 'nitter',
                        'platform'     => 'twitter',
                        'title'        => null,
                        'content'      => $content,
                        'url'          => $link,
                        'author'       => $this->extractNitterAuthor($link),
                        'published_at' => $pubDate ? date('Y-m-d H:i:s', $pubDate) : null,
                        'external_id'  => md5($link . $municipality->id),
                    ];
                }

                return $mentions; // sucesso na primeira instância disponível

            } catch (\Exception $e) {
                continue; // tentar próxima instância
            }
        }

        return []; // todas as instâncias falharam
    }

    private function fetchPortalMentions(string $portalUrl, Collection $definitions, Municipality $municipality): array
    {
        $feedUrl = $this->discoverFeedUrl($portalUrl);
        if ($feedUrl === null) {
            return [];
        }

        $response = Http::timeout(12)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; MeuMarqueteiro/1.0)'])
            ->get($feedUrl);

        if (!$response->successful()) {
            throw new \Exception("HTTP {$response->status()}");
        }

        $xml = simplexml_load_string($response->body());
        if (!$xml) {
            throw new \Exception('Feed XML inválido');
        }

        $items = $xml->channel->item ?? [];
        $host = parse_url($portalUrl, PHP_URL_HOST) ?: $portalUrl;
        $mentions = [];
        $count = 0;

        foreach ($items as $item) {
            if ($count++ >= 20) {
                break;
            }

            $pubDate = isset($item->pubDate) ? strtotime((string) $item->pubDate) : null;
            if ($pubDate && (time() - $pubDate) > 86400 * 7) {
                continue;
            }

            $title = $this->cleanText((string) ($item->title ?? ''));
            $description = $this->cleanText((string) ($item->description ?? ''));
            $matchedTerm = $this->matchMonitoringDefinition(trim($title . ' ' . $description), $definitions);

            if ($matchedTerm === null) {
                continue;
            }

            $link = $this->normalizeUrl((string) ($item->link ?? ''));

            $mentions[] = [
                'source' => 'portal_rss',
                'platform' => 'news',
                'keyword' => $matchedTerm,
                'title' => $title,
                'content' => $description,
                'url' => $link,
                'author' => $this->extractPublisher((string) ($item->source ?? '')) ?: $host,
                'published_at' => $pubDate ? date('Y-m-d H:i:s', $pubDate) : null,
                'external_id' => md5($host . '|' . $link . '|' . $municipality->id),
            ];
        }

        return $mentions;
    }

    /**
     * Salvar menção no banco (ignora duplicatas via external_id).
     * Retorna true se foi nova menção.
     */
    private function saveMention(array $data, Municipality $municipality): bool
    {
        if (empty($data['external_id'])) return false;

        // Verificar se já existe
        if (SocialMention::where('municipality_id', $municipality->id)
            ->where('external_id', $data['external_id'])
            ->exists()) {
            return false;
        }

        SocialMention::create(array_merge($data, [
            'municipality_id' => $municipality->id,
            'sentiment'       => 'pending',
        ]));

        return true;
    }

    private function normalizeUrl(string $url): string
    {
        $url = trim($url);
        $url = rtrim($url, "`,");
        if (str_starts_with($url, '//')) {
            $url = 'https:' . $url;
        }
        if (!str_starts_with($url, 'http')) {
            $url = 'https://' . ltrim($url, '/');
        }
        if (mb_strlen($url) > 2000) {
            $url = mb_substr($url, 0, 2000);
        }
        return $url;
    }

    /**
     * Analisar sentimento das menções pendentes via Claude.
     */
    public function analyzeSentimentBatch(Municipality $municipality): void
    {
        $pending = SocialMention::where('municipality_id', $municipality->id)
            ->where('sentiment', 'pending')
            ->whereNotNull('content')
            ->limit(20)
            ->get();

        if ($pending->isEmpty()) return;

        foreach ($pending as $mention) {
            try {
                $this->analyzeSentiment($mention, $municipality);
            } catch (\Exception $e) {
                Log::warning("Sentimento falhou para mention {$mention->id}: " . $e->getMessage());
                $mention->update(['sentiment' => 'neutral']);
            }
        }
    }

    public function analyzeMentionNow(SocialMention $mention, Municipality $municipality): void
    {
        try {
            $this->analyzeSentiment($mention, $municipality);
        } catch (\Exception $e) {
            Log::warning("Sentimento falhou para mention {$mention->id}: " . $e->getMessage());
            $mention->update(['sentiment' => 'neutral']);
        }
    }

    /**
     * Analisar sentimento de uma menção específica.
     */
    private function analyzeSentiment(SocialMention $mention, Municipality $municipality): void
    {
        $text   = trim(($mention->title ?? '') . ' ' . ($mention->content ?? ''));
        $city   = $municipality->name;

        $prompt = "Analise o sentimento desta menção sobre {$city}. Classifique em positive, neutral, negative ou urgent. Use urgent somente quando houver potencial de crise, viralizacao, acusacao sensivel, cobranca institucional forte ou risco reputacional imediato. Responda APENAS com JSON:\n\n" .
                  "Texto: \"{$text}\"\n\n" .
                  '{"sentiment":"positive|negative|neutral|urgent","score":-100_to_100,"reason":"motivo_breve_em_portugues"}';

        $response = Http::timeout(20)
            ->withHeaders([
                'x-api-key'         => env('ANTHROPIC_API_KEY'),
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-haiku-4-5-20251001',
                'max_tokens' => 150,
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if (!$response->successful()) {
            $mention->update(['sentiment' => 'neutral']);
            return;
        }

        $content = $response->json('content.0.text', '');
        // Extrair JSON da resposta
        preg_match('/\{[^}]+\}/s', $content, $matches);
        $json = json_decode($matches[0] ?? '{}', true);

        $sentiment = in_array($json['sentiment'] ?? '', ['positive', 'negative', 'neutral', 'urgent'])
            ? $json['sentiment']
            : 'neutral';

        $mention->update([
            'sentiment'        => $sentiment,
            'sentiment_score'  => $json['score'] ?? 0,
            'sentiment_reason' => $json['reason'] ?? null,
        ]);

        // Enviar alerta para menções negativas e urgentes
        if (in_array($sentiment, ['negative', 'urgent'], true) && !$mention->alert_sent && $this->shouldAlertMention($mention, $municipality)) {
            $this->sendMentionAlert($mention, $municipality);
        }
    }

    private function shouldAlertMention(SocialMention $mention, Municipality $municipality): bool
    {
        if ($mention->sentiment === 'urgent') {
            return true;
        }

        if ($mention->sentiment !== 'negative') {
            return false;
        }

        if (!$mention->keyword) {
            return true;
        }

        $keyword = MentionKeyword::where('municipality_id', $municipality->id)
            ->where('keyword', $mention->keyword)
            ->first();

        if (!$keyword instanceof MentionKeyword) {
            return true;
        }

        return (bool) $keyword->alert_negative;
    }

    /**
     * Enviar push notification para menção negativa ou urgente.
     */
    private function sendMentionAlert(SocialMention $mention, Municipality $municipality): void
    {
        try {
            $recipients = $municipality->users()
                ->active()
                ->municipalOperators()
                ->get();

            if ($recipients->isEmpty() && $municipality->mayor) {
                $recipients = collect([$municipality->mayor]);
            }

            if ($recipients->isEmpty()) {
                return;
            }

            $title = $mention->title ? Str::limit($mention->title, 60) : Str::limit($mention->content ?? '', 60);
            $source = $mention->source_label;
            $isUrgent = $mention->sentiment === 'urgent';
            $payload = [
                'title' => $isUrgent ? '🚨 Menção urgente detectada' : '⚠️ Menção negativa detectada',
                'body' => "{$source}: {$title}",
                'url' => $isUrgent ? '/mayor/content?tab=crisis&mention=' . $mention->id : '/mayor/mentions',
                'icon' => '/icon-192.png',
            ];

            /** @var User $recipient */
            foreach ($recipients as $recipient) {
                $this->push->sendToUser($recipient, $payload);
            }

            $mention->update(['alert_sent' => true]);
        } catch (\Exception $e) {
            Log::warning("Push de menção falhou: " . $e->getMessage());
        }
    }

    /**
     * Gerar resumo das menções das últimas 24h para o briefing matinal.
     */
    public function getDailySummary(Municipality $municipality): ?string
    {
        $since = now()->subDay();

        $mentions = SocialMention::where('municipality_id', $municipality->id)
            ->where('created_at', '>=', $since)
            ->whereIn('sentiment', ['positive', 'negative', 'neutral'])
            ->orderByDesc('published_at')
            ->get();

        if ($mentions->isEmpty()) return null;

        $positive = $mentions->where('sentiment', 'positive')->count();
        $negative = $mentions->where('sentiment', 'negative')->count();
        $neutral  = $mentions->where('sentiment', 'neutral')->count();
        $total    = $mentions->count();

        $summary  = "## Monitoramento de Redes e Notícias (últimas 24h)\n\n";
        $summary .= "Total de menções: {$total} | ✅ Positivas: {$positive} | ⚠️ Negativas: {$negative} | Neutras: {$neutral}\n\n";

        // Destacar as negativas
        $negatives = $mentions->where('sentiment', 'negative')->take(3);
        if ($negatives->isNotEmpty()) {
            $summary .= "**Menções negativas que merecem atenção:**\n";
            foreach ($negatives as $m) {
                $text = $m->title ?? Str::limit($m->content ?? '', 100);
                $summary .= "- [{$m->source_label}] {$text}\n";
            }
            $summary .= "\n";
        }

        // Destacar as positivas
        $positives = $mentions->where('sentiment', 'positive')->take(2);
        if ($positives->isNotEmpty()) {
            $summary .= "**Destaque positivo:**\n";
            foreach ($positives as $m) {
                $text = $m->title ?? Str::limit($m->content ?? '', 100);
                $summary .= "- [{$m->source_label}] {$text}\n";
            }
        }

        return $summary;
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function cleanText(string $text): string
    {
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    private function monitoringDefinitions(Municipality $municipality): Collection
    {
        $definitions = [];

        MentionKeyword::query()
            ->where('municipality_id', $municipality->id)
            ->where('is_active', true)
            ->get()
            ->each(function (MentionKeyword $keyword) use (&$definitions): void {
                $term = trim((string) $keyword->keyword);
                if ($term === '') {
                    return;
                }

                $definitions[mb_strtolower($term)] = [
                    'term' => $term,
                    'allow_social' => $keyword->type !== 'topic',
                    'origin' => 'keyword',
                ];
            });

        foreach ($this->splitList((string) data_get($municipality->settings, 'communication.monitoring.terms_text', '')) as $term) {
            $key = mb_strtolower($term);

            if (!isset($definitions[$key])) {
                $definitions[$key] = [
                    'term' => $term,
                    'allow_social' => true,
                    'origin' => 'settings',
                ];
                continue;
            }

            $definitions[$key]['allow_social'] = true;
        }

        return collect(array_values($definitions));
    }

    private function configuredPortalUrls(Municipality $municipality): array
    {
        return $this->splitList((string) data_get($municipality->settings, 'communication.monitoring.portals', ''));
    }

    private function splitList(string $value): array
    {
        return collect(preg_split('/[\n,;]+/', $value))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->unique(fn ($item) => mb_strtolower($item))
            ->values()
            ->all();
    }

    private function matchMonitoringDefinition(string $text, Collection $definitions): ?string
    {
        $haystack = mb_strtolower($text);
        if ($haystack === '') {
            return null;
        }

        foreach ($definitions as $definition) {
            $term = mb_strtolower((string) ($definition['term'] ?? ''));
            if ($term !== '' && str_contains($haystack, $term)) {
                return (string) $definition['term'];
            }
        }

        return null;
    }

    private function discoverFeedUrl(string $portalUrl): ?string
    {
        $portalUrl = $this->normalizeUrl($portalUrl);
        $response = Http::timeout(10)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; MeuMarqueteiro/1.0)'])
            ->get($portalUrl);

        if (!$response->successful()) {
            throw new \Exception("HTTP {$response->status()}");
        }

        $contentType = strtolower((string) $response->header('Content-Type'));
        if (str_contains($contentType, 'xml') || str_contains($contentType, 'rss')) {
            return $portalUrl;
        }

        $feedUrl = $this->extractFeedUrlFromHtml($portalUrl, (string) $response->body());
        if ($feedUrl !== null) {
            return $feedUrl;
        }

        foreach (['/feed', '/rss', '/rss.xml', '/feed.xml', '/feeds/posts/default?alt=rss'] as $suffix) {
            $candidate = rtrim($portalUrl, '/') . $suffix;

            try {
                $candidateResponse = Http::timeout(8)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; MeuMarqueteiro/1.0)'])
                    ->get($candidate);

                if (!$candidateResponse->successful()) {
                    continue;
                }

                $candidateType = strtolower((string) $candidateResponse->header('Content-Type'));
                if (str_contains($candidateType, 'xml') || str_contains($candidateType, 'rss')) {
                    return $candidate;
                }

                if (simplexml_load_string($candidateResponse->body())) {
                    return $candidate;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private function extractFeedUrlFromHtml(string $baseUrl, string $html): ?string
    {
        if (trim($html) === '') {
            return null;
        }

        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $loaded = $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            return null;
        }

        foreach ($dom->getElementsByTagName('link') as $link) {
            $type = strtolower(trim((string) $link->getAttribute('type')));
            $rel = strtolower(trim((string) $link->getAttribute('rel')));

            if ($rel === '' || !str_contains($rel, 'alternate')) {
                continue;
            }

            if (!str_contains($type, 'rss') && !str_contains($type, 'xml') && !str_contains($type, 'atom')) {
                continue;
            }

            $href = trim((string) $link->getAttribute('href'));
            if ($href !== '') {
                return $this->resolveUrl($baseUrl, $href);
            }
        }

        return null;
    }

    private function resolveUrl(string $baseUrl, string $relativeUrl): string
    {
        if (str_starts_with($relativeUrl, 'http://') || str_starts_with($relativeUrl, 'https://')) {
            return $relativeUrl;
        }

        if (str_starts_with($relativeUrl, '//')) {
            $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';
            return $scheme . ':' . $relativeUrl;
        }

        $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';
        $host = parse_url($baseUrl, PHP_URL_HOST) ?: '';
        if ($host === '') {
            return $this->normalizeUrl($relativeUrl);
        }

        if (str_starts_with($relativeUrl, '/')) {
            return $scheme . '://' . $host . $relativeUrl;
        }

        $path = (string) parse_url($baseUrl, PHP_URL_PATH);
        $basePath = rtrim(str_replace('\\', '/', dirname($path !== '' ? $path : '/')), '/');

        return rtrim($scheme . '://' . $host . ($basePath !== '' ? $basePath : ''), '/') . '/' . ltrim($relativeUrl, '/');
    }

    private function extractPublisher(string $source): ?string
    {
        if (empty($source)) return null;
        return strip_tags($source);
    }

    private function extractNitterAuthor(string $url): ?string
    {
        if (preg_match('/nitter\.[^\/]+\/([^\/]+)\/status/', $url, $m)) {
            return '@' . $m[1];
        }
        return null;
    }
}
