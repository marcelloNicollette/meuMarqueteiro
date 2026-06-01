<?php

namespace App\Services\Support;

use App\Models\Municipality;
use App\Models\MunicipalityCoverageAlert;
use App\Models\MunicipalityCoverageSnapshot;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class MunicipalityCoverageExecutiveService
{
    private ?Collection $municipalityMetricsCache = null;

    private ?Collection $rankingWithTrendCache = null;

    public function __construct(
        private readonly MunicipalityConfigurationStatusService $configurationStatus,
    ) {}

    public function slaTargetsHours(): array
    {
        return [
            'mentions_coverage_lost' => 6,
            'pra_hoje_coverage_lost' => 4,
            'configuration_coverage_lost' => 24,
            'executive_ranking_worsened' => 12,
        ];
    }

    public function governanceThresholdDefaults(): array
    {
        return [
            'enabled' => true,
            'minimum_configuration_score' => 70,
            'minimum_executive_score' => 65,
            'maximum_negative_score_delta' => 8,
            'maximum_position_loss' => 3,
            'maximum_active_alerts' => 3,
            'maximum_sla_breaches' => 1,
        ];
    }

    public function slaByType(): Collection
    {
        $eventTypeOptions = $this->eventTypeOptions();
        $targets = $this->slaTargetsHours();

        return MunicipalityCoverageAlert::query()
            ->selectRaw('event_type, COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_total")
            ->selectRaw("AVG(CASE WHEN resolved_at IS NOT NULL AND first_detected_at IS NOT NULL THEN EXTRACT(EPOCH FROM (resolved_at - first_detected_at)) / 3600 END) as avg_resolution_hours")
            ->selectRaw("AVG(CASE WHEN resolved_at IS NOT NULL AND first_detected_at IS NOT NULL THEN EXTRACT(EPOCH FROM (resolved_at - first_detected_at)) / 60 END) as avg_resolution_minutes")
            ->groupBy('event_type')
            ->get()
            ->map(function ($row) use ($eventTypeOptions, $targets) {
                $eventType = (string) $row->event_type;
                $targetHours = $targets[$eventType] ?? 24;
                $avgHours = $row->avg_resolution_hours !== null ? (float) $row->avg_resolution_hours : null;
                $avgMinutes = $row->avg_resolution_minutes !== null ? (float) $row->avg_resolution_minutes : null;

                return [
                    'event_type' => $eventType,
                    'label' => $eventTypeOptions[$eventType] ?? $eventType,
                    'total' => (int) $row->total,
                    'resolved_total' => (int) $row->resolved_total,
                    'avg_resolution_hours' => $avgHours,
                    'avg_resolution_minutes' => $avgMinutes,
                    'target_hours' => $targetHours,
                    'sla_status' => $avgHours === null ? 'neutral' : ($avgHours <= $targetHours ? 'ok' : 'warning'),
                ];
            })
            ->values();
    }

    public function municipalityMetrics(): Collection
    {
        if ($this->municipalityMetricsCache instanceof Collection) {
            return $this->municipalityMetricsCache;
        }

        $municipalities = Municipality::query()
            ->where('subscription_active', true)
            ->with('mayor')
            ->orderBy('name')
            ->get();

        $summaries = $this->configurationStatus->summarizeCollection($municipalities)->keyBy('municipality_id');
        $alerts = MunicipalityCoverageAlert::query()
            ->whereIn('municipality_id', $municipalities->pluck('id'))
            ->with('municipality')
            ->get();

        $slaBreaches = $this->decorateSlaBreaches(
            $alerts->where('status', 'active')->values()
        )->groupBy(fn (array $entry) => $entry['alert']->municipality_id);

        $metrics = $municipalities->map(function (Municipality $municipality) use ($summaries, $alerts, $slaBreaches) {
            $summary = (array) ($summaries->get($municipality->id) ?? []);
            $municipalityAlerts = $alerts->where('municipality_id', $municipality->id)->values();
            $activeAlerts = $municipalityAlerts->where('status', 'active');
            $highActiveAlerts = $activeAlerts->where('severity', 'high')->count();
            $last30dTotal = $municipalityAlerts
                ->filter(fn (MunicipalityCoverageAlert $alert) => $this->startedAt($alert)?->gte(now()->subDays(30)) ?? false)
                ->count();
            $breaches = collect($slaBreaches->get($municipality->id, []));
            $slaBreachesTotal = $breaches->count();
            $score = (int) ($summary['score'] ?? 0);
            $recurrencePenalty = min(25, $last30dTotal * 4);
            $slaPenalty = min(35, $slaBreachesTotal * 15);
            $activePenalty = min(20, $highActiveAlerts * 7 + max(0, $activeAlerts->count() - $highActiveAlerts) * 3);
            $executiveScore = max(0, min(100, $score - $recurrencePenalty - $slaPenalty - $activePenalty));
            $riskScore = max(0, min(100, (100 - $score) + $recurrencePenalty + $slaPenalty + $activePenalty));

            return [
                'municipality_id' => $municipality->id,
                'municipality_name' => $municipality->name,
                'municipality' => $municipality,
                'score' => $score,
                'status' => $summary['status'] ?? 'critical',
                'summary_label' => $summary['summary_label'] ?? 'Sem leitura',
                'active_alerts_total' => $activeAlerts->count(),
                'resolved_alerts_total' => $municipalityAlerts->where('status', 'resolved')->count(),
                'alerts_total' => $municipalityAlerts->count(),
                'active_high_alerts_total' => $highActiveAlerts,
                'recurrence_30d' => $last30dTotal,
                'sla_breaches_total' => $slaBreachesTotal,
                'sla_breach_max_overdue_hours' => (float) round($breaches->max('hours_overdue') ?? 0, 1),
                'monitoring_keywords_total' => (int) ($summary['monitoring_keywords_total'] ?? 0),
                'active_channels_total' => count((array) ($summary['active_channels'] ?? [])),
                'pra_hoje_time' => $summary['pra_hoje_time'] ?? null,
                'issues' => (array) ($summary['issues'] ?? []),
                'executive_score' => $executiveScore,
                'risk_score' => $riskScore,
                'last_detected_at' => optional($municipalityAlerts->max('last_detected_at')),
            ];
        })->values();

        $this->municipalityMetricsCache = $metrics;

        return $metrics;
    }

    public function coverageComparison(int $limit = 5): array
    {
        $metrics = $this->municipalityMetrics();

        return [
            'leaders' => $metrics
                ->sortByDesc('executive_score')
                ->sortBy('sla_breaches_total')
                ->take($limit)
                ->values(),
            'attention' => $metrics
                ->sortByDesc('risk_score')
                ->take($limit)
                ->values(),
        ];
    }

    public function executiveRanking(int $limit = 10): Collection
    {
        return $this->rankedMunicipalityMetrics()
            ->take($limit)
            ->values();
    }

    public function executiveRankingWithTrend(int $limit = 10): Collection
    {
        return $this->rankingWithTrend()
            ->take($limit)
            ->values();
    }

    public function municipalityMetricWithTrend(int $municipalityId): ?array
    {
        $row = $this->rankingWithTrend()->firstWhere('municipality_id', $municipalityId);

        return is_array($row) ? $row : null;
    }

    public function currentSlaBreaches(): Collection
    {
        $alerts = MunicipalityCoverageAlert::query()
            ->with('municipality')
            ->where('status', 'active')
            ->latest('last_detected_at')
            ->get();

        return $this->decorateSlaBreaches($alerts);
    }

    public function recentSnapshots(int $limit = 6): Collection
    {
        if (!Schema::hasTable('municipality_coverage_snapshots')) {
            return collect();
        }

        return MunicipalityCoverageSnapshot::query()
            ->latest('captured_at')
            ->limit($limit)
            ->get();
    }

    public function temporalSnapshotComparison(int $limit = 6): array
    {
        $snapshots = $this->recentSnapshots($limit)->sortBy('captured_at')->values();
        $currentSummary = $this->currentSummary();
        $latest = $snapshots->last();
        $previous = $snapshots->count() > 1 ? $snapshots->slice(-2, 1)->first() : null;

        return [
            'current' => $currentSummary,
            'latest_snapshot' => $latest,
            'previous_snapshot' => $previous,
            'deltas' => [
                'active_alerts' => $currentSummary['active_alerts'] - (int) data_get($latest, 'summary.active_alerts', $currentSummary['active_alerts']),
                'sla_breaches_total' => $currentSummary['sla_breaches_total'] - (int) data_get($latest, 'summary.sla_breaches_total', $currentSummary['sla_breaches_total']),
                'average_configuration_score' => $currentSummary['average_configuration_score'] - (int) data_get($latest, 'summary.average_configuration_score', $currentSummary['average_configuration_score']),
                'average_executive_score' => $currentSummary['average_executive_score'] - (int) data_get($latest, 'summary.average_executive_score', $currentSummary['average_executive_score']),
            ],
            'series' => $snapshots->map(function (MunicipalityCoverageSnapshot $snapshot) {
                return [
                    'label' => $snapshot->captured_at?->format('d/m') ?? '—',
                    'captured_at' => $snapshot->captured_at?->toIso8601String(),
                    'average_executive_score' => (int) data_get($snapshot->summary, 'average_executive_score', 0),
                    'average_configuration_score' => (int) data_get($snapshot->summary, 'average_configuration_score', 0),
                    'active_alerts' => (int) data_get($snapshot->summary, 'active_alerts', 0),
                    'sla_breaches_total' => (int) data_get($snapshot->summary, 'sla_breaches_total', 0),
                ];
            })->values(),
        ];
    }

    public function municipalityImprovementCurve(int $limit = 8): Collection
    {
        if (!Schema::hasTable('municipality_coverage_snapshots')) {
            return collect();
        }

        $snapshots = MunicipalityCoverageSnapshot::query()
            ->latest('captured_at')
            ->limit(8)
            ->get()
            ->sortBy('captured_at')
            ->values();

        if ($snapshots->isEmpty()) {
            return collect();
        }

        $series = [];
        foreach ($snapshots as $snapshot) {
            foreach ((array) $snapshot->ranking as $row) {
                $municipalityId = (int) ($row['municipality_id'] ?? 0);
                if ($municipalityId <= 0) {
                    continue;
                }

                $series[$municipalityId]['municipality_id'] = $municipalityId;
                $series[$municipalityId]['municipality_name'] = (string) ($row['municipality_name'] ?? 'Municipio');
                $series[$municipalityId]['points'][] = [
                    'label' => $snapshot->captured_at?->format('d/m') ?? '—',
                    'score' => (int) ($row['executive_score'] ?? 0),
                ];
            }
        }

        return collect($series)
            ->map(function (array $entry) {
                $points = collect($entry['points'] ?? []);
                $first = (int) data_get($points->first(), 'score', 0);
                $last = (int) data_get($points->last(), 'score', 0);
                $delta = $last - $first;

                return [
                    'municipality_id' => $entry['municipality_id'],
                    'municipality_name' => $entry['municipality_name'],
                    'points' => $points->values(),
                    'first_score' => $first,
                    'last_score' => $last,
                    'delta' => $delta,
                    'trend_direction' => $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'stable'),
                ];
            })
            ->sortByDesc(fn (array $entry) => abs($entry['delta']))
            ->take($limit)
            ->values();
    }

    public function captureSnapshot(string $period = 'daily'): ?MunicipalityCoverageSnapshot
    {
        if (!Schema::hasTable('municipality_coverage_snapshots')) {
            return null;
        }

        $summary = $this->currentSummary();
        $comparison = $this->coverageComparison(5);
        $ranking = $this->executiveRanking(10)
            ->map(fn (array $row) => [
                'position' => $row['position'],
                'municipality_id' => $row['municipality_id'],
                'municipality_name' => $row['municipality_name'],
                'executive_score' => $row['executive_score'],
                'score' => $row['score'],
                'recurrence_30d' => $row['recurrence_30d'],
                'sla_breaches_total' => $row['sla_breaches_total'],
                'active_alerts_total' => $row['active_alerts_total'],
            ])
            ->values()
            ->all();

        return MunicipalityCoverageSnapshot::query()->create([
            'period' => $period,
            'captured_at' => now(),
            'summary' => $summary,
            'comparison' => [
                'leaders' => collect($comparison['leaders'])->map(fn (array $row) => $this->snapshotRow($row))->values()->all(),
                'attention' => collect($comparison['attention'])->map(fn (array $row) => $this->snapshotRow($row))->values()->all(),
            ],
            'ranking' => $ranking,
        ]);
    }

    public function currentSummary(): array
    {
        $allAlerts = MunicipalityCoverageAlert::query()->get();
        $metrics = $this->municipalityMetrics();
        $breaches = $this->currentSlaBreaches();

        return [
            'total_alerts' => $allAlerts->count(),
            'active_alerts' => $allAlerts->where('status', 'active')->count(),
            'resolved_alerts' => $allAlerts->where('status', 'resolved')->count(),
            'high_alerts' => $allAlerts->where('severity', 'high')->count(),
            'medium_alerts' => $allAlerts->where('severity', 'medium')->count(),
            'tracked_municipalities' => $metrics->count(),
            'average_configuration_score' => (int) round($metrics->avg('score') ?? 0),
            'average_executive_score' => (int) round($metrics->avg('executive_score') ?? 0),
            'sla_breaches_total' => $breaches->count(),
            'captured_at' => now()->toIso8601String(),
        ];
    }

    public function eventTypeOptions(): array
    {
        return [
            'mentions_coverage_lost' => 'Menções',
            'pra_hoje_coverage_lost' => 'Pra hoje',
            'configuration_coverage_lost' => 'Configurações',
            'executive_ranking_worsened' => 'Ranking executivo',
        ];
    }

    private function decorateSlaBreaches(Collection $alerts): Collection
    {
        $targets = $this->slaTargetsHours();
        $eventTypeOptions = $this->eventTypeOptions();

        return $alerts
            ->map(function (MunicipalityCoverageAlert $alert) use ($targets, $eventTypeOptions) {
                $startedAt = $this->startedAt($alert);
                if (!$startedAt) {
                    return null;
                }

                $targetHours = $targets[$alert->event_type] ?? 24;
                $hoursOpen = $startedAt->diffInMinutes(now()) / 60;

                if ($hoursOpen <= $targetHours) {
                    return null;
                }

                return [
                    'alert' => $alert,
                    'event_label' => $eventTypeOptions[$alert->event_type] ?? $alert->event_type,
                    'target_hours' => $targetHours,
                    'hours_open' => round($hoursOpen, 1),
                    'hours_overdue' => round($hoursOpen - $targetHours, 1),
                ];
            })
            ->filter()
            ->values();
    }

    private function startedAt(MunicipalityCoverageAlert $alert): ?CarbonInterface
    {
        return $alert->first_detected_at ?: $alert->created_at;
    }

    private function snapshotRow(array $row): array
    {
        return [
            'municipality_id' => $row['municipality_id'],
            'municipality_name' => $row['municipality_name'],
            'executive_score' => $row['executive_score'],
            'score' => $row['score'],
            'sla_breaches_total' => $row['sla_breaches_total'],
            'recurrence_30d' => $row['recurrence_30d'],
            'active_alerts_total' => $row['active_alerts_total'],
        ];
    }

    private function rankedMunicipalityMetrics(): Collection
    {
        return $this->municipalityMetrics()
            ->sortByDesc('executive_score')
            ->values()
            ->map(function (array $row, int $index) {
                $row['position'] = $index + 1;

                return $row;
            })
            ->values();
    }

    private function rankingWithTrend(): Collection
    {
        if ($this->rankingWithTrendCache instanceof Collection) {
            return $this->rankingWithTrendCache;
        }

        $latestSnapshotMap = $this->latestSnapshotRankingMap();
        $ranking = $this->rankedMunicipalityMetrics()
            ->map(function (array $row) use ($latestSnapshotMap) {
                $snapshotRow = $latestSnapshotMap->get($row['municipality_id']);
                $previousScore = (int) data_get($snapshotRow, 'executive_score', $row['executive_score']);
                $delta = $row['executive_score'] - $previousScore;
                $previousPosition = data_get($snapshotRow, 'position');

                $row['previous_executive_score'] = $previousScore;
                $row['executive_score_delta'] = $delta;
                $row['trend_direction'] = $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'stable');
                $row['previous_position'] = is_numeric($previousPosition) ? (int) $previousPosition : null;
                $row['position_delta'] = is_numeric($previousPosition) ? ((int) $previousPosition - $row['position']) : null;

                return $row;
            })
            ->values();

        $this->rankingWithTrendCache = $ranking;

        return $ranking;
    }

    private function latestSnapshotRankingMap(): Collection
    {
        if (!Schema::hasTable('municipality_coverage_snapshots')) {
            return collect();
        }

        $snapshot = MunicipalityCoverageSnapshot::query()
            ->latest('captured_at')
            ->first();

        if (!$snapshot || !is_array($snapshot->ranking)) {
            return collect();
        }

        return collect($snapshot->ranking)->keyBy('municipality_id');
    }
}
