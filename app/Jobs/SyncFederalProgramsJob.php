<?php

namespace App\Jobs;

use App\Models\ApiSyncLog;
use App\Models\Municipality;
use App\Services\FederalPrograms\FederalProgramSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncFederalProgramsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;
    public int $tries = 1;

    public function __construct(
        public readonly int $municipalityId,
        public readonly int $syncLogId,
        public readonly bool $force = false,
    ) {}

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("radar-sync-municipality-{$this->municipalityId}"))
                ->releaseAfter(30)
                ->expireAfter(1800),
        ];
    }

    public function handle(FederalProgramSyncService $service): void
    {
        $syncLog = ApiSyncLog::query()->find($this->syncLogId);
        $municipality = Municipality::query()->find($this->municipalityId);

        if (!$syncLog || !$municipality) {
            if ($syncLog) {
                $syncLog->update([
                    'status' => 'failed',
                    'error_message' => 'Municipio não encontrado para executar o sync.',
                    'finished_at' => now(),
                ]);
                $this->appendAuditEvent(
                    $syncLog->fresh(),
                    'job_failed',
                    'Execução abortada por município ausente.',
                    $this->systemActor(),
                    ['reason' => 'Municipio não encontrado para executar o sync.'],
                );
            }

            return;
        }

        if (!in_array($syncLog->status, ['queued', 'running'], true)) {
            $this->appendAuditEvent(
                $syncLog,
                'job_ignored',
                'Job ignorado porque a execução ja mudou de status.',
                $this->systemActor(),
                ['status' => $syncLog->status],
            );
            Log::warning('SyncFederalProgramsJob ignorado por status invalido.', [
                'municipality_id' => $municipality->id,
                'municipality_name' => $municipality->name,
                'sync_log_id' => $syncLog->id,
                'status' => $syncLog->status,
            ]);

            return;
        }

        $startedAt = now();
        $started = microtime(true);

        $syncLog->update([
            'status' => 'running',
            'started_at' => $syncLog->started_at ?: $startedAt,
            'finished_at' => null,
            'error_message' => null,
            'error_details' => array_merge($syncLog->error_details ?? [], [
                'force' => $this->force,
            ]),
        ]);
        $syncLog = $this->appendAuditEvent(
            $syncLog->fresh(),
            'job_running',
            'Worker iníciou o processamento do sync.',
            $this->systemActor(),
            ['force' => $this->force],
        );

        Log::info('SyncFederalProgramsJob iniciado.', [
            'municipality_id' => $municipality->id,
            'municipality_name' => $municipality->name,
            'sync_log_id' => $syncLog->id,
            'force' => $this->force,
        ]);

        $result = $service->sync(
            municipality: $municipality,
            force: $this->force,
            parentSyncLogId: $syncLog->id,
        );
        $durationMs = (int) round((microtime(true) - $started) * 1000);
        $syncLog = $syncLog->fresh();

        if (!$syncLog || $syncLog->status !== 'running') {
            if ($syncLog) {
                $this->appendAuditEvent(
                    $syncLog,
                    'job_skipped_finalize',
                    'Execução não foi consolidada porque o status mudou durante o processamento.',
                    $this->systemActor(),
                    ['status' => $syncLog->status],
                );
            }
            Log::warning('SyncFederalProgramsJob finalizado sem consolidar por status alterado.', [
                'municipality_id' => $municipality->id,
                'municipality_name' => $municipality->name,
                'sync_log_id' => $this->syncLogId,
            ]);

            return;
        }

        $syncLog->update([
            'status' => 'success',
            'records_fetched' => (int) ($result['total_fetched'] ?? 0),
            'records_saved' => (int) (($result['novos'] ?? 0) + ($result['atualizados'] ?? 0)),
            'duration_ms' => $durationMs,
            'finished_at' => now(),
            'error_message' => null,
            'error_details' => array_merge($syncLog->error_details ?? [], [
                'force' => $this->force,
                'result' => $result,
            ]),
        ]);
        $this->appendAuditEvent(
            $syncLog->fresh(),
            'job_success',
            'Sync concluido com sucesso pelo worker.',
            $this->systemActor(),
            [
                'novos' => (int) ($result['novos'] ?? 0),
                'atualizados' => (int) ($result['atualizados'] ?? 0),
                'descartados' => (int) ($result['descartados'] ?? 0),
                'records_fetched' => (int) ($result['total_fetched'] ?? 0),
                'source_runs' => $result['source_runs'] ?? [],
                'duration_ms' => $durationMs,
            ],
        );

        Log::info('SyncFederalProgramsJob concluido.', [
            'municipality_id' => $municipality->id,
            'municipality_name' => $municipality->name,
            'sync_log_id' => $syncLog->id,
            'result' => $result,
            'duration_ms' => $durationMs,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        $syncLog = ApiSyncLog::query()->find($this->syncLogId);

        if ($syncLog && in_array($syncLog->status, ['queued', 'running'], true)) {
            $syncLog->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => $e->getMessage(),
                'error_details' => array_merge($syncLog->error_details ?? [], [
                    'force' => $this->force,
                    'exception' => $e::class,
                ]),
            ]);
            $this->appendAuditEvent(
                $syncLog->fresh(),
                'job_failed',
                'Worker marcou a execução como falha.',
                $this->systemActor(),
                [
                    'message' => $e->getMessage(),
                    'exception' => $e::class,
                ],
            );
        }

        Log::error('SyncFederalProgramsJob falhou.', [
            'municipality_id' => $this->municipalityId,
            'sync_log_id' => $this->syncLogId,
            'force' => $this->force,
            'message' => $e->getMessage(),
        ]);
    }

    private function appendAuditEvent(
        ApiSyncLog $execution,
        string $event,
        string $label,
        array $actor,
        array $context = [],
    ): ApiSyncLog {
        $details = is_array($execution->error_details) ? $execution->error_details : [];
        $timeline = collect($details['audit_timeline'] ?? [])
            ->push(array_filter([
                'event' => $event,
                'label' => $label,
                'at' => now()->toIso8601String(),
                'actor' => $actor,
                'context' => $context ?: null,
            ], fn ($value) => $value !== null))
            ->values()
            ->all();

        $execution->update([
            'error_details' => array_merge($details, [
                'audit_timeline' => $timeline,
            ]),
        ]);

        return $execution->fresh();
    }

    private function systemActor(): array
    {
        return [
            'id' => null,
            'name' => 'queue-worker',
            'email' => null,
            'role' => 'system',
        ];
    }
}
