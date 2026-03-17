<?php

namespace App\Http\Controllers\Mayor;

use App\Http\Controllers\Controller;
use App\Models\MorningBriefing;
use App\Services\AI\MorningBriefingService;
use Illuminate\Http\JsonResponse;

class BriefingController extends Controller
{
    public function __construct(private MorningBriefingService $service) {}

    public function index()
    {
        $municipality = auth()->user()->municipality;

        $briefings = $municipality->morningBriefings()
            ->orderByDesc('date')
            ->paginate(15);

        $todayBriefing = $municipality->morningBriefings()
            ->whereDate('date', today())
            ->first();

        return view('mayor.briefings.index', compact('briefings', 'todayBriefing'));
    }

    public function show(MorningBriefing $briefing)
    {
        $this->authorizeAccess($briefing);

        if (!$briefing->read_at) {
            $briefing->update(['read_at' => now()]);
        }

        return view('mayor.briefings.show', compact('briefing'));
    }

    public function markRead(MorningBriefing $briefing): JsonResponse
    {
        $this->authorizeAccess($briefing);
        $briefing->update(['read_at' => now()]);
        return response()->json(['ok' => true]);
    }

    /**
     * Gerar briefing sob demanda (quando o automático ainda não rodou hoje).
     */
    public function generate(): JsonResponse
    {
        $municipality = auth()->user()->municipality;

        // Verificar se já existe hoje
        $existing = $municipality->morningBriefings()
            ->whereDate('date', today())
            ->first();

        if ($existing) {
            return response()->json([
                'ok'          => true,
                'already_had' => true,
                'briefing_id' => $existing->id,
                'redirect'    => route('mayor.mandato.briefings.show', $existing),
            ]);
        }

        try {
            $briefing = $this->service->generate($municipality);

            return response()->json([
                'ok'          => true,
                'already_had' => false,
                'briefing_id' => $briefing->id,
                'redirect'    => route('mayor.mandato.briefings.show', $briefing),
            ]);
        } catch (\Throwable $e) {
            \Log::error("Falha ao gerar briefing sob demanda para {$municipality->name}: " . $e->getMessage());
            return response()->json(['ok' => false, 'error' => 'Não foi possível gerar o briefing agora. Tente novamente em instantes.'], 500);
        }
    }

    private function authorizeAccess(MorningBriefing $briefing): void
    {
        if ($briefing->municipality_id !== auth()->user()->municipality_id) {
            abort(403);
        }
    }
}
