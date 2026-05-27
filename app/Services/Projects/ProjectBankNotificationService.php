<?php

namespace App\Services\Projects;

use App\Models\ProjectThesis;
use App\Models\ProjectThesisNotification;
use App\Models\ProjectThesisShare;
use App\Models\User;
use App\Services\WebPushService;

class ProjectBankNotificationService
{
    public function __construct(
        private readonly WebPushService $webPush,
    ) {}

    public function dispatchShareNotification(ProjectThesisShare $share): ProjectThesisNotification
    {
        $share->loadMissing([
            'thesis',
            'sharedBy',
            'sharedWith',
        ]);

        $notification = ProjectThesisNotification::query()->create([
            'project_thesis_id' => $share->project_thesis_id,
            'user_id' => $share->shared_with_user_id,
            'project_thesis_share_id' => $share->id,
            'event_type' => 'share_received',
            'title' => 'Tese compartilhada com voce',
            'message' => $share->sharedBy?->name
                ? $share->sharedBy->name . ' compartilhou a tese "' . $share->thesis?->title . '" com voce.'
                : 'Uma tese foi compartilhada com voce no Banco de Projetos.',
            'action_url' => route('mayor.project-bank.show', $share->project_thesis_id),
            'fingerprint' => 'share_received:' . $share->id,
            'delivered_at' => now(),
            'metadata' => [
                'shared_by_user_id' => $share->shared_by_user_id,
                'shared_with_user_id' => $share->shared_with_user_id,
            ],
        ]);

        $this->sendPush($share->sharedWith, [
            'title' => 'Tese compartilhada com voce',
            'body' => ($share->sharedBy?->name ?? 'Sua equipe') . ' compartilhou a tese "' . ($share->thesis?->title ?? 'Sem titulo') . '"',
            'icon' => '/images/mascote-robo.jpg',
            'url' => route('mayor.project-bank.show', $share->project_thesis_id),
            'tag' => 'project-thesis-share-' . $share->project_thesis_id . '-share-' . $share->id,
            'requireInteraction' => false,
        ]);

        return $notification;
    }

    public function dispatchUrgencyAlerts(): int
    {
        $processed = 0;
        $today = now()->startOfDay();
        $windowEnd = $today->copy()->addDays(60)->endOfDay();

        ProjectThesis::query()
            ->with(['municipality', 'municipality.users'])
            ->where('urgency', 'alta')
            ->whereNotNull('resource_deadline')
            ->whereBetween('resource_deadline', [$today->toDateString(), $windowEnd->toDateString()])
            ->orderBy('resource_deadline')
            ->chunkById(100, function ($theses) use (&$processed, $today) {
                foreach ($theses as $thesis) {
                    $deadline = $thesis->resource_deadline?->copy()->startOfDay();
                    if (!$deadline || !$thesis->municipality) {
                        continue;
                    }

                    $daysRemaining = max($today->diffInDays($deadline, false), 0);
                    $bucket = $this->deadlineBucket($daysRemaining);
                    $recipients = $thesis->municipality->users
                        ->filter(fn (User $user) => $user->is_active && ($user->isMayor() || $user->isSecretary() || $user->isAdvisor()));

                    foreach ($recipients as $recipient) {
                        $fingerprint = 'resource_deadline_alert:' . $thesis->id . ':' . $deadline->format('Ymd') . ':' . $bucket;

                        if ($this->alreadySent($thesis, $recipient, 'resource_deadline_alert', $fingerprint)) {
                            continue;
                        }

                        ProjectThesisNotification::query()->create([
                            'project_thesis_id' => $thesis->id,
                            'user_id' => $recipient->id,
                            'event_type' => 'resource_deadline_alert',
                            'title' => 'Prazo proximo no Banco de Projetos',
                            'message' => $this->deadlineAlertMessage($thesis, $daysRemaining),
                            'action_url' => route('mayor.project-bank.show', $thesis),
                            'fingerprint' => $fingerprint,
                            'delivered_at' => now(),
                            'metadata' => [
                                'days_remaining' => $daysRemaining,
                                'resource_deadline' => $deadline->toDateString(),
                                'bucket' => $bucket,
                            ],
                        ]);

                        $this->sendPush($recipient, [
                            'title' => 'Prazo proximo no Banco de Projetos',
                            'body' => $thesis->title . ' tem prazo de recurso em ' . $this->deadlineLabel($daysRemaining) . '.',
                            'icon' => '/images/mascote-robo.jpg',
                            'url' => route('mayor.project-bank.show', $thesis),
                            'tag' => 'project-thesis-alert-' . $thesis->id . '-' . $bucket . '-user-' . $recipient->id,
                            'requireInteraction' => $daysRemaining <= 7,
                        ]);

                        $processed++;
                    }
                }
            });

        return $processed;
    }

    public function alreadySent(
        ProjectThesis $thesis,
        User $user,
        string $eventType,
        string $fingerprint
    ): bool {
        return ProjectThesisNotification::query()
            ->where('project_thesis_id', $thesis->id)
            ->where('user_id', $user->id)
            ->where('event_type', $eventType)
            ->where('fingerprint', $fingerprint)
            ->exists();
    }

    private function deadlineAlertMessage(ProjectThesis $thesis, int $daysRemaining): string
    {
        return implode("\n", array_filter([
            'Tese com urgencia alta e prazo de recurso proximo.',
            'Tese: ' . $thesis->title,
            'Categoria: ' . $thesis->category,
            $thesis->resource_deadline ? 'Prazo do recurso: ' . $thesis->resource_deadline->format('d/m/Y') : null,
            'Janela restante: ' . $this->deadlineLabel($daysRemaining) . '.',
            'Acao sugerida: revisar a tese e decidir se salva, compartilha ou ja vira projeto.',
        ]));
    }

    private function deadlineLabel(int $daysRemaining): string
    {
        return match (true) {
            $daysRemaining <= 0 => 'hoje',
            $daysRemaining === 1 => '1 dia',
            default => $daysRemaining . ' dias',
        };
    }

    private function deadlineBucket(int $daysRemaining): string
    {
        return match (true) {
            $daysRemaining <= 7 => 'lte7',
            $daysRemaining <= 30 => 'lte30',
            default => 'lte60',
        };
    }

    private function sendPush(?User $user, array $payload): void
    {
        if (!$user) {
            return;
        }

        try {
            $this->webPush->sendToUser($user, $payload);
        } catch (\Throwable) {
        }
    }
}
