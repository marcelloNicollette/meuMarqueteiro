<?php

namespace App\Http\Controllers\Mayor;

use App\Http\Controllers\Controller;
use App\Models\MentionKeyword;
use App\Models\SocialMention;
use App\Services\Social\SocialMonitorService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MentionsController extends Controller
{
    public function __construct(private SocialMonitorService $monitor) {}

    // ── Dashboard ────────────────────────────────────────────────────

    public function index(Request $request)
    {
        return redirect()->route('mayor.content.index', [
            'area' => 'mentions',
            'mention_filter' => $request->get('filter', 'all'),
            'mention_source' => $request->get('source', 'all'),
            'mention_days' => (int) $request->get('days', 7),
        ]);
    }

    // ── Marcar como lida ─────────────────────────────────────────────

    public function markRead(Request $request)
    {
        $user = $request->user();
        $municipality = $user->municipality;

        if ($request->mention_id) {
            SocialMention::where('municipality_id', $municipality->id)
                ->where('id', $request->mention_id)
                ->update(['is_read' => true]);
        } else {
            // Marcar todas como lidas
            SocialMention::where('municipality_id', $municipality->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);
        }

        return back()->with('success', 'Menções marcadas como lidas.');
    }

    // ── Buscar agora (manual) ─────────────────────────────────────────

    public function refresh()
    {
        $user = request()->user();
        $municipality = $user->municipality;

        if (!$this->monitor->hasMonitoringCoverage($municipality)) {
            return back()->with('error', 'Configure pelo menos um termo ou palavra-chave de monitoramento antes de buscar menções.');
        }

        try {
            $result = $this->monitor->monitor($municipality);
            $msg = "{$result['new']} novas menções encontradas.";
            if (!empty($result['errors'])) {
                $msg .= ' Avisos: ' . implode('; ', $result['errors']);
            }
            return back()->with('success', $msg);
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao buscar menções: ' . $e->getMessage());
        }
    }

    // ── Configuração de palavras-chave ────────────────────────────────

    public function config()
    {
        $user = request()->user();
        $municipality = $user->municipality;
        $keywords     = MentionKeyword::where('municipality_id', $municipality->id)
            ->orderBy('type')->get();

        $scanTargets = $this->monitor->getScanTargets($municipality, $keywords);

        return view('mayor.mentions.config', compact('keywords', 'municipality', 'scanTargets'));
    }

    public function storeKeyword(Request $request)
    {
        $user = $request->user();
        $municipality = $user->municipality;

        $data = $request->validate([
            'keyword'        => 'required|string|max:100',
            'type'           => 'required|in:city,mayor,secretary,topic,hashtag',
            'alert_negative' => 'boolean',
        ]);

        // Não duplicar
        $exists = MentionKeyword::where('municipality_id', $municipality->id)
            ->where('keyword', $data['keyword'])->exists();

        if ($exists) {
            return back()->with('error', "A palavra-chave \"{$data['keyword']}\" já está cadastrada.");
        }

        MentionKeyword::create(array_merge($data, [
            'municipality_id' => $municipality->id,
            'alert_negative'  => $request->boolean('alert_negative', true),
        ]));

        return back()->with('success', "Palavra-chave \"{$data['keyword']}\" adicionada.");
    }

    public function storeManualMention(Request $request)
    {
        $user = $request->user();
        $municipality = $user->municipality;

        $data = $request->validate([
            'channel' => 'required|in:whatsapp,news,social,manual',
            'title' => 'nullable|string|max:255',
            'content' => 'required|string|max:4000',
            'author' => 'nullable|string|max:255',
            'url' => 'nullable|url|max:2000',
        ]);

        $mention = SocialMention::create([
            'municipality_id' => $municipality->id,
            'source' => 'manual_' . $data['channel'],
            'platform' => match ($data['channel']) {
                'whatsapp' => 'whatsapp',
                'news' => 'news',
                'social' => 'social',
                default => 'manual',
            },
            'keyword' => null,
            'title' => $data['title'] ?: Str::limit($data['content'], 120, ''),
            'content' => $data['content'],
            'url' => $data['url'] ?? null,
            'author' => $data['author'] ?? null,
            'published_at' => now(),
            'sentiment' => 'pending',
            'is_read' => false,
            'alert_sent' => false,
            'external_id' => md5($municipality->id . '|' . now()->timestamp . '|' . Str::random(12)),
        ]);

        $this->monitor->analyzeMentionNow($mention, $municipality);

        return back()->with('success', 'Menção manual registrada e classificada com sucesso.');
    }

    public function reclassify(Request $request, SocialMention $mention)
    {
        $user = $request->user();
        $municipality = $user->municipality;

        abort_if((int) $mention->municipality_id !== (int) $municipality->id, 403);

        $data = $request->validate([
            'sentiment' => 'required|in:positive,neutral,negative,urgent',
        ]);

        $mention->update([
            'sentiment' => $data['sentiment'],
        ]);

        return back()->with('success', 'Classificação da menção atualizada manualmente.');
    }

    public function destroyKeyword($id)
    {
        $user = request()->user();
        $municipality = $user->municipality;
        MentionKeyword::where('municipality_id', $municipality->id)->findOrFail($id)->delete();
        return back()->with('success', 'Palavra-chave removida.');
    }

    public function toggleKeyword($id)
    {
        $user = request()->user();
        $municipality = $user->municipality;
        $kw = MentionKeyword::where('municipality_id', $municipality->id)->findOrFail($id);
        $kw->update(['is_active' => !$kw->is_active]);
        return back()->with('success', $kw->is_active ? 'Palavra-chave ativada.' : 'Pausada.');
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function getChartData(int $municipalityId, int $days): array
    {
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date   = now()->subDays($i)->format('Y-m-d');
            $label  = now()->subDays($i)->locale('pt_BR')->isoFormat('DD/MM');

            $counts = SocialMention::where('municipality_id', $municipalityId)
                ->whereDate('created_at', $date)
                ->selectRaw('sentiment, count(*) as total')
                ->groupBy('sentiment')
                ->pluck('total', 'sentiment');

            $data[] = [
                'label'    => $label,
                'positive' => $counts['positive'] ?? 0,
                'negative' => $counts['negative'] ?? 0,
                'neutral'  => $counts['neutral']  ?? 0,
                'urgent'   => $counts['urgent'] ?? 0,
            ];
        }
        return $data;
    }

    private function buildReputationBoard(array $stats): array
    {
        $total = max(1, (int) ($stats['total'] ?? 0));

        return [
            'segments' => [
                [
                    'key' => 'positive',
                    'label' => 'Positivas',
                    'count' => (int) ($stats['positive'] ?? 0),
                    'percent' => round(((int) ($stats['positive'] ?? 0) / $total) * 100, 1),
                    'color' => '#1e7e48',
                ],
                [
                    'key' => 'neutral',
                    'label' => 'Neutras',
                    'count' => (int) ($stats['neutral'] ?? 0),
                    'percent' => round(((int) ($stats['neutral'] ?? 0) / $total) * 100, 1),
                    'color' => '#94a3b8',
                ],
                [
                    'key' => 'negative',
                    'label' => 'Negativas',
                    'count' => (int) ($stats['negative'] ?? 0),
                    'percent' => round(((int) ($stats['negative'] ?? 0) / $total) * 100, 1),
                    'color' => '#b52b2b',
                ],
                [
                    'key' => 'urgent',
                    'label' => 'Urgentes',
                    'count' => (int) ($stats['urgent'] ?? 0),
                    'percent' => round(((int) ($stats['urgent'] ?? 0) / $total) * 100, 1),
                    'color' => '#ea580c',
                ],
            ],
        ];
    }

    private function mapSourceLabel(string $source): string
    {
        return match ($source) {
            'google_news' => 'Google News',
            'nitter' => 'Twitter/X',
            'rss' => 'RSS',
            'manual_whatsapp' => 'WhatsApp manual',
            'manual_news' => 'Portal manual',
            'manual_social' => 'Rede social manual',
            'manual_manual' => 'Manual',
            default => Str::headline(str_replace('_', ' ', $source)),
        };
    }
}
