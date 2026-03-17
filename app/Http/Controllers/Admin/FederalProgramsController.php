<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FederalProgramAlert;
use App\Models\Municipality;
use App\Services\FederalPrograms\FederalProgramSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class FederalProgramsController extends Controller
{
    // ── Painel de sincronismo ────────────────────────────────────────────
    public function index()
    {
        $municipalities = Municipality::where('subscription_active', true)
            ->withCount(['federalPrograms'])
            ->orderBy('name')
            ->get();

        // Estatísticas globais
        $stats = [
            'total'        => FederalProgramAlert::count(),
            'open'         => FederalProgramAlert::where('status', 'open')->count(),
            'closing'      => FederalProgramAlert::where('status', 'closing')->count(),
            'applied'      => FederalProgramAlert::where('status', 'applied')->count(),
            'high_match'   => FederalProgramAlert::where('match_score', '>=', 0.85)->count(),
            'last_sync'    => Municipality::whereNotNull('data_last_synced_at')->max('data_last_synced_at'),
        ];

        // Programas por município com match score médio
        $programStats = FederalProgramAlert::selectRaw(
            "municipality_id, count(*) as total, avg(match_score) as avg_score,
             sum(case when status = 'open' then 1 else 0 end) as open_count,
             max(updated_at) as last_updated"
        )
            ->groupBy('municipality_id')
            ->get()
            ->keyBy('municipality_id');

        return view('admin.federal-programs.index', compact('municipalities', 'stats', 'programStats'));
    }

    // ── Sync de um município via AJAX ────────────────────────────────────
    public function syncMunicipality(Request $request, Municipality $municipality)
    {
        $force = $request->boolean('force', false);

        try {
            $service = app(FederalProgramSyncService::class);
            $result  = $service->sync($municipality, force: $force);

            return response()->json([
                'ok'      => true,
                'message' => "✅ {$municipality->name}: {$result['novos']} novos, {$result['atualizados']} atualizados, {$result['descartados']} descartados.",
                'result'  => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok'      => false,
                'message' => "Erro: " . $e->getMessage(),
            ], 500);
        }
    }

    // ── Sync geral — todos os municípios via Artisan (background) ────────
    public function syncAll(Request $request)
    {
        try {
            // Dispara em background para não timeout
            Artisan::queue('marqueteiro:sync-federal-programs', [
                '--force' => $request->boolean('force'),
            ]);

            return response()->json([
                'ok'      => true,
                'message' => 'Job enfileirado para todos os municípios ativos. Acompanhe o progresso abaixo.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ── Detalhes dos programas de um município ───────────────────────────
    public function municipalityPrograms(Municipality $municipality)
    {
        $programs = FederalProgramAlert::where('municipality_id', $municipality->id)
            ->orderByDesc('match_score')
            ->orderBy('deadline')
            ->get();

        return response()->json([
            'municipality' => $municipality->name,
            'programs'     => $programs,
        ]);
    }

    // ── Deletar programas desatualizados de um município ─────────────────
    public function clearMunicipality(Municipality $municipality)
    {
        $deleted = FederalProgramAlert::where('municipality_id', $municipality->id)
            ->where('status', 'closed')
            ->delete();

        return response()->json([
            'ok'      => true,
            'message' => "{$deleted} programa(s) encerrado(s) removido(s) de {$municipality->name}.",
        ]);
    }
}
