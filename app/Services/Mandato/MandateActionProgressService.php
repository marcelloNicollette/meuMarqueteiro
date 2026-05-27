<?php

namespace App\Services\Mandato;

use App\Models\MandateAction;
use App\Models\MandateActionMilestone;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MandateActionProgressService
{
    public function syncMilestones(
        MandateAction $action,
        array $milestones,
        bool $usesMilestonesProgress,
        ?User $actor = null,
    ): void {
        $normalized = $this->normalizeMilestones($milestones);

        if ($usesMilestonesProgress && $normalized->isEmpty()) {
            throw ValidationException::withMessages([
                'milestones' => 'Adicione ao menos um marco para ativar o calculo automatico por marcos.',
            ]);
        }

        $existing = $action->milestones()->get()->keyBy('id');
        $keptIds = [];

        foreach ($normalized as $index => $item) {
            $milestone = isset($item['id']) ? $existing->get($item['id']) : null;
            $wasCompleted = (bool) $milestone?->completed_at;

            $payload = [
                'title' => $item['title'],
                'due_date' => $item['due_date'],
                'order' => $index + 1,
                'completed_at' => $item['completed']
                    ? ($milestone?->completed_at ?? now())
                    : null,
                'completed_by' => $item['completed']
                    ? ($milestone?->completed_by ?? $actor?->id)
                    : null,
            ];

            if ($milestone) {
                $milestone->update($payload);
            } else {
                $milestone = $action->milestones()->create($payload);
            }

            if (!$wasCompleted && $milestone->completed_at) {
                $action->progressLogs()->create([
                    'mandate_action_milestone_id' => $milestone->id,
                    'event_type' => 'milestone_completed',
                    'description' => 'Marco concluido: ' . $milestone->title,
                    'performed_by' => $milestone->completed_by,
                    'occurred_at' => $milestone->completed_at,
                ]);
            }

            $keptIds[] = $milestone->id;
        }

        $deleteQuery = $action->milestones();
        if (!empty($keptIds)) {
            $deleteQuery->whereNotIn('id', $keptIds);
        }

        $deleteQuery->delete();

        if ($usesMilestonesProgress) {
            $action->update([
                'physical_progress' => $this->calculateMilestoneProgress($action),
            ]);
        }
    }

    public function calculateMilestoneProgress(MandateAction $action): int
    {
        $milestones = $action->milestones()->get();

        if ($milestones->isEmpty()) {
            return 0;
        }

        $completed = $milestones->filter(fn (MandateActionMilestone $milestone) => (bool) $milestone->completed_at)->count();

        return (int) round(($completed / $milestones->count()) * 100);
    }

    public function recordProgressSnapshot(
        MandateAction $action,
        ?User $actor = null,
        ?int $beforeProgress = null,
        ?string $beforeStatus = null,
        bool $force = false,
    ): void {
        $beforeProgress = $beforeProgress ?? (int) ($action->getOriginal('physical_progress') ?? 0);
        $beforeStatus = $beforeStatus ?? ($action->getOriginal('status') ?: $action->status);
        $afterProgress = (int) ($action->physical_progress ?? 0);
        $afterStatus = (string) $action->status;

        if (!$force && $beforeProgress === $afterProgress && $beforeStatus === $afterStatus) {
            return;
        }

        $eventType = $force ? 'action_registered' : 'progress_updated';

        $description = $force
            ? sprintf('Acao registrada com status %s e progresso %d%%.', $action->status_label, $afterProgress)
            : sprintf(
                'Atualizacao de progresso: %d%% -> %d%%%s',
                $beforeProgress,
                $afterProgress,
                $beforeStatus !== $afterStatus ? " | status: {$beforeStatus} -> {$afterStatus}" : ''
            );

        $action->progressLogs()->create([
            'event_type' => $eventType,
            'description' => $description,
            'from_progress' => $beforeProgress,
            'to_progress' => $afterProgress,
            'from_status' => $beforeStatus,
            'to_status' => $afterStatus,
            'performed_by' => $actor?->id,
            'occurred_at' => now(),
        ]);
    }

    private function normalizeMilestones(array $milestones): Collection
    {
        return collect($milestones)
            ->map(function ($item) {
                $title = trim((string) data_get($item, 'title', ''));
                $dueDate = data_get($item, 'due_date');
                $completed = filter_var(data_get($item, 'completed', false), FILTER_VALIDATE_BOOLEAN);
                $hasContent = $title !== '' || filled($dueDate) || $completed;

                if (!$hasContent) {
                    return null;
                }

                if ($title === '') {
                    throw ValidationException::withMessages([
                        'milestones' => 'Informe o nome de cada marco preenchido.',
                    ]);
                }

                return [
                    'id' => ($id = (int) data_get($item, 'id')) > 0 ? $id : null,
                    'title' => Str::limit($title, 255, ''),
                    'due_date' => filled($dueDate) ? $dueDate : null,
                    'completed' => $completed,
                ];
            })
            ->filter()
            ->values();
    }
}
