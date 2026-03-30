<?php

namespace App\Services\Social;

use App\Models\MentionKeyword;
use App\Models\Municipality;
use App\Models\SocialMention;
use App\Services\WebPushService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Monitora menções do município em fontes públicas gratuitas:
 * - Google News (via RSS — sem API key)
 * - Nitter (instância pública do Twitter)
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
        $keywords = MentionKeyword::where('municipality_id', $municipality->id)
            ->where('is_active', true)
            ->get();

        if ($keywords->isEmpty()) {
            return ['found' => 0, 'new' => 0, 'errors' => []];
        }

        $found  = 0;
        $new    = 0;
        $errors = [];

        foreach ($keywords as $keyword) {
            // Google News RSS
            try {
                $mentions = $this->fetchGoogleNews($keyword->keyword, $municipality);
                foreach ($mentions as $mention) {
                    $mention['keyword'] = $keyword->keyword;
                    if ($this->saveMention($mention, $municipality)) {
                        $new++;
                    }
                    $found++;
                }
            } catch (\Exception $e) {
                $errors[] = "Google News ({$keyword->keyword}): " . $e->getMessage();
                Log::warning("SocialMonitor Google News erro: " . $e->getMessage());
            }

            // Nitter (Twitter público)
            if ($keyword->type !== 'topic') {
                try {
                    $mentions = $this->fetchNitter($keyword->keyword, $municipality);
                    foreach ($mentions as $mention) {
                        $mention['keyword'] = $keyword->keyword;
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

        // Analisar sentimento das novas menções (em batch)
        if ($new > 0) {
            $this->analyzeSentimentBatch($municipality);
        }

        return ['found' => $found, 'new' => $new, 'errors' => $errors];
    }

    public function getScanTargets(Municipality $municipality, $keywords): array
    {
        $targetsByKeywordId = [];

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

            $targetsByKeywordId[$kw->id] = $targets;
        }

        return $targetsByKeywordId;
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

    /**
     * Analisar sentimento de uma menção específica.
     */
    private function analyzeSentiment(SocialMention $mention, Municipality $municipality): void
    {
        $text   = trim(($mention->title ?? '') . ' ' . ($mention->content ?? ''));
        $city   = $municipality->name;

        $prompt = "Analise o sentimento desta menção sobre {$city}. Responda APENAS com JSON:\n\n" .
                  "Texto: \"{$text}\"\n\n" .
                  '{"sentiment":"positive|negative|neutral","score":-100_to_100,"reason":"motivo_breve_em_portugues"}';

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

        $sentiment = in_array($json['sentiment'] ?? '', ['positive', 'negative', 'neutral'])
            ? $json['sentiment']
            : 'neutral';

        $mention->update([
            'sentiment'        => $sentiment,
            'sentiment_score'  => $json['score'] ?? 0,
            'sentiment_reason' => $json['reason'] ?? null,
        ]);

        // Enviar push se negativo e keyword configurada para alertar
        if ($sentiment === 'negative' && !$mention->alert_sent) {
            $this->sendNegativeAlert($mention, $municipality);
        }
    }

    /**
     * Enviar push notification para menção negativa.
     */
    private function sendNegativeAlert(SocialMention $mention, Municipality $municipality): void
    {
        try {
            $mayor = $municipality->mayor;
            if (!$mayor) return;

            $title   = $mention->title ? Str::limit($mention->title, 60) : Str::limit($mention->content ?? '', 60);
            $source  = $mention->source_label;

            $this->push->sendToUser($mayor, [
                'title' => "⚠️ Menção negativa detectada",
                'body'  => "{$source}: {$title}",
                'url'   => '/mayor/mentions',
                'icon'  => '/icon-192.png',
            ]);

            $mention->update(['alert_sent' => true]);
        } catch (\Exception $e) {
            Log::warning("Push de menção negativa falhou: " . $e->getMessage());
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
