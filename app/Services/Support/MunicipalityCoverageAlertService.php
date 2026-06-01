<?php

namespace App\Services\Support;

use App\Models\Municipality;
use App\Models\MunicipalityCoverageAlert;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\WebPushService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MunicipalityCoverageAlertService
{
    private array $ownerCache = [];

    public function __construct(
        private readonly MunicipalityConfigurationStatusService $configurationStatus,
        private readonly MunicipalityCoverageExecutiveService $executive,
        private readonly WebPushService $webPush,
    ) {}

    public function dispatchCoverageAlerts(): int
    {
        $processed = 0;
        $eligibleMunicipalityIds = Municipality::query()
            ->where('subscription_active', true)
            ->where('onboarding_status', 'completed')
            ->pluck('id');

        MunicipalityCoverageAlert::query()
            ->where('status', 'active')
            ->whereNotIn('municipality_id', $eligibleMunicipalityIds)
            ->get()
            ->each(fn (MunicipalityCoverageAlert $alert) => $this->resolveAlert($alert, null, 'municipality_not_eligible'));

        Municipality::query()
            ->where('subscription_active', true)
            ->where('onboarding_status', 'completed')
            ->with('mayor')
            ->orderBy('name')
            ->chunkById(100, function ($municipalities) use (&$processed) {
                foreach ($municipalities as $municipality) {
                    $processed += $this->syncForMunicipality($municipality);
                }
            });

        return $processed;
    }

    public function syncForMunicipality(Municipality $municipality): int
    {
        $summary = $this->configurationStatus->summarize($municipality);
        $definitions = collect($this->buildAlertDefinitions($municipality, $summary))->keyBy('event_type');
        $activeAlerts = MunicipalityCoverageAlert::query()
            ->where('municipality_id', $municipality->id)
            ->where('status', 'active')
            ->get()
            ->keyBy('event_type');

        $processed = 0;

        foreach ($definitions as $eventType => $definition) {
            $processed++;
            $alert = $activeAlerts->get($eventType);

            if (!$alert) {
                $metadata = $this->appendWorkflowEvent(
                    $this->mergedMetadata([], $definition['metadata'] ?? []),
                    'created',
                    [
                        'actor_name' => 'Sistema',
                        'actor_role' => 'Automação',
                        'details' => 'Alerta criado automaticamente pela leitura de cobertura.',
                    ]
                );

                $alert = MunicipalityCoverageAlert::query()->create([
                    'municipality_id' => $municipality->id,
                    'event_type' => $eventType,
                    'severity' => $definition['severity'],
                    'title' => $definition['title'],
                    'message' => $definition['message'],
                    'action_url' => $definition['action_url'] ?? null,
                    'fingerprint' => $definition['fingerprint'],
                    'status' => 'active',
                    'first_detected_at' => now(),
                    'last_detected_at' => now(),
                    'metadata' => $metadata,
                ]);

                $this->pushToAdmins($alert, $municipality);
                continue;
            }

            $shouldRepush = $this->shouldRepush($alert, $definition);

            $alert->update([
                'severity' => $definition['severity'],
                'title' => $definition['title'],
                'message' => $definition['message'],
                'action_url' => $definition['action_url'] ?? null,
                'fingerprint' => $definition['fingerprint'],
                'last_detected_at' => now(),
                'metadata' => $this->mergedMetadata($alert->metadata ?? [], $definition['metadata'] ?? []),
            ]);

            if ($shouldRepush) {
                $this->pushToAdmins($alert->fresh(), $municipality);
            }
        }

        $staleAlerts = $activeAlerts->keys()->diff($definitions->keys());
        if ($staleAlerts->isNotEmpty()) {
            MunicipalityCoverageAlert::query()
                ->where('municipality_id', $municipality->id)
                ->where('status', 'active')
                ->whereIn('event_type', $staleAlerts->all())
                ->get()
                ->each(function (MunicipalityCoverageAlert $alert) {
                    $this->resolveAlert($alert, null, 'coverage_restored');
                });
        }

        return $processed;
    }

    public function activeForMunicipality(Municipality $municipality): Collection
    {
        return MunicipalityCoverageAlert::query()
            ->where('municipality_id', $municipality->id)
            ->where('status', 'active')
            ->latest('last_detected_at')
            ->get();
    }

    public function recentForMunicipality(Municipality $municipality, int $limit = 8): Collection
    {
        return MunicipalityCoverageAlert::query()
            ->where('municipality_id', $municipality->id)
            ->latest('last_detected_at')
            ->limit($limit)
            ->get();
    }

    public function resolveSelected(array $alertIds, ?User $actor = null): int
    {
        $alerts = $this->selectedAlerts($alertIds)->where('status', 'active');

        foreach ($alerts as $alert) {
            $this->resolveAlert($alert, $actor, 'manual_resolution');
        }

        return $alerts->count();
    }

    public function recheckSelected(array $alertIds): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $alertIds))));
        if (empty($ids)) {
            return 0;
        }

        $municipalities = Municipality::query()
            ->whereIn(
                'id',
                MunicipalityCoverageAlert::query()
                    ->whereIn('id', $ids)
                    ->pluck('municipality_id')
                    ->unique()
                    ->all()
            )
            ->with('mayor')
            ->get();

        foreach ($municipalities as $municipality) {
            $this->syncForMunicipality($municipality);
        }

        return $municipalities->count();
    }

    public function activateCriticalPreset(array $filters): array
    {
        return array_merge($filters, [
            'severity' => 'high',
            'status' => 'active',
        ]);
    }

    public function acknowledge(MunicipalityCoverageAlert $alert, User $actor): void
    {
        $metadata = $alert->metadata ?? [];
        data_set($metadata, 'workflow.acknowledged', true);
        data_set($metadata, 'workflow.acknowledged_at', now()->toIso8601String());
        data_set($metadata, 'workflow.acknowledged_by_user_id', $actor->id);
        data_set($metadata, 'workflow.acknowledged_by_name', $actor->name);
        data_set($metadata, 'workflow.acknowledged_by_role', $this->roleLabel($actor));
        $metadata = $this->appendWorkflowEvent($metadata, 'acknowledged', [
            'actor_user_id' => $actor->id,
            'actor_name' => $actor->name,
            'actor_role' => $this->roleLabel($actor),
            'details' => 'Alerta reconhecido pelo owner ou pela operação.',
        ]);

        $alert->update(['metadata' => $metadata]);
    }

    public function unacknowledge(MunicipalityCoverageAlert $alert): void
    {
        $metadata = $alert->metadata ?? [];
        data_set($metadata, 'workflow.acknowledged', false);
        data_set($metadata, 'workflow.acknowledged_at', null);
        data_set($metadata, 'workflow.acknowledged_by_user_id', null);
        data_set($metadata, 'workflow.acknowledged_by_name', null);
        data_set($metadata, 'workflow.acknowledged_by_role', null);
        $metadata = $this->appendWorkflowEvent($metadata, 'acknowledge_removed', [
            'actor_name' => 'Operação',
            'actor_role' => 'Central executiva',
            'details' => 'Acknowledge removido manualmente.',
        ]);

        $alert->update(['metadata' => $metadata]);
    }

    public function assignOwner(MunicipalityCoverageAlert $alert, ?User $owner, User $actor): void
    {
        $metadata = $alert->metadata ?? [];
        data_set($metadata, 'workflow.owner_user_id', $owner?->id);
        data_set($metadata, 'workflow.owner_name', $owner?->name);
        data_set($metadata, 'workflow.owner_role', $owner ? $this->roleLabel($owner) : null);
        data_set($metadata, 'workflow.owner_role_key', $owner?->role?->value ?? ($owner ? (string) $owner->role : null));
        data_set($metadata, 'workflow.owner_assigned_by_user_id', $actor->id);
        data_set($metadata, 'workflow.owner_assigned_by_name', $actor->name);
        data_set($metadata, 'workflow.owner_assigned_at', now()->toIso8601String());
        $metadata = $this->appendWorkflowEvent($metadata, $owner ? 'owner_assigned' : 'owner_cleared', [
            'actor_user_id' => $actor->id,
            'actor_name' => $actor->name,
            'actor_role' => $this->roleLabel($actor),
            'details' => $owner
                ? 'Owner definido para ' . $owner->name . ' (' . $this->roleLabel($owner) . ').'
                : 'Owner removido do alerta.',
            'owner_user_id' => $owner?->id,
            'owner_name' => $owner?->name,
            'owner_role' => $owner ? $this->roleLabel($owner) : null,
        ]);

        $alert->update(['metadata' => $metadata]);
    }

    public function acknowledgeSelected(array $alertIds, User $actor): int
    {
        $alerts = $this->selectedAlerts($alertIds);

        foreach ($alerts as $alert) {
            $this->acknowledge($alert, $actor);
        }

        return $alerts->count();
    }

    public function addInternalComment(MunicipalityCoverageAlert $alert, User $actor, string $comment): void
    {
        $metadata = is_array($alert->metadata) ? $alert->metadata : [];
        $comments = collect(data_get($metadata, 'workflow.comments', []))
            ->filter(fn ($row) => is_array($row))
            ->values();
        $comment = trim($comment);

        $comments->push([
            'id' => (string) str()->uuid(),
            'message' => $comment,
            'author_user_id' => $actor->id,
            'author_name' => $actor->name,
            'author_role' => $this->roleLabel($actor),
            'at' => now()->toIso8601String(),
        ]);

        data_set($metadata, 'workflow.comments', $comments->take(-30)->values()->all());
        $metadata = $this->appendWorkflowEvent($metadata, 'comment_added', [
            'actor_user_id' => $actor->id,
            'actor_name' => $actor->name,
            'actor_role' => $this->roleLabel($actor),
            'details' => 'Comentário interno registrado na central.',
        ]);

        $alert->update(['metadata' => $metadata]);
    }

    public function assignSelectedToOwner(array $alertIds, ?User $owner, User $actor): int
    {
        $alerts = $this->selectedAlerts($alertIds);

        foreach ($alerts as $alert) {
            $this->assignOwner($alert, $owner, $actor);
        }

        return $alerts->count();
    }

    public function workflowSnapshot(MunicipalityCoverageAlert $alert): array
    {
        $metadata = is_array($alert->metadata) ? $alert->metadata : [];
        $owner = $this->resolveOwnerFromMetadata($metadata);
        $ownerAssignedAt = data_get($metadata, 'workflow.owner_assigned_at')
            ? Carbon::parse((string) data_get($metadata, 'workflow.owner_assigned_at'))
            : null;
        $ownerTargetHours = $this->ownerSlaTargetHours($alert, $owner);
        $deadline = $ownerAssignedAt?->copy()->addHours($ownerTargetHours);
        $remainingMinutes = $deadline ? now()->diffInMinutes($deadline, false) : null;
        $overdueHours = $deadline && $deadline->isPast()
            ? round(abs(now()->diffInMinutes($deadline, false)) / 60, 1)
            : 0.0;
        $history = collect(data_get($metadata, 'workflow.history', []))
            ->filter(fn ($row) => is_array($row))
            ->sortByDesc('at')
            ->values();
        $comments = collect(data_get($metadata, 'workflow.comments', []))
            ->filter(fn ($row) => is_array($row))
            ->sortByDesc('at')
            ->values();
        $warningMinutes = $this->ownerWarningMinutes();
        $remainingHours = $remainingMinutes !== null ? round($remainingMinutes / 60, 1) : null;

        return [
            'owner_name' => data_get($metadata, 'workflow.owner_name'),
            'owner_user_id' => data_get($metadata, 'workflow.owner_user_id'),
            'owner_role' => data_get($metadata, 'workflow.owner_role'),
            'owner_role_key' => data_get($metadata, 'workflow.owner_role_key'),
            'owner_assigned_at' => $ownerAssignedAt?->toIso8601String(),
            'acknowledged' => (bool) data_get($metadata, 'workflow.acknowledged', false),
            'acknowledged_by_name' => data_get($metadata, 'workflow.acknowledged_by_name'),
            'acknowledged_at' => data_get($metadata, 'workflow.acknowledged_at'),
            'owner_sla_target_hours' => $ownerTargetHours,
            'owner_sla_deadline' => $deadline?->toIso8601String(),
            'owner_sla_status' => $this->ownerSlaStatus($alert, $ownerAssignedAt, $remainingMinutes),
            'owner_sla_overdue_hours' => $overdueHours,
            'owner_sla_remaining_minutes' => $remainingMinutes,
            'owner_sla_remaining_hours' => $remainingHours,
            'owner_warning_minutes' => $warningMinutes,
            'history' => $history->take(20)->all(),
            'history_count' => $history->count(),
            'comments' => $comments->take(12)->all(),
            'comments_count' => $comments->count(),
            'last_transition' => data_get($metadata, 'workflow.last_transition'),
            'last_transition_at' => data_get($metadata, 'workflow.last_transition_at'),
        ];
    }

    public function personalQueueFor(User $user, int $limit = 8): Collection
    {
        return MunicipalityCoverageAlert::query()
            ->with('municipality')
            ->where('status', 'active')
            ->whereRaw("(metadata->'workflow'->>'owner_user_id') = ?", [(string) $user->id])
            ->latest('last_detected_at')
            ->limit($limit)
            ->get()
            ->map(function (MunicipalityCoverageAlert $alert) {
                $alert->setAttribute('workflow_snapshot', $this->workflowSnapshot($alert));

                return $alert;
            })
            ->values();
    }

    public function personalQueueSummary(Collection $alerts): array
    {
        return [
            'total' => $alerts->count(),
            'high' => $alerts->where('severity', 'high')->count(),
            'breached' => $alerts->filter(fn (MunicipalityCoverageAlert $alert) => data_get($alert, 'workflow_snapshot.owner_sla_status') === 'breached')->count(),
            'warning' => $alerts->filter(fn (MunicipalityCoverageAlert $alert) => data_get($alert, 'workflow_snapshot.owner_sla_status') === 'warning')->count(),
        ];
    }

    public function dispatchOwnerDeadlineWarnings(): int
    {
        if (!(bool) SystemSetting::get('coverage_alert_owner_notifications_enabled', SystemSetting::defaults()['coverage_alert_owner_notifications_enabled'])) {
            return 0;
        }

        $processed = 0;
        $alerts = MunicipalityCoverageAlert::query()
            ->with('municipality')
            ->where('status', 'active')
            ->whereRaw("(metadata->'workflow'->>'owner_user_id') IS NOT NULL")
            ->get();

        foreach ($alerts as $alert) {
            $workflow = $this->workflowSnapshot($alert);
            $owner = $this->resolveOwnerFromMetadata(is_array($alert->metadata) ? $alert->metadata : []);
            if (!$owner || !$owner->is_active) {
                continue;
            }

            $status = $workflow['owner_sla_status'] ?? 'unassigned';
            if (!in_array($status, ['warning', 'breached'], true)) {
                continue;
            }

            $deadline = data_get($workflow, 'owner_sla_deadline');
            if (!$deadline) {
                continue;
            }

            $fingerprint = $status . ':' . Carbon::parse((string) $deadline)->timestamp;
            if ($this->ownerNotificationAlreadySent($alert, $status, $fingerprint)) {
                continue;
            }

            try {
                $this->webPush->sendToUser($owner, [
                    'title' => $status === 'breached' ? 'SLA do owner estourado' : 'SLA do owner prestes a vencer',
                    'body' => ($alert->municipality?->name ?? 'Município') . ' · ' . $alert->title,
                    'icon' => '/images/mascote-robo.jpg',
                    'url' => route('admin.coverage-alerts.index'),
                    'tag' => 'coverage-owner-' . $status . '-' . $alert->id,
                    'requireInteraction' => $status === 'breached',
                ]);

                $this->markOwnerNotificationSent($alert, $status, $fingerprint, $owner);
                $processed++;
            } catch (\Throwable) {
            }
        }

        return $processed;
    }

    private function buildAlertDefinitions(Municipality $municipality, array $summary): array
    {
        $definitions = [];
        $activeChannels = collect($summary['active_channels'] ?? []);
        $monitoringTerms = collect($summary['monitoring_terms'] ?? []);
        $notificationChannels = $this->notificationChannels($municipality);
        $requiredChecklist = collect($summary['checklist'] ?? [])
            ->only(['base_institucional', 'perfil_estrategico', 'mapa_politico', 'comunicacao', 'notificacoes']);
        $missingRequired = $requiredChecklist->filter(fn ($item) => ($item['ready'] ?? false) !== true);

        if ($monitoringTerms->isEmpty() || ($summary['monitoring_keywords_total'] ?? 0) < 3 || $activeChannels->isEmpty()) {
            $definitions[] = [
                'event_type' => 'mentions_coverage_lost',
                'severity' => ($monitoringTerms->isEmpty() || $activeChannels->isEmpty()) ? 'high' : 'medium',
                'title' => 'Menções perderam cobertura mínima',
                'message' => 'Termos: ' . $monitoringTerms->count()
                    . ' · palavras-chave ativas: ' . ($summary['monitoring_keywords_total'] ?? 0)
                    . ' · canais ativos: ' . $activeChannels->count()
                    . '. Revise o contexto de comunicação e o monitoramento.',
                'action_url' => route('admin.municipalities.onboarding.show', $municipality),
                'fingerprint' => 'mentions_coverage:' . $municipality->id,
                'metadata' => [
                    'monitoring_terms_count' => $monitoringTerms->count(),
                    'monitoring_keywords_total' => $summary['monitoring_keywords_total'] ?? 0,
                    'active_channels_count' => $activeChannels->count(),
                ],
            ];
        }

        $platformEnabled = $notificationChannels['platform'] ?? true;
        $emailEnabled = $notificationChannels['email'] ?? false;
        $praHojeReady = !empty($summary['pra_hoje_time']) && ($summary['pra_hoje_enabled'] ?? false) && ($platformEnabled || $emailEnabled);

        if (!$praHojeReady) {
            $definitions[] = [
                'event_type' => 'pra_hoje_coverage_lost',
                'severity' => 'high',
                'title' => 'Pra hoje perdeu cobertura mínima',
                'message' => 'O briefing diário está sem horário válido, desativado ou sem canal de entrega habilitado para este município.',
                'action_url' => route('admin.municipalities.onboarding.show', $municipality),
                'fingerprint' => 'pra_hoje_coverage:' . $municipality->id,
                'metadata' => [
                    'pra_hoje_time' => $summary['pra_hoje_time'],
                    'pra_hoje_enabled' => $summary['pra_hoje_enabled'] ?? false,
                    'notification_channels' => $notificationChannels,
                ],
            ];
        }

        if (($summary['score'] ?? 0) < 70 || $missingRequired->isNotEmpty()) {
            $missingLabel = $missingRequired->pluck('label')->implode(', ');
            if ($missingLabel === '') {
                $missingLabel = collect($summary['issues'] ?? [])->implode(', ') ?: 'blocos essenciais precisam de revisão';
            }

            $definitions[] = [
                'event_type' => 'configuration_coverage_lost',
                'severity' => ($summary['score'] ?? 0) < 55 ? 'high' : 'medium',
                'title' => 'Configurações perderam cobertura mínima',
                'message' => 'Score atual: ' . ($summary['score'] ?? 0)
                    . '% · pendências: '
                    . $missingLabel
                    . '. Recomenda-se revisar o onboarding administrativo.',
                'action_url' => route('admin.municipalities.show', $municipality),
                'fingerprint' => 'configuration_coverage:' . $municipality->id,
                'metadata' => [
                    'score' => $summary['score'] ?? 0,
                    'issues' => $summary['issues'] ?? [],
                    'missing_required' => $missingRequired->pluck('label')->values()->all(),
                ],
            ];
        }

        $executiveMetric = $this->executive->municipalityMetricWithTrend($municipality->id);
        $thresholds = $this->governanceThresholds($municipality);

        if (($thresholds['enabled'] ?? true) && is_array($executiveMetric)) {
            $breaches = [];

            if (($executiveMetric['score'] ?? 0) < $thresholds['minimum_configuration_score']) {
                $breaches[] = 'configuração em ' . $executiveMetric['score'] . '% abaixo da meta de ' . $thresholds['minimum_configuration_score'] . '%';
            }

            if (($executiveMetric['executive_score'] ?? 0) < $thresholds['minimum_executive_score']) {
                $breaches[] = 'score executivo em ' . $executiveMetric['executive_score'] . ' abaixo da meta de ' . $thresholds['minimum_executive_score'];
            }

            if (($executiveMetric['executive_score_delta'] ?? 0) <= ($thresholds['maximum_negative_score_delta'] * -1)) {
                $breaches[] = 'queda de ' . abs((int) $executiveMetric['executive_score_delta']) . ' ponto(s) acima do limite de ' . $thresholds['maximum_negative_score_delta'];
            }

            if (($executiveMetric['position_delta'] ?? 0) <= ($thresholds['maximum_position_loss'] * -1)) {
                $breaches[] = 'perda de ' . abs((int) $executiveMetric['position_delta']) . ' posição(ões) acima do limite de ' . $thresholds['maximum_position_loss'];
            }

            if (($executiveMetric['active_alerts_total'] ?? 0) > $thresholds['maximum_active_alerts']) {
                $breaches[] = 'alertas ativos em ' . $executiveMetric['active_alerts_total'] . ' acima do teto de ' . $thresholds['maximum_active_alerts'];
            }

            if (($executiveMetric['sla_breaches_total'] ?? 0) > $thresholds['maximum_sla_breaches']) {
                $breaches[] = 'breaches de SLA em ' . $executiveMetric['sla_breaches_total'] . ' acima do teto de ' . $thresholds['maximum_sla_breaches'];
            }

            if ($breaches !== []) {
                $definitions[] = [
                    'event_type' => 'executive_ranking_worsened',
                    'severity' => $this->executiveThresholdSeverity($executiveMetric, $thresholds),
                    'title' => 'Ranking executivo entrou em piora automática',
                    'message' => 'Triggers: ' . implode(' · ', $breaches) . '. Revise o município e as metas operacionais configuradas.',
                    'action_url' => route('admin.municipalities.show', $municipality),
                    'fingerprint' => 'executive_ranking_worsened:' . $municipality->id,
                    'metadata' => [
                        'thresholds' => $thresholds,
                        'current_metric' => [
                            'position' => $executiveMetric['position'] ?? null,
                            'executive_score' => $executiveMetric['executive_score'] ?? null,
                            'configuration_score' => $executiveMetric['score'] ?? null,
                            'executive_score_delta' => $executiveMetric['executive_score_delta'] ?? null,
                            'position_delta' => $executiveMetric['position_delta'] ?? null,
                            'active_alerts_total' => $executiveMetric['active_alerts_total'] ?? null,
                            'sla_breaches_total' => $executiveMetric['sla_breaches_total'] ?? null,
                        ],
                        'breaches' => $breaches,
                    ],
                ];
            }
        }

        return $definitions;
    }

    private function notificationChannels(Municipality $municipality): array
    {
        $settings = is_array($municipality->settings) ? $municipality->settings : [];

        return [
            'platform' => (bool) data_get($settings, 'notifications.channels.platform', true),
            'email' => (bool) data_get($settings, 'notifications.channels.email', false),
            'whatsapp' => (bool) data_get($settings, 'notifications.channels.whatsapp', false),
        ];
    }

    private function shouldRepush(MunicipalityCoverageAlert $alert, array $definition): bool
    {
        if ($alert->severity !== $definition['severity']) {
            return true;
        }

        if (!$alert->last_pushed_at) {
            return true;
        }

        return $alert->last_pushed_at->lte(now()->subHours(12));
    }

    private function governanceThresholds(Municipality $municipality): array
    {
        $settings = is_array($municipality->settings) ? $municipality->settings : [];
        $defaults = $this->executive->governanceThresholdDefaults();

        return [
            'enabled' => (bool) data_get($settings, 'coverage_governance.thresholds.enabled', $defaults['enabled']),
            'minimum_configuration_score' => (int) data_get($settings, 'coverage_governance.thresholds.minimum_configuration_score', $defaults['minimum_configuration_score']),
            'minimum_executive_score' => (int) data_get($settings, 'coverage_governance.thresholds.minimum_executive_score', $defaults['minimum_executive_score']),
            'maximum_negative_score_delta' => (int) data_get($settings, 'coverage_governance.thresholds.maximum_negative_score_delta', $defaults['maximum_negative_score_delta']),
            'maximum_position_loss' => (int) data_get($settings, 'coverage_governance.thresholds.maximum_position_loss', $defaults['maximum_position_loss']),
            'maximum_active_alerts' => (int) data_get($settings, 'coverage_governance.thresholds.maximum_active_alerts', $defaults['maximum_active_alerts']),
            'maximum_sla_breaches' => (int) data_get($settings, 'coverage_governance.thresholds.maximum_sla_breaches', $defaults['maximum_sla_breaches']),
        ];
    }

    private function executiveThresholdSeverity(array $metric, array $thresholds): string
    {
        if (($metric['executive_score'] ?? 0) < max(0, $thresholds['minimum_executive_score'] - 10)) {
            return 'high';
        }

        if (($metric['executive_score_delta'] ?? 0) <= max(1, $thresholds['maximum_negative_score_delta']) * -2) {
            return 'high';
        }

        if (($metric['sla_breaches_total'] ?? 0) > max(1, $thresholds['maximum_sla_breaches'])) {
            return 'high';
        }

        return 'medium';
    }

    private function selectedAlerts(array $alertIds): Collection
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $alertIds))));
        if ($ids === []) {
            return collect();
        }

        return MunicipalityCoverageAlert::query()
            ->whereIn('id', $ids)
            ->get();
    }

    private function mergedMetadata(array $existing, array $runtime): array
    {
        return array_replace_recursive($existing, $runtime);
    }

    private function resolveAlert(MunicipalityCoverageAlert $alert, ?User $actor = null, string $reason = 'resolved'): void
    {
        $metadata = is_array($alert->metadata) ? $alert->metadata : [];
        $metadata = $this->appendWorkflowEvent($metadata, 'resolved', [
            'actor_user_id' => $actor?->id,
            'actor_name' => $actor?->name ?? 'Sistema',
            'actor_role' => $actor ? $this->roleLabel($actor) : 'Automação',
            'details' => match ($reason) {
                'coverage_restored' => 'Alerta encerrado automaticamente após revalidação com cobertura restabelecida.',
                'municipality_not_eligible' => 'Alerta encerrado porque o município saiu da base elegível da central.',
                default => 'Alerta resolvido manualmente na central executiva.',
            },
            'reason' => $reason,
        ]);

        $alert->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'metadata' => $metadata,
        ]);
    }

    private function appendWorkflowEvent(array $metadata, string $transition, array $context = []): array
    {
        $history = collect(data_get($metadata, 'workflow.history', []))
            ->filter(fn ($row) => is_array($row))
            ->values();

        $history->push(array_merge([
            'transition' => $transition,
            'at' => now()->toIso8601String(),
        ], $context));

        data_set($metadata, 'workflow.history', $history->take(-40)->values()->all());
        data_set($metadata, 'workflow.last_transition', $transition);
        data_set($metadata, 'workflow.last_transition_at', now()->toIso8601String());

        return $metadata;
    }

    private function ownerSlaTargetHours(MunicipalityCoverageAlert $alert, ?User $owner = null): int
    {
        $defaults = [
            'high' => 4,
            'medium' => 12,
            'default' => 24,
        ];

        $severityKey = match ($alert->severity) {
            'high' => 'high',
            'medium' => 'medium',
            default => 'default',
        };

        $ownerPreferences = $owner && is_array($owner->preferences) ? $owner->preferences : [];
        $ownerOverride = data_get($ownerPreferences, 'coverage_alerts.owner_sla_hours.' . $severityKey);
        if (is_numeric($ownerOverride) && (int) $ownerOverride > 0) {
            return (int) $ownerOverride;
        }

        $roleKey = $owner?->role?->value ?? ($owner ? (string) $owner->role : 'admin');
        $profileSetting = 'coverage_alert_owner_sla_' . $roleKey . '_' . $severityKey . '_hours';
        $profileValue = SystemSetting::get($profileSetting);
        if (is_numeric($profileValue) && (int) $profileValue > 0) {
            return (int) $profileValue;
        }

        return match ($severityKey) {
            'high' => (int) SystemSetting::get('coverage_alert_owner_sla_high_hours', $defaults['high']),
            'medium' => (int) SystemSetting::get('coverage_alert_owner_sla_medium_hours', $defaults['medium']),
            default => (int) SystemSetting::get('coverage_alert_owner_sla_default_hours', $defaults['default']),
        };
    }

    private function ownerSlaStatus(MunicipalityCoverageAlert $alert, ?Carbon $ownerAssignedAt, ?int $remainingMinutes): string
    {
        if ($alert->status !== 'active') {
            return 'resolved';
        }

        if (!$ownerAssignedAt) {
            return 'unassigned';
        }

        if ($remainingMinutes === null) {
            return 'unassigned';
        }

        if ($remainingMinutes < 0) {
            return 'breached';
        }

        if ($remainingMinutes <= $this->ownerWarningMinutes()) {
            return 'warning';
        }

        return 'ok';
    }

    private function ownerWarningMinutes(): int
    {
        return max(15, (int) SystemSetting::get(
            'coverage_alert_owner_warning_minutes',
            SystemSetting::defaults()['coverage_alert_owner_warning_minutes']
        ));
    }

    private function resolveOwnerFromMetadata(array $metadata): ?User
    {
        $ownerId = (int) data_get($metadata, 'workflow.owner_user_id', 0);
        if ($ownerId <= 0) {
            return null;
        }

        if (!array_key_exists($ownerId, $this->ownerCache)) {
            $this->ownerCache[$ownerId] = User::query()->find($ownerId);
        }

        return $this->ownerCache[$ownerId];
    }

    private function ownerNotificationAlreadySent(MunicipalityCoverageAlert $alert, string $status, string $fingerprint): bool
    {
        return (string) data_get($alert->metadata, 'workflow.notifications.' . $status . '.fingerprint', '') === $fingerprint;
    }

    private function markOwnerNotificationSent(MunicipalityCoverageAlert $alert, string $status, string $fingerprint, User $owner): void
    {
        $metadata = is_array($alert->metadata) ? $alert->metadata : [];
        data_set($metadata, 'workflow.notifications.' . $status, [
            'fingerprint' => $fingerprint,
            'sent_at' => now()->toIso8601String(),
            'owner_user_id' => $owner->id,
            'owner_name' => $owner->name,
        ]);
        $metadata = $this->appendWorkflowEvent($metadata, 'owner_' . $status . '_notification_sent', [
            'actor_name' => 'Sistema',
            'actor_role' => 'Automação',
            'details' => 'Notificação enviada ao owner para SLA ' . ($status === 'breached' ? 'estourado' : 'iminente') . '.',
            'owner_user_id' => $owner->id,
            'owner_name' => $owner->name,
        ]);

        $alert->update(['metadata' => $metadata]);
    }

    private function roleLabel(User $user): string
    {
        $role = $user->role?->value ?? (string) $user->role;

        return match ($role) {
            'advisor' => 'Assessor',
            'secretary' => 'Secretário',
            'mayor' => 'Prefeito',
            default => 'Admin',
        };
    }

    private function pushToAdmins(MunicipalityCoverageAlert $alert, Municipality $municipality): void
    {
        $admins = User::query()
            ->admins()
            ->active()
            ->get();

        foreach ($admins as $admin) {
            try {
                $this->webPush->sendToUser($admin, [
                    'title' => $alert->title,
                    'body' => $municipality->name . ' · ' . $alert->message,
                    'icon' => '/images/mascote-robo.jpg',
                    'url' => $alert->action_url ?: route('admin.municipalities.show', $municipality),
                    'tag' => 'municipality-coverage-alert-' . $municipality->id . '-' . $alert->event_type,
                    'requireInteraction' => $alert->severity === 'high',
                ]);
            } catch (\Throwable) {
            }
        }

        $alert->update(['last_pushed_at' => now()]);
    }
}
