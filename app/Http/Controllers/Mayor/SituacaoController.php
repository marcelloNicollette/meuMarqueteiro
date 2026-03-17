<?php

namespace App\Http\Controllers\Mayor;

use App\Http\Controllers\Controller;

class SituacaoController extends Controller
{
    public function index()
    {
        $user         = auth()->user();
        $municipality = $user->municipality;

        // ── Compromissos ──────────────────────────────────────
        $commitments = $municipality->governmentCommitments()->get();

        $totalGeral    = $commitments->count();
        $entregues     = $commitments->where('status', 'entregue')->count();
        $emAndamento   = $commitments->where('status', 'em_andamento')->count();
        $emRisco       = $commitments->where('status', 'em_risco')->count();
        $prometidos    = $commitments->where('status', 'prometido')->count();
        $cancelados    = $commitments->where('status', 'cancelado')->count();

        $pctConcluido  = $totalGeral > 0 ? round(($entregues / $totalGeral) * 100) : 0;

        // Por área temática
        $porArea = $commitments->groupBy('area')->map(fn($g) => [
            'total'     => $g->count(),
            'entregues' => $g->where('status', 'entregue')->count(),
            'em_risco'  => $g->where('status', 'em_risco')->count(),
            'pct'       => $g->count() > 0
                ? round(($g->where('status', 'entregue')->count() / $g->count()) * 100)
                : 0,
        ])->sortByDesc('total');

        // Compromissos em risco — críticos em destaque
        $emRiscoItems = $commitments
            ->where('status', 'em_risco')
            ->sortBy('deadline');

        // Compromissos próximos do prazo (30 dias)
        $prazosProximos = $commitments
            ->filter(
                fn($c) =>
                $c->deadline &&
                    !in_array($c->status, ['entregue', 'cancelado']) &&
                    $c->deadline->diffInDays(now(), false) >= -30 &&
                    $c->deadline->diffInDays(now(), false) <= 0
            )
            ->sortBy('deadline')
            ->take(5);

        // Recém entregues (últimos 60 dias)
        $recentesEntregues = $commitments
            ->filter(
                fn($c) =>
                $c->status === 'entregue' &&
                    $c->delivered_at &&
                    $c->delivered_at->diffInDays(now()) <= 60
            )
            ->sortByDesc('delivered_at')
            ->take(4);

        // ── Recursos federais captados ────────────────────────
        $programas         = $municipality->federalPrograms()->get();
        $totalProgramas    = $programas->count();
        $programasAbertos  = $programas->whereIn('status', ['open'])->count();
        $programasMonitor  = $programas->whereIn('status', ['monitoring'])->count();

        // Valor total captado (convênios com valor)
        $valorCaptado = $programas
            ->whereNotNull('max_value')
            ->where('ai_matched', true)
            ->sum('max_value');

        $topProgramas = $programas
            ->whereIn('status', ['open', 'monitoring'])
            ->sortByDesc('match_score')
            ->take(3);

        // ── Indicadores de atividade ──────────────────────────
        $totalConversas = $user->conversations()->count();
        $totalMensagens = \App\Models\Message::whereHas(
            'conversation',
            fn($q) =>
            $q->where('user_id', $user->id)
        )->count();

        $totalDemandas      = 0;
        $demandasResolvidas = 0;
        try {
            // Query direta — model Demand pode ainda nao existir
            $totalDemandas      = \DB::table('demands')
                ->where('municipality_id', $municipality->id)
                ->count();
            $demandasResolvidas = \DB::table('demands')
                ->where('municipality_id', $municipality->id)
                ->where('status', 'resolvida')
                ->count();
        } catch (\Exception $e) {
            // Tabela demands pode nao existir ainda
        }

        // Briefings gerados
        $totalBriefings = $municipality->morningBriefings()->count();
        $briefingHoje   = $municipality->morningBriefings()->whereDate('date', today())->first();

        return view('mayor.situacao.index', compact(
            'municipality',
            'commitments',
            'totalGeral',
            'entregues',
            'emAndamento',
            'emRisco',
            'prometidos',
            'cancelados',
            'pctConcluido',
            'porArea',
            'emRiscoItems',
            'prazosProximos',
            'recentesEntregues',
            'totalProgramas',
            'programasAbertos',
            'programasMonitor',
            'valorCaptado',
            'topProgramas',
            'totalConversas',
            'totalMensagens',
            'totalDemandas',
            'demandasResolvidas',
            'totalBriefings',
            'briefingHoje'
        ));
    }
}
