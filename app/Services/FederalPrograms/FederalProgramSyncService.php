<?php

namespace App\Services\FederalPrograms;

use App\Enums\ResourceOpportunityStatus;
use App\Models\ApiSyncLog;
use App\Models\FederalProgramAlert;
use App\Models\Municipality;
use App\Models\ResourceSource;
use App\Services\Radar\CanonicalResourceSyncService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Console\Output\OutputInterface;

class FederalProgramSyncService
{
    private ?array $resourceSourceCatalog = null;

    // ── Mapeamento de áreas ──────────────────────────────────────────────
    private const AREA_MAP = [
        'saude'          => ['saude', 'saúde', 'sus', 'ubs', 'atenção básica', 'hospitalar', 'vigilância sanitária'],
        'educacao'       => ['educação', 'educacao', 'escola', 'ensino', 'fnde', 'creche', 'alfabetização'],
        'infraestrutura' => ['infraestrutura', 'pavimentação', 'pavimentacao', 'estrada', 'ponte', 'iluminação', 'mobilidade'],
        'saneamento'     => ['saneamento', 'esgoto', 'água', 'agua', 'abastecimento', 'resíduos', 'residuos'],
        'habitacao'      => ['habitação', 'habitacao', 'moradia', 'casa', 'reassentamento'],
        'social'         => ['social', 'assistência', 'assistencia', 'cras', 'creas', 'vulnerabilidade', 'criança', 'idoso'],
        'meio_ambiente'  => ['ambiental', 'meio ambiente', 'floresta', 'clima', 'resíduos sólidos'],
        'economia'       => ['desenvolvimento econômico', 'emprego', 'turismo', 'agropecuária', 'bndes'],
    ];

    // ── Constructor ─────────────────────────────────────────────────────
    public function __construct(
        private OfficialApiRadarPipelineService $officialApiPipeline,
        private StructuredScrapingRadarPipelineService $structuredScrapingPipeline,
        private DiaryMonitorRadarPipelineService $diaryMonitorPipeline,
        private ClaudeMatchingService $claude,
        private CanonicalResourceSyncService $canonicalSync,
    ) {}

    // ── Ponto de entrada principal ───────────────────────────────────────
    public function sync(
        Municipality    $municipality,
        bool            $force   = false,
        bool            $dryRun  = false,
        ?OutputInterface $output = null,
        ?int            $parentSyncLogId = null,
    ): array {
        $result = [
            'novos' => 0,
            'atualizados' => 0,
            'descartados' => 0,
            'total_fetched' => 0,
            'portal_transparencia' => 0,
            'transferegov' => 0,
            'source_runs' => [],
        ];

        $log = fn(string $msg) => $output?->writeln("    <fg=yellow>…</> {$msg}");
        $raw = [];

        $log('Coletando fontes oficiais do Grupo A...');
        $officialPipelineResult = $this->officialApiPipeline->collect($municipality, $log);
        $raw = array_merge($raw, $officialPipelineResult['items'] ?? []);
        $this->mergePipelineResult($result, $officialPipelineResult);

        $log('Coletando scraping estruturado do Grupo B...');
        $scrapingPipelineResult = $this->structuredScrapingPipeline->collect($municipality, $log);
        $raw = array_merge($raw, $scrapingPipelineResult['items'] ?? []);
        $this->mergePipelineResult($result, $scrapingPipelineResult);

        $log('Coletando monitoramento de diarios oficiais do Grupo C...');
        $diaryPipelineResult = $this->diaryMonitorPipeline->collect($municipality, $log);
        $raw = array_merge($raw, $diaryPipelineResult['items'] ?? []);
        $this->mergePipelineResult($result, $diaryPipelineResult);

        if (!$dryRun) {
            $this->writeSourceRunLogs(
                municipality: $municipality,
                sourceRuns: $result['source_runs'],
                parentSyncLogId: $parentSyncLogId,
            );
        }

        if (empty($raw)) {
            Log::info("sync-federal [{$municipality->id}]: sem programas brutos para processar.");
            return $result;
        }

        $log("Total bruto consolidado: {$result['total_fetched']} registros. Enviando ao Claude para análise...");

        // 2. Claude avalia elegibilidade em lote
        $evaluated = $this->claude->evaluateBatch($municipality, $raw);

        // 3. Salvar / atualizar
        foreach ($evaluated as $item) {
            $item = $this->normalizeOpportunityPayload($item);

            if (($item['match_score'] ?? 0) < 0.30 || $item['status'] === ResourceOpportunityStatus::Rejected->value) {
                $result['descartados']++;
                continue;
            }

            if ($dryRun) {
                $output?->writeln("    [DRY-RUN] {$item['program_name']} — score: {$item['match_score']}");
                $result['novos']++;
                continue;
            }

            $existing = FederalProgramAlert::where('municipality_id', $municipality->id)
                ->where('program_code', $item['program_code'])
                ->first();

            if ($existing) {
                if ($force || $existing->status !== 'closed') {
                    $existing->update($item);
                    $this->syncCanonicalFromLegacy($existing, $dryRun);
                    $result['atualizados']++;
                }
            } else {
                $created = FederalProgramAlert::create(array_merge($item, [
                    'municipality_id' => $municipality->id,
                    'ai_matched'      => true,
                ]));
                $this->syncCanonicalFromLegacy($created, $dryRun);
                $result['novos']++;
            }
        }

        // Atualizar timestamp do município
        if (!$dryRun) {
            $municipality->update(['data_last_synced_at' => now()]);
        }

        // Registrar no sync_log se a tabela existir
        $this->writeSyncLog($municipality, $result, $dryRun);

        return $result;
    }

