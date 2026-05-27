<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\IngestPublicDataJob;
use App\Models\DocumentEmbedding;
use App\Models\Municipality;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Queue;
use Illuminate\Http\Request;

class IntegrationMonitorController extends Controller
{
    private function getApisCatalog(): array
    {
        return [
            'ibge_municípios' => ['nome' => 'IBGE Cidades',        'grupo' => 'Socioeconômico'],
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
            'transferegov'    => ['nome' => 'Portal Transparência (Captação)', 'grupo' => 'Captação'],
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
            'municípios_ativos' => $municipalities->count(),
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
        $job = new IngestPublicDataJob($municipality);
        $connection = $this->resolveAsyncQueueConnection();

        if ($connection) {
            Queue::connection($connection)->push($job);
            return back()->with('success', "Sincronizacao enfileirada para {$municipality->name} na fila {$connection}. Os dados serao indexados em breve.");
        }

        IngestPublicDataJob::dispatch($municipality);
        return back()->with('warning', "A fila padrao esta configurada como sync. O processamento pode bloquear a requisicao enquanto indexa {$municipality->name}.");
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
        $connection = $this->resolveAsyncQueueConnection();

        foreach ($municipalities as $m) {
            $job = new IngestPublicDataJob($m);

            if ($connection) {
                Queue::connection($connection)->push($job);
                continue;
            }

            IngestPublicDataJob::dispatch($m);
        }

        if ($connection) {
            return back()->with('success', "Sincronizacoes enfileiradas para {$municipalities->count()} município(s) na fila {$connection}.");
        }

        return back()->with('warning', "A fila padrao esta configurada como sync. O processamento pode bloquear a requisicao ao sincronizar {$municipalities->count()} município(s).");
    }

    private function resolveAsyncQueueConnection(): ?string
    {
        $default = (string) config('queue.default', 'sync');
        $connections = array_keys((array) config('queue.connections', []));

        if ($default !== 'sync') {
            return $default;
        }

        // Prefer the persisted queue first so ingestion can survive long retries
        // without depending on the request/response lifecycle.
        foreach (['database', 'background', 'deferred'] as $candidate) {
            if (in_array($candidate, $connections, true)) {
                return $candidate;
            }
        }

        return null;
    }
}
