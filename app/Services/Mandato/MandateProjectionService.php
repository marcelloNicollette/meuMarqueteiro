<?php

namespace App\Services\Mandato;

use App\Models\MandateAction;
use App\Models\MandateAxis;
use App\Models\MandatePromise;
use App\Models\Municipality;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class MandateProjectionService
{
    public function calculate(Municipality $municipality): array
    {
        $today = CarbonImmutable::today();
        $windowStart = $today->subDays(60)->startOfDay();
        $termEndDate = $this->resolveTermEndDate($municipality);
        $daysRemaining = max(0, $today->diffInDays($termEndDate, false));

        $axes = MandateAxis::query()
            ->where('municipality_id', $municipality->id)
            ->where('is_active', true)
            ->orderBy('order')
            ->get(['id', 'name', 'icon', 'order']);

        $promises = MandatePromise::query()
            ->where('municipality_id', $municipality->id)
            ->where('is_active', true)
            ->with([
                'actions' => fn ($query) => $query
                    ->select('mandate_actions.id', 'mandate_actions.municipality_id', 'mandate_actions.status')
                    ->withPivot('fulfillment_level'),
            ])
            ->get(['id', 'municipality_id', 'mandate_axis_id', 'text', 'status', 'score']);

        $actions = MandateAction::query()
            ->where('municipality_id', $municipality->id)
            ->with([
                'promises' => fn ($query) => $query
                    ->select('mandate_promises.id', 'mandate_promises.mandate_axis_id')
                    ->withPivot('fulfillment_level'),
                'progressLogs' => fn ($query) => $query
                    ->where('occurred_at', '>=', $windowStart)
                    ->orderBy('occurred_at'),
            ])
            ->get([
                'id',
                'municipality_id',
                'mandate_axis_id',
                'title',
                'status',
                'physical_progress',
                'created_at',
                'start_date',
                'end_date',
            ]);

        $runningActions = $actions->where('status', 'em_andamento')->values();
        $actionVelocities = $runningActions
            ->mapWithKeys(fn (MandateAction $action) => [$action->id => $this->calculateActionVelocity($action, $windowStart, $today)]);

        $portfolioVelocity = round(
            $actionVelocities->filter(fn (float $velocity) => $velocity > 0)->avg() ?? 0,
            2
        );

        $projectedActions = $runningActions
            ->mapWithKeys(fn (MandateAction $action) => [
                $action->id => $this->projectRunningAction(
                    action: $action,
                    termEndDate: $termEndDate,
                    fallbackVelocity: $portfolioVelocity,
                    actionVelocity: (float) ($actionVelocities[$action->id] ?? 0),
                    today: $today,
                ),
            ]);

        $projectedFulfilledIds = $promises
            ->filter(fn (MandatePromise $promise) => $this->isPromiseProjectedFulfilled($promise, $projectedActions))
            ->pluck('id')
            ->all();

        $currentFulfilled = $promises->where('status', 'fulfilled')->count();
        $projectedFulfilled = count($projectedFulfilledIds);
        $projectedPending = max($promises->count() - $projectedFulfilled, 0);

        $axisAlerts = $axes->map(function (MandateAxis $axis) use ($projectedActions, $promises) {
            $axisPromises = $promises->where('mandate_axis_id', $axis->id)->values();
            $projectedAxisFulfilled = $axisPromises
                ->filter(fn (MandatePromise $promise) => $this->isPromiseProjectedFulfilled($promise, $projectedActions))
                ->count();
            $gap = max($axisPromises->count() - $projectedAxisFulfilled, 0);

            return [
                'axis_id' => $axis->id,
                'axis_name' => $axis->name,
                'axis_icon' => $axis->icon,
                'total_promises' => $axisPromises->count(),
                'projected_fulfilled' => $projectedAxisFulfilled,
                'gap' => $gap,
                'risk_level' => match (true) {
                    $gap >= 5 => 'high',
                    $gap >= 2 => 'medium',
                    $gap >= 1 => 'low',
                    default => 'ok',
                },
                'running_actions' => $projectedActions
                    ->filter(fn (array $item) => (int) ($item['axis_id'] ?? 0) === $axis->id)
                    ->count(),
                'projected_running_actions_completed' => $projectedActions
                    ->filter(fn (array $item) => (int) ($item['axis_id'] ?? 0) === $axis->id && ($item['will_complete_by_term'] ?? false))
                    ->count(),
            ];
        })
            ->sortByDesc('gap')
            ->values()
            ->all();

        $maxAxisGap = collect($axisAlerts)->max('gap') ?? 0;
        $significantDeviation = $projectedPending >= 2 || $maxAxisGap >= 2;

        return [
            'term_end_date' => $termEndDate,
            'term_end_label' => $termEndDate->format('d/m/Y'),
            'days_remaining' => $daysRemaining,
            'window_days' => 60,
            'running_actions_considered' => $runningActions->count(),
            'portfolio_daily_progress_rate' => $portfolioVelocity,
            'projected_actions_completed' => $projectedActions->filter(fn (array $item) => $item['will_complete_by_term'] ?? false)->count(),
            'current_fulfilled_promises' => $currentFulfilled,
            'projected_fulfilled_promises' => $projectedFulfilled,
            'projected_pending_promises' => $projectedPending,
            'projected_completion_ratio' => $promises->count() > 0
                ? (int) round(($projectedFulfilled / $promises->count()) * 100)
                : 0,
            'needs_alert' => $projectedPending > 0,
            'significant_deviation' => $significantDeviation,
            'alert_message' => $projectedPending > 0
                ? "No ritmo atual, {$projectedPending} compromissos não serao entregues ate o fim do mandato."
                : 'No ritmo atual, o mandato projeta entregar todos os compromissos cadastrados.',
            'axis_alerts' => array_slice(array_values(array_filter($axisAlerts, fn (array $item) => $item['gap'] > 0)), 0, 4),
        ];
    }

    private function resolveTermEndDate(Municipality $municipality): CarbonImmutable
    {
        $configured = data_get($municipality->settings, 'mandato.term_end_date');

        if (filled($configured)) {
            try {
                return CarbonImmutable::parse($configured)->endOfDay();
            } catch (\Throwable) {
                // Fallback abaixo.
            }
        }

        $year = (int) now()->year;
        $endYear = $year % 4 === 0 ? $year : $year + (4 - ($year % 4));

        return CarbonImmutable::create($endYear, 12, 31)->endOfDay();
    }

    private function calculateActionVelocity(
        MandateAction $action,
        CarbonImmutable $windowStart,
        CarbonImmutable $today,
    ): float {
        $points = collect();

        $referenceStart = $action->start_date
            ? CarbonImmutable::parse($action->start_date)
            : CarbonImmutable::parse($action->created_at);

        if ($referenceStart->greaterThanOrEqualTo($windowStart)) {
            $points->push([
                'date' => $referenceStart,
                'progress' => 0,
            ]);
        }

        foreach ($action->progressLogs as $log) {
            if ($log->to_progress === null) {
                continue;
            }

            $points->push([
                'date' => CarbonImmutable::parse($log->occurred_at),
                'progress' => (int) $log->to_progress,
            ]);
        }

        $points->push([
            'date' => $today,
            'progress' => (int) ($action->physical_progress ?? 0),
        ]);

        $points = $points
            ->sortBy(fn (array $point) => $point['date']->timestamp)
            ->values();

        if ($points->count() < 2) {
            return 0.0;
        }

        $first = $points->first();
        $last = $points->last();
        $days = max(1, $first['date']->diffInDays($last['date']));
        $delta = max(0, (int) $last['progress'] - (int) $first['progress']);

        return round($delta / $days, 2);
    }

    private function projectRunningAction(
        MandateAction $action,
        CarbonImmutable $termEndDate,
        float $fallbackVelocity,
        float $actionVelocity,
        CarbonImmutable $today,
    ): array {
        $velocity = $actionVelocity > 0 ? $actionVelocity : $fallbackVelocity;
        $progress = (int) ($action->physical_progress ?? 0);

        if ($velocity <= 0 || $progress >= 100) {
            return [
                'axis_id' => $action->mandate_axis_id,
                'velocity' => $velocity,
                'will_complete_by_term' => false,
                'projected_completion_date' => null,
            ];
        }

        $daysToFinish = (int) ceil((100 - $progress) / $velocity);
        $completionDate = $today->addDays(max($daysToFinish, 1));

        return [
            'axis_id' => $action->mandate_axis_id,
            'velocity' => $velocity,
            'will_complete_by_term' => $completionDate->lessThanOrEqualTo($termEndDate),
            'projected_completion_date' => $completionDate,
        ];
    }

    private function isPromiseProjectedFulfilled(MandatePromise $promise, Collection $projectedActions): bool
    {
        if ($promise->status === 'fulfilled') {
            return true;
        }

        $maxProjectedLevel = $promise->actions->reduce(function (int $carry, MandateAction $action) use ($projectedActions) {
            if ($action->status === 'concluido') {
                return max($carry, (int) ($action->pivot->fulfillment_level ?? 0));
            }

            $projection = $projectedActions->get($action->id);
            if (($projection['will_complete_by_term'] ?? false) === true) {
                return max($carry, (int) ($action->pivot->fulfillment_level ?? 0));
            }

            return $carry;
        }, 0);

        return $maxProjectedLevel >= 100;
    }
}
