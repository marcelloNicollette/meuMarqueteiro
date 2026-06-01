<?php

namespace App\Services\Support;

use Barryvdh\DomPDF\Facade\Pdf;

class MunicipalityCoverageExecutiveReportService
{
    public function __construct(
        private readonly MunicipalityCoverageExecutiveService $executive,
        private readonly MunicipalityCoverageExecutiveMailGovernanceService $governance,
    ) {}

    public function buildPayload(string $period = 'manual', int $limit = 15, array $approvalContext = []): array
    {
        $ranking = $this->executive->executiveRankingWithTrend($limit)->values();
        $temporalComparison = $this->executive->temporalSnapshotComparison(6);
        $comparison = $this->executive->coverageComparison(5);
        $improvementCurve = $this->executive->municipalityImprovementCurve(8);
        $snapshotHistory = $this->executive->recentSnapshots(6);

        return [
            'period' => $period,
            'generated_at' => now(),
            'summary' => $this->executive->currentSummary(),
            'temporal_comparison' => $temporalComparison,
            'comparison' => $comparison,
            'ranking' => $ranking,
            'improvement_curve' => $improvementCurve,
            'snapshot_history' => $snapshotHistory,
            'identity' => $this->governance->identity(),
            'signatures' => $this->governance->signatures(),
            'approval' => $approvalContext,
            'ranking_summary' => [
                'tracked' => $ranking->count(),
                'leaders_improving' => $ranking->filter(fn (array $row) => ($row['trend_direction'] ?? 'stable') === 'up')->count(),
                'in_drop' => $ranking->filter(fn (array $row) => ($row['trend_direction'] ?? 'stable') === 'down')->count(),
                'avg_executive_score' => (int) round($ranking->avg('executive_score') ?? 0),
            ],
        ];
    }

    public function pdfBinary(string $period = 'manual', int $limit = 15, array $approvalContext = []): string
    {
        return $this->pdfInstance($period, $limit, $approvalContext)->output();
    }

    public function pdfDownload(string $period = 'manual', int $limit = 15, array $approvalContext = [])
    {
        return $this->pdfInstance($period, $limit, $approvalContext)
            ->download($this->pdfFilename($period));
    }

    public function pdfFilename(string $period = 'manual'): string
    {
        return 'ranking-executivo-cobertura-' . $period . '-' . now()->format('Ymd-His') . '.pdf';
    }

    private function pdfInstance(string $period, int $limit, array $approvalContext = [])
    {
        return Pdf::loadView('admin.coverage-alerts.exports.executive-ranking-pdf', [
            'payload' => $this->buildPayload($period, $limit, $approvalContext),
        ])->setPaper('a4');
    }
}
