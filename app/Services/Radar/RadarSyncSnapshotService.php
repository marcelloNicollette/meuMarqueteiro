<?php

namespace App\Services\Radar;

use App\Models\ApiSyncLog;
use App\Models\Municipality;
use App\Models\SystemSetting;
use Illuminate\Support\Collection;

class RadarSyncSnapshotService
{
    public function __construct(
        private readonly RadarSyncExportService $exportService,
    ) {}

    public function buildSnapshot(string $period): array
    {
        [$startedAt, $endedAt] = $this->windowForPeriod($period);
        $logs = $this->snapshotQuery($startedAt, $endedAt)->get();

        $summary = [
            'period' => $period,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'total' => $logs->count(),
            'failed' => $logs->where('status', 'failed')->count(),
            'stale' => $logs->filter(fn (ApiSyncLog $log) => (bool) data_get($log->error_details, 'stale_auto_closed', false))->count(),
            'retried' => $logs->filter(fn (ApiSyncLog $log) => data_get($log->error_details, 'retried_to_log_id') || data_get($log->error_details, 'retry_of_log_id'))->count(),
            'running' => $logs->where('status', 'running')->count(),
            'queued' => $logs->where('status', 'queued')->count(),
            'success' => $logs->where('status', 'success')->count(),
        ];

        $historyRows = $logs
            ->map(fn (ApiSyncLog $log) => $this->historyRow($log))
            ->values()
            ->all();
        $municipalitySummary = $this->municipalitySummaryRows($logs);
        $filterRows = $this->filterRows($period, $startedAt, $endedAt);

        $historyHeaders = [
            'ID',
            'Municipio',
            'Status',
            'Modo',
            'Origem Disparo',
            'Fila',
            'Operador',
            'Email Operador',
            'Motivo',
            'Novos',
            'Atualizados',
            'Descartados',
            'Capturados',
            'Salvos',
            'Início',
            'Fim',
            'Duracao (ms)',
            'Autoencerrado',
            'Possivel Travado',
            'Erro',
            'Timeline Consolidada',
        ];

        $summaryHeaders = [
            'Municipio',
            'Execucoes',
            'Sucesso',
            'Falha',
            'Em Execução',
            'Na Fila',
            'Autoencerradas',
            'Reenfileiradas',
            'Ultimo Status',
            'Ultimo Operador',
            'Ultimo Motivo',
            'Ultima Atualizacao',
        ];

        return [
            'period' => $period,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'summary' => $summary,
            'history_rows' => $historyRows,
            'municipality_summary_rows' => $municipalitySummary,
            'attachments' => [
                'history_csv' => [
                    'name' => $this->filename("histórico-sync-radar-{$period}", 'csv'),
                    'mime' => 'text/csv',
                    'content' => $this->exportService->csvContent($filterRows, $historyHeaders, $historyRows),
                ],
                'summary_csv' => [
                    'name' => $this->filename("resumo-sync-radar-{$period}", 'csv'),
                    'mime' => 'text/csv',
                    'content' => $this->exportService->csvContent($filterRows, $summaryHeaders, $municipalitySummary),
                ],
                'history_xlsx' => [
                    'name' => $this->filename("histórico-sync-radar-{$period}", 'xlsx'),
                    'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'content' => $this->exportService->xlsxBinary([
                        ['name' => 'Filtros', 'rows' => array_merge([['Filtro', 'Valor']], $filterRows)],
                        ['name' => 'Histórico', 'rows' => array_merge([$historyHeaders], $historyRows)],
                    ]),
                ],
                'summary_xlsx' => [
                    'name' => $this->filename("resumo-sync-radar-{$period}", 'xlsx'),
                    'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'content' => $this->exportService->xlsxBinary([
                        ['name' => 'Filtros', 'rows' => array_merge([['Filtro', 'Valor']], $filterRows)],
                        ['name' => 'Resumo', 'rows' => array_merge([$summaryHeaders], $municipalitySummary)],
                    ]),
                ],
            ],
        ];
    }

    public function recipientsFromSettings(): array
    {
        $raw = SystemSetting::get('radar_sync_snapshot_recipients', config('radar.sync_snapshot.recipients', []));

        if (is_string($raw)) {
            $raw = array_map('trim', explode(',', $raw));
        }

        $recipients = collect(is_array($raw) ? $raw : [])
            ->map(fn ($email) => trim((string) $email))
            ->filter(fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->values()
            ->all();

        return $recipients;
    }

    public function snapshotEnabled(): bool
    {
        return (bool) SystemSetting::get('radar_sync_snapshot_enabled', config('radar.sync_snapshot.enabled', false));
    }

    public function dailyEnabled(): bool
    {
        return (bool) SystemSetting::get('radar_sync_snapshot_daily_enabled', config('radar.sync_snapshot.daily_enabled', true));
    }

    public function weeklyEnabled(): bool
    {
        return (bool) SystemSetting::get('radar_sync_snapshot_weekly_enabled', config('radar.sync_snapshot.weekly_enabled', true));
    }

    private function windowForPeriod(string $period): array
    {
        $end = now();

        return match ($period) {
            'weekly' => [$end->copy()->subDays(7), $end],
            default => [$end->copy()->subDay(), $end],
        };
    }

    private function snapshotQuery($startedAt, $endedAt)
    {
        return ApiSyncLog::query()
            ->radarFederalPrograms()
            ->with('municipality')
            ->whereBetween('started_at', [$startedAt, $endedAt])
            ->latest('id');
    }

    private function historyRow(ApiSyncLog $log): array
    {
        $result = is_array($log->error_details) ? ($log->error_details['result'] ?? null) : null;
        $events = $this->formattedAuditEvents($log);

        return [
            (string) $log->id,
            $log->municipality?->name ?? 'Municipio removido',
            $this->statusLabel($log->status),
            data_get($log->error_details, 'force', false) ? 'Forcado' : 'Normal',
            (string) data_get($log->error_details, 'queued_via', 'nao_informado'),
            (string) data_get($log->error_details, 'queue_name', 'radar-sync'),
            (string) data_get($log->error_details, 'last_operator.name', data_get($log->error_details, 'triggered_by.name', 'sistema')),
            (string) data_get($log->error_details, 'last_operator.email', data_get($log->error_details, 'triggered_by.email', '')),
            (string) data_get($log->error_details, 'last_operation_reason', data_get($log->error_details, 'operation_reason', '')),
            (string) data_get($result, 'novos', 0),
            (string) data_get($result, 'atualizados', 0),
            (string) data_get($result, 'descartados', 0),
            (string) $log->records_fetched,
            (string) $log->records_saved,
            $log->started_at?->toIso8601String() ?? '',
            $log->finished_at?->toIso8601String() ?? '',
            $log->duration_ms !== null ? (string) $log->duration_ms : '',
            data_get($log->error_details, 'stale_auto_closed', false) ? 'Sim' : 'Nao',
            $this->isStale($log) ? 'Sim' : 'Nao',
            $log->error_message ?: '',
            $this->timelineAsString($events),
        ];
    }

    private function municipalitySummaryRows(Collection $logs): array
    {
        return $logs
            ->groupBy('municipality_id')
            ->map(function (Collection $groupedLogs) {
                $latest = $groupedLogs->sortByDesc('id')->first();

                return [
                    $latest?->municipality?->name ?? 'Municipio removido',
                    (string) $groupedLogs->count(),
                    (string) $groupedLogs->where('status', 'success')->count(),
                    (string) $groupedLogs->where('status', 'failed')->count(),
                    (string) $groupedLogs->where('status', 'running')->count(),
                    (string) $groupedLogs->where('status', 'queued')->count(),
                    (string) $groupedLogs->filter(fn (ApiSyncLog $log) => (bool) data_get($log->error_details, 'stale_auto_closed', false))->count(),
                    (string) $groupedLogs->filter(fn (ApiSyncLog $log) => data_get($log->error_details, 'retried_to_log_id') || data_get($log->error_details, 'retry_of_log_id'))->count(),
                    $latest ? $this->statusLabel((string) $latest->status) : '—',
                    (string) data_get($latest?->error_details, 'last_operator.name', data_get($latest?->error_details, 'triggered_by.name', '')),
                    (string) data_get($latest?->error_details, 'last_operation_reason', data_get($latest?->error_details, 'operation_reason', '')),
                    $latest?->updated_at?->diffForHumans() ?? '',
                ];
            })
            ->sortByDesc(fn (array $row) => (int) $row[1])
            ->values()
            ->all();
    }

    private function filterRows(string $period, $startedAt, $endedAt): array
    {
        return [
            ['Periodo', $period === 'weekly' ? 'Semanal (ultimos 7 dias)' : 'Diario (ultimas 24h)'],
            ['Início da janela', $startedAt->toIso8601String()],
            ['Fim da janela', $endedAt->toIso8601String()],
            ['Gerado em', now()->toIso8601String()],
        ];
    }

    private function filename(string $base, string $extension): string
    {
        return $base . '-' . now()->format('Ymd-His') . '.' . $extension;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'queued' => 'Na fila',
            'running' => 'Em execução',
            'success' => 'Concluido',
            'failed' => 'Falhou',
            default => ucfirst($status),
        };
    }

    private function isStale(ApiSyncLog $log): bool
    {
        if ((bool) data_get($log->error_details, 'stale_auto_closed', false)) {
            return true;
        }

        if ($log->status === 'queued' && $log->started_at?->lt(now()->subMinutes(5))) {
            return true;
        }

        if ($log->status === 'running' && $log->started_at?->lt(now()->subMinutes(15))) {
            return true;
        }

        return false;
    }

    private function formattedAuditEvents(ApiSyncLog $log): array
    {
        $events = data_get($log->error_details, 'audit_timeline', []);

        if (!is_array($events)) {
            return [];
        }

        return collect($events)
            ->filter(fn ($event) => is_array($event))
            ->values()
            ->all();
    }

    private function timelineAsString(array $events): string
    {
        return collect($events)
            ->map(function (array $event) {
                $parts = array_filter([
                    (string) ($event['at'] ?? ''),
                    (string) data_get($event, 'actor.name', 'sistema'),
                    (string) ($event['label'] ?? 'Evento operacional'),
                    data_get($event, 'context.reason') ? 'Motivo: ' . data_get($event, 'context.reason') : null,
                    data_get($event, 'context.stale_reason') ? 'Stale: ' . data_get($event, 'context.stale_reason') : null,
                ]);

                return implode(' | ', $parts);
            })
            ->implode("\n");
    }
}
