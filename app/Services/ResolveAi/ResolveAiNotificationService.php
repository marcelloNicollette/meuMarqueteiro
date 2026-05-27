<?php

namespace App\Services\ResolveAi;

use App\Models\Demand;
use App\Models\DemandNotification;
use App\Models\User;
use App\Services\Support\RuntimeMailConfigService;

class ResolveAiNotificationService
{
    public function __construct(
        private readonly RuntimeMailConfigService $mail,
        private readonly ResolveAiSettingsService $settings,
    ) {}

    public function dispatchRegistered(Demand $demand): void
    {
        $this->notifyContactArea(
            $demand,
            'registered',
            'Nova demanda encaminhada no Resolve ai',
            $this->registeredMessage($demand)
        );
    }

    public function dispatchCompletionRequested(Demand $demand): void
    {
        $this->notifyUser(
            $demand,
            'completion_requested',
            $demand->registeredBy,
            'Demanda aguardando confirmação no Resolve ai',
            $this->completionRequestedMessage($demand)
        );
    }

    public function dispatchCompletionConfirmed(Demand $demand): void
    {
        $this->notifyContactArea(
            $demand,
            'completion_confirmed',
            'Demanda confirmada no Resolve ai',
            $this->completionConfirmedMessage($demand)
        );
    }

    public function dispatchReopened(Demand $demand): void
    {
        $this->notifyContactArea(
            $demand,
            'reopened',
            'Demanda reaberta no Resolve ai',
            $this->reopenedMessage($demand)
        );
    }

    public function dispatchDeadlineWarning(Demand $demand): void
    {
        $fingerprint = 'deadline_warning:' . optional($demand->due_at)->timestamp;

        $this->notifyContactArea(
            $demand,
            'deadline_warning',
            'Prazo próximo de vencimento no Resolve ai',
            $this->deadlineWarningMessage($demand),
            ['fingerprint' => $fingerprint]
        );
    }

    public function dispatchOverdueAlert(Demand $demand): void
    {
        $fingerprint = 'overdue_alert:' . optional($demand->due_at)->timestamp;

        $this->notifyUser(
            $demand,
            'overdue_alert',
            $demand->registeredBy,
            'Demanda atrasada no Resolve ai',
            $this->overdueAlertMessage($demand),
            ['fingerprint' => $fingerprint]
        );
    }

    public function dispatchInactivityFollowup(Demand $demand, int $hoursWithoutProgress): void
    {
        $activityAnchor = $this->activityAnchor($demand);
        $fingerprint = 'inactivity_followup:' . optional($activityAnchor)->timestamp;
        $metadata = [
            'fingerprint' => $fingerprint,
            'hours_without_progress' => $hoursWithoutProgress,
            'activity_anchor_at' => $activityAnchor?->toIso8601String(),
        ];

        $this->notifyContactArea(
            $demand,
            'inactivity_followup',
            'Cobrança automática por demanda sem andamento no Resolve ai',
            $this->inactivityFollowupMessage($demand, $hoursWithoutProgress),
            $metadata
        );

        $this->notifyUser(
            $demand,
            'inactivity_followup',
            $demand->registeredBy,
            'Acompanhamento automático de demanda sem andamento no Resolve ai',
            $this->inactivityFollowupMessage($demand, $hoursWithoutProgress),
            $metadata
        );
    }

    public function dispatchOverdueReminder(Demand $demand, int $hoursOverdue, string $fingerprint): void
    {
        $metadata = [
            'fingerprint' => $fingerprint,
            'hours_overdue' => $hoursOverdue,
            'due_at' => $demand->due_at?->toIso8601String(),
        ];

        $this->notifyContactArea(
            $demand,
            'overdue_followup',
            'Cobrança automática de demanda atrasada no Resolve ai',
            $this->overdueReminderMessage($demand, $hoursOverdue),
            $metadata
        );

        $this->notifyUser(
            $demand,
            'overdue_followup',
            $demand->registeredBy,
            'Lembrete automático de demanda atrasada no Resolve ai',
            $this->overdueReminderMessage($demand, $hoursOverdue),
            $metadata
        );
    }