    // ── Inferir área a partir do texto ───────────────────────────────────
    public static function inferArea(string $text): string
    {
        $lower = mb_strtolower($text);
        foreach (self::AREA_MAP as $area => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($lower, $kw)) return $area;
            }
        }
        return 'outros';
    }

    private function writeSyncLog(Municipality $municipality, array $result, bool $dryRun): void
    {
        try {
            if (\Schema::hasTable('sync_logs')) {
                \DB::table('sync_logs')->insert([
                    'municipality_id' => $municipality->id,
                    'type'            => 'federal_programs',
                    'status'          => 'success',
                    'details'         => json_encode(array_merge($result, ['dry_run' => $dryRun])),
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
        } catch (\Exception $e) {
            // Tabela pode não  existir ainda — não  é crítico
        }
    }

    private function writeSourceRunLogs(
        Municipality $municipality,
        array $sourceRuns,
        ?int $parentSyncLogId = null,
    ): void {
        foreach ($sourceRuns as $sourceRun) {
            $durationMs = (int) ($sourceRun['duration_ms'] ?? 0);
            $finishedAt = now();

            ApiSyncLog::query()->create([
                'municipality_id' => $municipality->id,
                'source' => (string) ($sourceRun['source_key'] ?? 'unknown_source'),
                'data_type' => 'federal_programs_radar_source',
                'status' => (string) ($sourceRun['status'] ?? 'success'),
                'records_fetched' => (int) ($sourceRun['records_fetched'] ?? 0),
                'records_saved' => 0,
                'error_message' => ($sourceRun['status'] ?? 'success') === 'failed'
                    ? (string) ($sourceRun['message'] ?? 'Falha sem mensagem adicional.')
                    : null,
                'error_details' => [
                    'pipeline_group' => (string) ($sourceRun['pipeline_group'] ?? 'group_a_api'),
                    'parent_sync_log_id' => $parentSyncLogId,
                    'source_name' => (string) ($sourceRun['source_name'] ?? ''),
                    'message' => (string) ($sourceRun['message'] ?? ''),
                    'debug' => is_array($sourceRun['debug'] ?? null) ? $sourceRun['debug'] : null,
                ],
                'duration_ms' => $durationMs,
                'started_at' => $finishedAt->copy()->subMilliseconds(max($durationMs, 0)),
                'finished_at' => $finishedAt,
            ]);
        }
    }

    private function mergePipelineResult(array &$result, array $pipelineResult): void
    {
        $result['total_fetched'] += (int) ($pipelineResult['total_fetched'] ?? 0);
        $result['source_runs'] = array_merge($result['source_runs'] ?? [], $pipelineResult['source_runs'] ?? []);

        foreach (($pipelineResult['source_runs'] ?? []) as $sourceRun) {
            $sourceKey = (string) ($sourceRun['source_key'] ?? '');

            if ($sourceKey !== '') {
                $result[$sourceKey] = (int) ($sourceRun['records_fetched'] ?? 0);
            }
        }
    }

    private function syncCanonicalFromLegacy(FederalProgramAlert $alert, bool $dryRun): void
    {
        if ($dryRun || !$this->canonicalSync->isEnabled()) {
            return;
        }

        try {
            $this->canonicalSync->syncFromAlert($alert->fresh());
        } catch (\Throwable $e) {
            Log::warning('Falha ao espelhar oportunidade canônica do radar.', [
                'legacy_alert_id' => $alert->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function normalizeOpportunityPayload(array $item): array
    {
        $deadline = filled($item['deadline'] ?? null)
            ? Carbon::parse($item['deadline'])
            : null;

        $status = ResourceOpportunityStatus::normalize(
            $item['status'] ?? null,
            $deadline,
        );

        $maxValue = (float) ($item['max_value'] ?? 0);
        $estimatedSize = null;

        if ($maxValue > 0) {
            $estimatedSize = $maxValue <= 500000
                ? 'small'
                : ($maxValue <= 3000000 ? 'medium' : 'large');
        }

        $sourcePlatform = $this->normalizeSourceAlias($item['source_platform'] ?? null);
        $sourceKey = $this->resolveSourceKey($item, $sourcePlatform);
        $resourceSource = $this->resourceSourceFor($sourceKey);

        return array_merge($item, [
            'status' => $status,
            'source_platform' => $sourcePlatform,
            'source_key' => $sourceKey,
            'resource_source_id' => $item['resource_source_id'] ?? $resourceSource['id'] ?? null,
            'capture_method' => $item['capture_method'] ?? $resourceSource['capture_method'] ?? 'api_official',
            'resource_scope' => $item['resource_scope'] ?? $resourceSource['resource_scope'] ?? 'federal',
            'curation_status' => $item['curation_status'] ?? 'auto_published',
            'published_at' => $item['published_at'] ?? now(),
            'closed_at' => in_array($status, [
                ResourceOpportunityStatus::ClosedRecently->value,
                ResourceOpportunityStatus::Archived->value,
            ], true) ? ($item['closed_at'] ?? $deadline ?? now()) : ($item['closed_at'] ?? null),
            'closed_visibility_until' => in_array($status, [
                ResourceOpportunityStatus::ClosedRecently->value,
                ResourceOpportunityStatus::Archived->value,
            ], true) ? ($item['closed_visibility_until'] ?? ($deadline?->copy()->addDays(60) ?? now()->addDays(60))) : ($item['closed_visibility_until'] ?? null),
            'estimated_size' => $item['estimated_size'] ?? $estimatedSize,
            'compatibility_factors' => $item['compatibility_factors'] ?? [],
            'viability_factors' => $item['viability_factors'] ?? [],
            'source_metadata' => $item['source_metadata'] ?? [],
        ]);
    }

    private function resolveSourceKey(array $item, string $sourcePlatform): string
    {
        if (filled($item['source_key'] ?? null)) {
            return $this->normalizeSourceAlias($item['source_key']);
        }

        $source = $this->normalizeSourceAlias($item['source'] ?? null, $sourcePlatform);

        return match ($source) {
            'emendas_parlamentares',
            'transparencia_emenda' => 'emendas_parlamentares',
            'transparencia_convenio' => 'portal_transparencia',
            default => $sourcePlatform,
        };
    }

    private function normalizeSourceAlias(mixed $value, string $fallback = 'portal_transparencia'): string
    {
        $normalized = Str::of((string) ($value ?? ''))
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->value();

        return match ($normalized) {
            '', 'portal', 'transparencia', 'portal_da_transparencia', 'portal_transparencia_federal' => 'portal_transparencia',
            'transparencia_emenda', 'emenda_parlamentar', 'emendas', 'emendas_parlamentar' => 'emendas_parlamentares',
            'transparencia_convenio' => 'transparencia_convenio',
            'diario_oficial', 'dou' => 'diario_oficial_uniao',
            default => $normalized ?: $fallback,
        };
    }

    private function resourceSourceFor(string $sourceKey): ?array
    {
        $catalog = $this->resourceSourceCatalog();

        return $catalog[$sourceKey] ?? null;
    }

    private function resourceSourceCatalog(): array
    {
        if ($this->resourceSourceCatalog !== null) {
            return $this->resourceSourceCatalog;
        }

        $this->resourceSourceCatalog = ResourceSource::query()
            ->get(['id', 'key', 'resource_scope', 'capture_method'])
            ->mapWithKeys(fn (ResourceSource $source) => [
                $source->key => [
                    'id' => $source->id,
                    'resource_scope' => $source->resource_scope,
                    'capture_method' => $source->capture_method,
                ],
            ])
            ->all();

        return $this->resourceSourceCatalog;
    }
}
