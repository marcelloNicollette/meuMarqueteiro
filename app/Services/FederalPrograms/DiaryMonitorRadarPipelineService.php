<?php

namespace App\Services\FederalPrograms;

use App\Models\Municipality;
use App\Models\ResourceSource;
use Illuminate\Support\Facades\Log;

class DiaryMonitorRadarPipelineService
{
    public function __construct(
        private readonly DiaryMonitorRadarFetcher $fetcher,
    ) {}

    public function collect(Municipality $municipality, ?callable $logger = null): array
    {
        $rawItems = [];
        $sourceRuns = [];

        $sources = ResourceSource::query()
            ->where('pipeline_group', 'group_c_diary_monitor')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        foreach ($sources as $source) {
            $started = microtime(true);

            try {
                $fetchResult = $this->fetcher->fetchWithMetrics($source, $municipality);
                $items = (array) ($fetchResult['items'] ?? []);
                $metrics = (array) ($fetchResult['metrics'] ?? []);
                $durationMs = (int) round((microtime(true) - $started) * 1000);
                $rawItems = array_merge($rawItems, $items);
                $message = $this->successMessage($items, $metrics);

                $sourceRuns[] = [
                    'source_key' => $source->key,
                    'source_name' => $source->name,
                    'pipeline_group' => 'group_c_diary_monitor',
                    'status' => 'success',
                    'records_fetched' => count($items),
                    'duration_ms' => $durationMs,
                    'message' => $message,
                    'debug' => (array) ($metrics['debug'] ?? []),
                ];

                if ($logger) {
                    $logger("{$source->name}: {$message}");
                }
            } catch (\Throwable $e) {
                $durationMs = (int) round((microtime(true) - $started) * 1000);

                Log::warning('Falha ao coletar fonte do Grupo C do Radar.', [
                    'source_key' => $source->key,
                    'source_name' => $source->name,
                    'municipality_id' => $municipality->id,
                    'municipality_name' => $municipality->name,
                    'message' => $e->getMessage(),
                ]);

                $sourceRuns[] = [
                    'source_key' => $source->key,
                    'source_name' => $source->name,
                    'pipeline_group' => 'group_c_diary_monitor',
                    'status' => 'failed',
                    'records_fetched' => 0,
                    'duration_ms' => $durationMs,
                    'message' => $e->getMessage(),
                ];

                if ($logger) {
                    $logger("{$source->name}: indisponível ({$e->getMessage()}).");
                }
            }
        }

        return [
            'items' => $rawItems,
            'source_runs' => $sourceRuns,
            'total_fetched' => count($rawItems),
            'successful_sources' => collect($sourceRuns)->where('status', 'success')->count(),
            'failed_sources' => collect($sourceRuns)->where('status', 'failed')->count(),
        ];
    }

    private function successMessage(array $items, array $metrics): string
    {
        $selected = count($items);
        $entrypointsVisited = (int) ($metrics['entrypoints_visited'] ?? 0);
        $entrypointsTotal = (int) ($metrics['entrypoints_total'] ?? 0);
        $rawCandidates = (int) ($metrics['raw_candidates'] ?? 0);
        $filteredCandidates = (int) ($metrics['filtered_candidates'] ?? 0);
        $qualifiedCandidates = (int) ($metrics['qualified_candidates'] ?? 0);

        if ($entrypointsTotal === 0) {
            return 'Nenhum ponto de monitoramento configurado para diario oficial.';
        }

        if ($selected <= 0) {
            return "Nenhuma publicacao relevante identificada. Entry points {$entrypointsVisited}/{$entrypointsTotal}; brutos {$rawCandidates}; filtrados {$filteredCandidates}; qualificados {$qualifiedCandidates}.";
        }

        return "{$selected} publicacao(oes) relevante(s) identificada(s) via diario oficial. Entry points {$entrypointsVisited}/{$entrypointsTotal}; brutos {$rawCandidates}; filtrados {$filteredCandidates}; qualificados {$qualifiedCandidates}.";
    }
}