    public function alreadySent(
        Demand $demand,
        string $eventType,
        ?string $channel = null,
        ?string $fingerprint = null,
        ?string $recipientType = null,
        ?int $recipientId = null
    ): bool
    {
        return $demand->notifications()
            ->where('event_type', $eventType)
            ->when($channel, fn ($query) => $query->where('channel', $channel))
            ->when($fingerprint, fn ($query) => $query->where('metadata->fingerprint', $fingerprint))
            ->when($recipientType, fn ($query) => $query->where('recipient_type', $recipientType))
            ->when($recipientId, fn ($query) => $query->where('recipient_id', $recipientId))
            ->whereIn('status', ['logged', 'sent'])
            ->exists();
    }

    private function notifyContactArea(
        Demand $demand,
        string $eventType,
        string $subject,
        string $message,
        array $metadata = []
    ): void {
        if (!$demand->contactArea) {
            return;
        }

        $channels = $this->settings->forMunicipality($demand->municipality)['channels'];

        if (($channels['internal'] ?? false) && !$this->alreadySent($demand, $eventType, 'internal', $metadata['fingerprint'] ?? null, 'contact_area', $demand->contact_area_id)) {
            $this->logNotification($demand, [
                'event_type' => $eventType,
                'channel' => 'internal',
                'recipient_type' => 'contact_area',
                'recipient_id' => $demand->contact_area_id,
                'destination' => $demand->contactArea->name,
                'status' => 'logged',
                'message' => $message,
                'metadata' => $metadata,
                'sent_at' => now(),
            ]);
        }

        $contactEmail = $demand->contactArea->notification_email ?: $demand->contactArea->email;

        if (($channels['email'] ?? false) && $contactEmail) {
            $this->sendEmail(
                $demand,
                $eventType,
                'contact_area',
                $demand->contact_area_id,
                $contactEmail,
                $subject,
                $message,
                $metadata
            );
        }
    }

    private function notifyUser(
        Demand $demand,
        string $eventType,
        ?User $user,
        string $subject,
        string $message,
        array $metadata = []
    ): void {
        if (!$user) {
            return;
        }

        $channels = $this->settings->forMunicipality($demand->municipality)['channels'];

        if (($channels['internal'] ?? false) && !$this->alreadySent($demand, $eventType, 'internal', $metadata['fingerprint'] ?? null, 'user', $user->id)) {
            $this->logNotification($demand, [
                'event_type' => $eventType,
                'channel' => 'internal',
                'recipient_type' => 'user',
                'recipient_id' => $user->id,
                'destination' => $user->name,
                'status' => 'logged',
                'message' => $message,
                'metadata' => $metadata,
                'sent_at' => now(),
            ]);
        }

        if (($channels['email'] ?? false) && $user->email) {
            $this->sendEmail(
                $demand,
                $eventType,
                'user',
                $user->id,
                $user->email,
                $subject,
                $message,
                $metadata
            );
        }
    }

