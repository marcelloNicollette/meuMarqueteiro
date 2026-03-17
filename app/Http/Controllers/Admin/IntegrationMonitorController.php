<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\IngestPublicDataJob;
use App\Models\DocumentEmbedding;
use App\Models\Municipality;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class IntegrationMonitorController extends Controller
{
    private function getApisCatalog(): array
    {
        return [
            'ibge_municipios' => ['nome' => 'IBGE Cidades',        'grupo' => 'Socioeconômico'],
            'ibge_populacao'  => ['nome' => 'IBGE Estimativas',    'grupo' => 'Socioeconômico'],
            'atlas_brasil'    => ['nome' => 'Atlas Brasil (PNUD)', 'grupo' => 'Socioeconômico'],
            'ipea_data'       => ['nome' => 'IPEA Data',           'grupo' => 'Socioeconômico'],
            'siconfi'         => ['nome' => 'SICONFI (STN)',        'grupo' => 'Fiscal'],
            'finbra'          => ['nome' => 'FINBRA (STN)',         'grupo' => 'Fiscal'],
            'transparencia'   => ['nome' => 'Portal Transparência', 'grupo' => 'Fiscal'],
            'datasus'         => ['nome' => 'DATASUS',             'grupo' => 'Saúde'],
            'fns'             => ['nome' => 'FNS',                 'grupo' => 'Saúde'],
            'fnde'            => ['nome' => 'FNDE',                'grupo' => 'Educação'],
            'inep_censo'      => ['nome' => 'INEP Censo Escolar',  'grupo' => 'Educação'],
            'inep_ideb'       => ['nome' => 'INEP IDEB',           'grupo' => 'Educação'],
            'snis'            => ['nome' => 'SNIS Saneamento',     'grupo' => 'Infraestrutura'],
            'aneel'           => ['nome' => 'ANEEL / SIGEL',       'grupo' => 'Infraestrutura'],
            'transferegov'    => ['nome' => 'Transferegov',        'grupo' => 'Captação'],
            'bndes'           => ['nome' => 'BNDES',               'grupo' => 'Captação'],
        ];
    }

    public function index()
    {
        $municipalities = Municipality::where('subscription_active', true)
            ->withCount(['documents'])
            ->orderBy('name')
            ->get();

        $embeddingCounts = DocumentEmbedding::whereNotNull('municipality_id')
            ->selectRaw('municipality_id, count(*) as total')
            ->groupBy('municipality_id')
            ->pluck('total', 'municipality_id');

        $catalog    = $this->getApisCatalog();
        $apisAtivas = [];
        foreach ($catalog as $key => $api) {
            if (SystemSetting::get("integration_{$key}_ativo", false)) {
                $apisAtivas[$key] = $api;
            }
        }

        $stats = [
            'municipios_ativos' => $municipalities->count(),
            'apis_ativas'       => count($apisAtivas),
            'apis_total'        => count($catalog),
            'ultima_sync'       => Municipality::whereNotNull('data_last_synced_at')->max('data_last_synced_at'),
            'total_embeddings'  => DocumentEmbedding::count(),
        ];

        return view('admin.integrations.index', compact(
            'municipalities',
            'apisAtivas',
            'catalog',
            'stats',
            'embeddingCounts'
        ));
    }

    public function sync(Request $request, Municipality $municipality)
    {
        IngestPublicDataJob::dispatch($municipality);
        return back()->with('success', "Job enfileirado para {$municipality->name}. Os dados serão indexados em breve.");
    }

    public function syncNow(Municipality $municipality)
    {
        try {
            $orchestrator = app(\App\Services\DataIngestion\DataIngestionOrchestrator::class);
            $report       = $orchestrator->ingest($municipality);
            $indexados    = $report['total_indexados'] ?? 0;
            $erros        = $report['erros'] ?? [];

            if ($indexados > 0) {
                $msg = "Sincronização concluída: {$indexados} chunks indexados para {$municipality->name}.";
                return back()->with('success', $msg);
            }

            // Nenhum chunk — mostrar erros detalhados
            $detalhes = collect($erros)->map(fn($e, $k) => "{$k}: {$e}")->implode(' | ');
            $status   = $report['status'] ?? '—';

            if ($status === 'nenhuma_api_ativa') {
                return back()->with('error', "Nenhuma API está ativa para {$municipality->name}. Ative em Configurações → APIs Externas.");
            }

            return back()->with('error', "0 chunks indexados para {$municipality->name}. Erros: {$detalhes}");
        } catch (\Exception $e) {
            return back()->with('error', 'Erro: ' . $e->getMessage());
        }
    }

    public function syncAll(Request $request)
    {
        $municipalities = Municipality::where('subscription_active', true)->get();
        foreach ($municipalities as $m) {
            IngestPublicDataJob::dispatch($m);
        }
        return back()->with('success', "Jobs enfileirados para {$municipalities->count()} município(s).");
    }
}
