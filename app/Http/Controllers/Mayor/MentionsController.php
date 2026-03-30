<?php

namespace App\Http\Controllers\Mayor;

use App\Http\Controllers\Controller;
use App\Models\MentionKeyword;
use App\Models\SocialMention;
use App\Services\Social\SocialMonitorService;
use Illuminate\Http\Request;

class MentionsController extends Controller
{
    public function __construct(private SocialMonitorService $monitor) {}

    // ── Dashboard ────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $user = $request->user();
        $municipality = $user->municipality;

        // Filtros
        $filter    = $request->get('filter', 'all'); // all | negative | positive | neutral | unread
        $source    = $request->get('source', 'all');
        $days      = (int) $request->get('days', 7);

        $query = SocialMention::where('municipality_id', $municipality->id)
            ->where('created_at', '>=', now()->subDays($days))
            ->orderByDesc('published_at');

        if ($filter === 'unread')  $query->where('is_read', false);
        elseif ($filter !== 'all') $query->where('sentiment', $filter);

        if ($source !== 'all') $query->where('source', $source);

        $mentions = $query->paginate(20)->withQueryString();

        // Stats
        $since = now()->subDays($days);
        $stats = [
            'total'    => SocialMention::where('municipality_id', $municipality->id)->where('created_at', '>=', $since)->count(),
            'positive' => SocialMention::where('municipality_id', $municipality->id)->where('created_at', '>=', $since)->where('sentiment', 'positive')->count(),
            'negative' => SocialMention::where('municipality_id', $municipality->id)->where('created_at', '>=', $since)->where('sentiment', 'negative')->count(),
            'neutral'  => SocialMention::where('municipality_id', $municipality->id)->where('created_at', '>=', $since)->where('sentiment', 'neutral')->count(),
            'unread'   => SocialMention::where('municipality_id', $municipality->id)->where('is_read', false)->count(),
        ];

        // Gráfico de sentimento por dia (últimos 7 dias)
        $chartData = $this->getChartData($municipality->id, $days);

        // Keywords configuradas
        $keywords = MentionKeyword::where('municipality_id', $municipality->id)
            ->orderBy('type')->get();

        return view('mayor.mentions.index', compact(
            'mentions',
            'stats',
            'chartData',
            'keywords',
            'filter',
            'source',
            'days',
            'municipality'
        ));
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

        $keywords = MentionKeyword::where('municipality_id', $municipality->id)
            ->where('is_active', true)->count();

        if ($keywords === 0) {
            return back()->with('error', 'Configure pelo menos uma palavra-chave antes de monitorar.');
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
            ];
        }
        return $data;
    }
}