    private function sendEmail(
        Demand $demand,
        string $eventType,
        string $recipientType,
        ?int $recipientId,
        string $destination,
        string $subject,
        string $message,
        array $metadata = []
    ): void {
        $fingerprint = $metadata['fingerprint'] ?? null;

        if ($this->alreadySent($demand, $eventType, 'email', $fingerprint, $recipientType, $recipientId)) {
            return;
        }

        try {
            $this->mail->sendRaw([$destination], $subject, $message);

            $this->logNotification($demand, [
                'event_type' => $eventType,
                'channel' => 'email',
                'recipient_type' => $recipientType,
                'recipient_id' => $recipientId,
                'destination' => $destination,
                'status' => 'sent',
                'message' => $message,
                'metadata' => $metadata,
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $this->logNotification($demand, [
                'event_type' => $eventType,
                'channel' => 'email',
                'recipient_type' => $recipientType,
                'recipient_id' => $recipientId,
                'destination' => $destination,
                'status' => 'failed',
                'message' => $message,
                'metadata' => $metadata,
                'failed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    private function logNotification(Demand $demand, array $payload): DemandNotification
    {
        return $demand->notifications()->create($payload);
    }

    private function registeredMessage(Demand $demand): string
    {
        return implode("\n", array_filter([
            'Nova demanda encaminhada para sua pasta.',
            'Demanda: ' . ($demand->title ?: $demand->raw_input),
            $demand->locality ? 'Localidade: ' . $demand->locality : null,
            'Prioridade: ' . ucfirst((string) $demand->priority),
            $demand->due_at ? 'Prazo: ' . $demand->due_at->format('d/m/Y H:i') : null,
        ]));
    }

    private function completionRequestedMessage(Demand $demand): string
    {
        return implode("\n", array_filter([
            'Uma demanda foi marcada como concluída e aguarda sua confirmação.',
            'Demanda: ' . ($demand->title ?: $demand->raw_input),
            $demand->completion_note ? 'Conclusão informada: ' . $demand->completion_note : null,
            $demand->contactArea?->name ? 'Secretaria: ' . $demand->contactArea->name : null,
        ]));
    }

    private function completionConfirmedMessage(Demand $demand): string
    {
        return implode("\n", array_filter([
            'A conclusão da demanda foi confirmada pelo criador.',
            'Demanda: ' . ($demand->title ?: $demand->raw_input),
            $demand->locality ? 'Localidade: ' . $demand->locality : null,
        ]));
    }

    private function reopenedMessage(Demand $demand): string
    {
        return implode("\n", array_filter([
            'A demanda foi reaberta e voltou para execução.',
            'Demanda: ' . ($demand->title ?: $demand->raw_input),
            $demand->reopened_reason ? 'Justificativa: ' . $demand->reopened_reason : null,
        ]));
    }

    private function deadlineWarningMessage(Demand $demand): string
    {
        return implode("\n", array_filter([
            'Prazo próximo de vencimento no Resolve ai.',
            'Demanda: ' . ($demand->title ?: $demand->raw_input),
            $demand->due_at ? 'Prazo: ' . $demand->due_at->format('d/m/Y H:i') : null,
            $demand->locality ? 'Localidade: ' . $demand->locality : null,
        ]));
    }

    private function overdueAlertMessage(Demand $demand): string
    {
        return implode("\n", array_filter([
            'A demanda ultrapassou o prazo previsto e permanece em aberto.',
            'Demanda: ' . ($demand->title ?: $demand->raw_input),
            $demand->contactArea?->name ? 'Secretaria: ' . $demand->contactArea->name : null,
            $demand->due_at ? 'Prazo original: ' . $demand->due_at->format('d/m/Y H:i') : null,
        ]));
    }

    private function inactivityFollowupMessage(Demand $demand, int $hoursWithoutProgress): string
    {
        return implode("\n", array_filter([
            'Cobrança automática do Resolve ai: a demanda está sem atualização recente.',
            'Demanda: ' . ($demand->title ?: $demand->raw_input),
            $demand->contactArea?->name ? 'Secretaria: ' . $demand->contactArea->name : null,
            $demand->locality ? 'Localidade: ' . $demand->locality : null,
            'Sem andamento há aproximadamente ' . $hoursWithoutProgress . 'h.',
            $demand->due_at ? 'Prazo atual: ' . $demand->due_at->format('d/m/Y H:i') : null,
        ]));
    }

    private function overdueReminderMessage(Demand $demand, int $hoursOverdue): string
    {
        return implode("\n", array_filter([
            'Cobrança automática do Resolve ai: a demanda segue atrasada.',
            'Demanda: ' . ($demand->title ?: $demand->raw_input),
            $demand->contactArea?->name ? 'Secretaria: ' . $demand->contactArea->name : null,
            $demand->locality ? 'Localidade: ' . $demand->locality : null,
            'Atraso acumulado: aproximadamente ' . $hoursOverdue . 'h.',
            $demand->due_at ? 'Prazo original: ' . $demand->due_at->format('d/m/Y H:i') : null,
        ]));
    }

    private function activityAnchor(Demand $demand)
    {
        return collect([
            $demand->last_progress_at,
            $demand->acknowledged_at,
            $demand->reopened_at,
            $demand->created_at,
        ])->filter()->sortDesc()->first();
    }
}
