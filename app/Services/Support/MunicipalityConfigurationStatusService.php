<?php

namespace App\Services\Support;

use App\Models\MentionKeyword;
use App\Models\Municipality;
use App\Models\User;
use Illuminate\Support\Collection;

class MunicipalityConfigurationStatusService
{
    public function summarize(Municipality $municipality): array
    {
        $settings = is_array($municipality->settings) ? $municipality->settings : [];
        $mayor = $municipality->relationLoaded('mayor') ? $municipality->getRelation('mayor') : $municipality->mayor()->first();
        $preferredName = $mayor instanceof User ? (string) data_get($mayor->preferences, 'preferred_name', '') : '';
        $praHojeTime = $mayor instanceof User ? (string) data_get($mayor->preferences, 'pra_hoje.delivery_time', '') : '';
        $praHojeEnabled = $mayor instanceof User ? (bool) data_get($mayor->preferences, 'pra_hoje.enabled', true) : false;

        $channels = collect((array) data_get($settings, 'communication.channels', []));
        $activeChannels = $channels
            ->filter(fn ($channel) => !empty($channel['active']))
            ->keys()
            ->values();

        $monitoringTerms = $this->splitList((string) data_get($settings, 'communication.monitoring.terms_text', ''));
        $monitoringPortals = $this->splitList((string) data_get($settings, 'communication.monitoring.portals', ''));
        $keywordsTotal = MentionKeyword::query()
            ->where('municipality_id', $municipality->id)
            ->where('is_active', true)
            ->count();

        $baseInstitutionalReady =
            !empty(data_get($settings, 'municipality_profile.mandate.mayor_full_name'))
            && !empty(data_get($settings, 'municipality_profile.mandate.party'))
            && !empty(data_get($settings, 'municipality_profile.mandate.term_start_date'))
            && !empty(data_get($settings, 'municipality_profile.mandate.term_end_date'));

        $strategicProfileReady = !empty($municipality->voice_profile);
        $politicalMapReady = !empty($municipality->political_map);
        $communicationReady = $activeChannels->isNotEmpty() && !empty($monitoringTerms);
        $notificationsReady = $mayor instanceof User && $praHojeTime !== '';
        $resolveAiReady = $this->resolveAiReady($municipality);
        $projectBankReady = !empty(data_get($settings, 'project_bank.library_size'))
            || !empty(data_get($settings, 'project_bank.bootstrapped_at'))
            || !empty(data_get($settings, 'project_bank.last_curated_at'));

        $checklist = [
            'base_institucional' => ['label' => 'Base institucional', 'ready' => $baseInstitutionalReady],
            'perfil_estrategico' => ['label' => 'Perfil estratégico', 'ready' => $strategicProfileReady],
            'mapa_politico' => ['label' => 'Mapa político', 'ready' => $politicalMapReady],
            'comunicacao' => ['label' => 'Comunicação transversal', 'ready' => $communicationReady],
            'notificacoes' => ['label' => 'Notificações e Pra hoje', 'ready' => $notificationsReady],
            'resolve_ai' => ['label' => 'Resolve ai operacional', 'ready' => $resolveAiReady],
            'banco_projetos' => ['label' => 'Banco de Projetos', 'ready' => $projectBankReady],
        ];

        $issues = [];
        if (!$baseInstitutionalReady) {
            $issues[] = 'base institucional incompleta';
        }
        if (!$strategicProfileReady) {
            $issues[] = 'perfil estratégico do prefeito ausente';
        }
        if (!$politicalMapReady) {
            $issues[] = 'mapa político pendente';
        }
        if (!$communicationReady) {
            $issues[] = 'comunicação sem canais ativos ou termos de monitoramento';
        }
        if (!$notificationsReady) {
            $issues[] = 'Pra hoje sem horário configurado';
        }
        if (!$resolveAiReady) {
            $issues[] = 'Resolve ai sem base mínima operacional';
        }

        $score = (int) round(
            collect($checklist)->filter(fn ($entry) => $entry['ready'])->count() / max(1, count($checklist)) * 100
        );

        return [
            'municipality_id' => $municipality->id,
            'municipality_name' => $municipality->name,
            'preferred_name' => $preferredName !== '' ? $preferredName : ($mayor?->name ?? ''),
            'mayor_name' => $mayor?->name,
            'pra_hoje_enabled' => $praHojeEnabled,
            'pra_hoje_time' => $praHojeTime !== '' ? $praHojeTime : null,
            'monitoring_terms' => $monitoringTerms,
            'monitoring_portals' => $monitoringPortals,
            'monitoring_keywords_total' => $keywordsTotal,
            'active_channels' => $activeChannels->all(),
            'responsible_user_id' => data_get($settings, 'communication.monitoring.responsible_user_id'),
            'communication_visual_style' => (string) data_get($settings, 'communication.visual_identity.style', ''),
            'communication_palette' => (string) data_get($settings, 'communication.visual_identity.palette', ''),
            'checklist' => $checklist,
            'score' => $score,
            'issues' => $issues,
            'status' => $score >= 85 ? 'ok' : ($score >= 55 ? 'warning' : 'critical'),
            'summary_label' => $score >= 85 ? 'Pronto para operar' : ($score >= 55 ? 'Parcialmente configurado' : 'Configuração crítica'),
        ];
    }

    public function summarizeCollection(iterable $municipalities): Collection
    {
        return collect($municipalities)->map(fn (Municipality $municipality) => $this->summarize($municipality));
    }

    public function aggregate(Collection $summaries): array
    {
        return [
            'total' => $summaries->count(),
            'ready_total' => $summaries->where('status', 'ok')->count(),
            'warning_total' => $summaries->where('status', 'warning')->count(),
            'critical_total' => $summaries->where('status', 'critical')->count(),
            'average_score' => (int) round($summaries->avg('score') ?? 0),
            'mentions_ready_total' => $summaries->filter(fn ($entry) => !empty($entry['monitoring_terms']) && ($entry['monitoring_keywords_total'] ?? 0) > 0)->count(),
            'pra_hoje_ready_total' => $summaries->filter(fn ($entry) => !empty($entry['pra_hoje_time']) && ($entry['pra_hoje_enabled'] ?? false))->count(),
        ];
    }

    private function splitList(string $value): array
    {
        return collect(preg_split('/[\n,;]+/', $value))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->unique(fn ($item) => mb_strtolower($item))
            ->values()
            ->all();
    }

    private function resolveAiReady(Municipality $municipality): bool
    {
        $areasReady = $municipality->contactAreas()
            ->where('active', true)
            ->where(function ($query) {
                $query->whereNotNull('notification_email')->orWhereNotNull('email');
            })
            ->count();

        $localitiesReady = $municipality->localities()->where('active', true)->count();

        return $areasReady > 0 && $localitiesReady > 0;
    }
}
