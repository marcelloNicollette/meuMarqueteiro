<?php

namespace App\Console\Commands;

use App\Models\Demand;
use App\Services\ResolveAi\ResolveAiNotificationService;
use App\Services\ResolveAi\ResolveAiSettingsService;
use Illuminate\Console\Command;

class DispatchResolveAiAlerts extends Command
{
    protected $signature = 'resolve-ai:dispatch-alerts';
    protected $description = 'Dispara alertas operacionais do Resolve ai para prazos próximos e atrasos.';

    public function __construct(
        private readonly ResolveAiNotificationService $notifications,
        private readonly ResolveAiSettingsService $settings,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $processed = 0;
        $now = now();

        Demand::query()
            ->with(['municipality', 'contactArea', 'registeredBy', 'notifications'])
            ->whereIn('status', ['registered', 'in_progress', 'reopened', 'pending', 'overdue'])
            ->whereNotNull('due_at')
            ->orderBy('due_at')
            ->chunkById(100, function ($demands) use (&$processed, $now) {
                foreach ($demands as $demand) {
                    $municipality = $demand->municipality;
                    if (!$municipality) {
                        continue;
                    }

                    $settings = $this->settings->forMunicipality($municipality);
                    $leadHours = (int) ($settings['alert_lead_hours'] ?? 24);
                    $inactivityFollowupHours = (int) ($settings['inactivity_followup_hours'] ?? 48);
                    $overdueRepeatHours = (int) ($settings['overdue_repeat_hours'] ?? 24);
                    $warningFingerprint = 'deadline_warning:' . optional($demand->due_at)->timestamp;
                    $overdueFingerprint = 'overdue_alert:' . optional($demand->due_at)->timestamp;
                    $windowEnd = $now->copy()->addHours($leadHours);
                    $activityAnchor = $this->activityAnchor($demand);

                    if (
                        in_array($demand->status, ['registered', 'in_progress', 'reopened', 'pending'], true)
                        && $activityAnchor
                        && $activityAnchor->copy()->addHours($inactivityFollowupHours)->lte($now)
                    ) {
                        $inactivityFingerprint = 'inactivity_followup:' . $activityAnchor->timestamp;

                        if (!$this->notifications->alreadySent($demand, 'inactivity_followup', null, $inactivityFingerprint)) {
                            $hoursWithoutProgress = max((int) $activityAnchor->diffInHours($now), $inactivityFollowupHours);
                            $this->notifications->dispatchInactivityFollowup($demand, $hoursWithoutProgress);
                            $demand->events()->create([
                                'user_id' => null,
                                'event_type' => 'inactivity_followup',
                                'message' => 'Cobrança automática disparada por falta de andamento recente.',
                                'metadata' => [
                                    'hours_without_progress' => $hoursWithoutProgress,
                                    'activity_anchor_at' => $activityAnchor->toIso8601String(),
                                ],
                            ]);
                            $processed++;
                        }
                    }

                    if (
                        $demand->status !== 'overdue'
                        && $demand->due_at
                        && $demand->due_at->betweenIncluded($now, $windowEnd)
                        && !$this->notifications->alreadySent($demand, 'deadline_warning', null, $warningFingerprint)
                    ) {
                        $this->notifications->dispatchDeadlineWarning($demand);
                        $processed++;
                    }

                    if ($demand->due_at && $demand->due_at->isPast()) {
                        if ($demand->status !== 'overdue') {
                            $demand->update(['status' => 'overdue']);
                            $demand->events()->create([
                                'user_id' => null,
                                'event_type' => 'overdue_marked',
                                'message' => 'Prazo vencido sem conclusão. Demanda marcada como atrasada automaticamente.',
                                'metadata' => ['due_at' => $demand->due_at->toIso8601String()],
                            ]);
                            $demand->refresh();
                        }

                        if (!$this->notifications->alreadySent($demand, 'overdue_alert', null, $overdueFingerprint)) {
                            $this->notifications->dispatchOverdueAlert($demand);
                            $processed++;
                        }

                        $hoursOverdue = (int) max($demand->due_at->diffInHours($now), 0);
                        if ($hoursOverdue >= $overdueRepeatHours) {
                            $repeatBucket = (int) floor($hoursOverdue / max($overdueRepeatHours, 1));
                            $overdueFollowupFingerprint = 'overdue_followup:' . optional($demand->due_at)->timestamp . ':' . $repeatBucket;

                            if (!$this->notifications->alreadySent($demand, 'overdue_followup', null, $overdueFollowupFingerprint)) {
                                $this->notifications->dispatchOverdueReminder($demand, $hoursOverdue, $overdueFollowupFingerprint);
                                $demand->events()->create([
                                    'user_id' => null,
                                    'event_type' => 'overdue_followup',
                                    'message' => 'Cobrança automática repetida para demanda atrasada.',
                                    'metadata' => [
                                        'hours_overdue' => $hoursOverdue,
                                        'repeat_bucket' => $repeatBucket,
                                        'due_at' => $demand->due_at->toIso8601String(),
                                    ],
                                ]);
                                $processed++;
                            }
                        }
                    }
                }
            });

        $this->info("Alertas processados: {$processed}");

        return self::SUCCESS;
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
