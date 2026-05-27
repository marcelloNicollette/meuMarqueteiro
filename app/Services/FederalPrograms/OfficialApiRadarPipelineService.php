<?php

namespace App\Services\FederalPrograms;

use App\Models\Municipality;
use App\Services\FederalPrograms\Contracts\OfficialApiRadarSource;
use Illuminate\Support\Facades\Log;

class OfficialApiRadarPipelineService
{
    /**
     * @param  iterable<int, OfficialApiRadarSource>  $sources
     */
    public function __construct(
        private readonly iterable $sources,
    ) {}

    public function collect(Municipality $municipality, ?callable $logger = null): array
    {
        $rawItems = [];
        $sourceRuns = [];

        foreach ($this->sources as $source) {
            $started = microtime(true);

            try {
                $items = $source->fetch($municipality);
                $durationMs = (int) round((microtime(true) - $started) * 1000);
                $rawItems = array_merge($rawItems, $items);

                $sourceRuns[] = [
                    'source_key' => $source->sourceKey(),
                    'source_name' => $source->sourceName(),
                    'pipeline_group' => 'group_a_api',
                    'status' => 'success',
                    'records_fetched' => count($items),
                    'duration_ms' => $durationMs,
                    'message' => count($items) > 0
                        ? count($items) . ' registro(s) coletado(s).'
                        : 'Nenhum registro retornado pela fonte.',
                ];

                if ($logger) {
                    $logger("{$source->sourceName()}: " . count($items) . " registro(s) coletado(s).");
                }
            } catch (\Throwable $e) {
                $durationMs = (int) round((microtime(true) - $started) * 1000);

                Log::warning('Falha ao coletar fonte oficial do Radar.', [
                    'source_key' => $source->sourceKey(),
                    'source_name' => $source->sourceName(),
                    'municipality_id' => $municipality->id,
                    'municipality_name' => $municipality->name,
                    'message' => $e->getMessage(),
                ]);

                $sourceRuns[] = [
                    'source_key' => $source->sourceKey(),
                    'source_name' => $source->sourceName(),
                    'pipeline_group' => 'group_a_api',
                    'status' => 'failed',
                    'records_fetched' => 0,
                    'duration_ms' => $durationMs,
                    'message' => $e->getMessage(),
                ];

                if ($logger) {
                    $logger("{$source->sourceName()}: indisponível ({$e->getMessage()}).");
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
}
