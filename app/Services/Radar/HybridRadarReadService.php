<?php

namespace App\Services\Radar;

use App\Enums\ResourceOpportunityStatus;
use App\Models\FederalProgramAlert;
use App\Models\Municipality;
use App\Models\ResourceOpportunity;
use App\Models\ResourceOpportunityCycle;
use App\Models\ResourceReopenNotification;
use App\Models\ResourceUserSave;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use stdClass;

class HybridRadarReadService
{
    public function __construct(
        private readonly CanonicalResourceSyncService $canonicalSync,
    ) {}

    public function municipalityRadarPrograms(Municipality $municipality, bool $visibleOnly = true): Collection
    {
        $legacyPrograms = $this->legacyMunicipalityPrograms($municipality, $visibleOnly);

        if ($legacyPrograms->isNotEmpty()) {
            return $this->overlayCanonicalData($legacyPrograms);
        }

        if (!$this->canonicalEnabled()) {
            return collect();
        }

        return $this->canonicalMunicipalityPrograms($municipality, $visibleOnly);
    }

    public function enrichProgramsForUser(Collection $programs, User $user): Collection
    {
        $opportunityIds = $programs
            ->pluck('canonical_opportunity_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $cycleIds = $programs
            ->pluck('canonical_cycle_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $savedByOpportunity = ResourceUserSave::query()
            ->where('user_id', $user->id)
            ->where('municipality_id', $user->municipality_id)
            ->when($opportunityIds->isNotEmpty(), fn ($query) => $query->whereIn('resource_opportunity_id', $opportunityIds))
            ->get()
            ->groupBy('resource_opportunity_id');

        $reopenByOpportunity = ResourceReopenNotification::query()
            ->where('user_id', $user->id)
            ->where('municipality_id', $user->municipality_id)
            ->whereNull('cancelled_at')
            ->where('status', 'active')
            ->when($opportunityIds->isNotEmpty(), fn ($query) => $query->whereIn('resource_opportunity_id', $opportunityIds))
            ->get()
            ->groupBy('resource_opportunity_id');

        return $programs->map(function (FederalProgramAlert $program) use ($savedByOpportunity, $reopenByOpportunity, $cycleIds) {
            $opportunityId = (int) ($program->canonical_opportunity_id ?? 0);
            $cycleId = (int) ($program->canonical_cycle_id ?? 0);

            $save = $opportunityId > 0 ? $savedByOpportunity->get($opportunityId)?->first() : null;
            $reopen = $opportunityId > 0 ? $reopenByOpportunity->get($opportunityId)?->first() : null;

            $program->setAttribute('is_saved', $save !== null);
            $program->setAttribute('saved_resource_id', $save?->id);
            $program->setAttribute('is_reopen_notifying', $reopen !== null);
            $program->setAttribute('reopen_notification_id', $reopen?->id);
            $program->setAttribute('can_subscribe_reopen', $cycleId > 0 && in_array($program->status, [
                ResourceOpportunityStatus::ClosedRecently->value,
                ResourceOpportunityStatus::Archived->value,
                ResourceOpportunityStatus::Reopened->value,
            ], true));

            return $program;
        });
    }

    public function topMunicipalityPrograms(
        Municipality $municipality,
        int $limit = 5,
        ?float $minMatchScore = null,
        ?array $statuses = null,
        bool $visibleOnly = false,
    ): Collection {
        return $this->municipalityRadarPrograms($municipality, $visibleOnly)
            ->when($statuses !== null, fn (Collection $items) => $items->filter(
                fn (FederalProgramAlert $program) => in_array($program->status, $statuses, true)
            ))
            ->when($minMatchScore !== null, fn (Collection $items) => $items->filter(
                fn (FederalProgramAlert $program) => (float) ($program->match_score ?? 0) >= $minMatchScore
            ))
            ->sortByDesc(fn (FederalProgramAlert $program) => (float) ($program->match_score ?? 0))
            ->values()
            ->take($limit)
            ->values();
    }

    public function firstMunicipalityProgram(
        Municipality $municipality,
        ?float $minMatchScore = null,
        ?array $statuses = null,
        bool $visibleOnly = false,
    ): ?FederalProgramAlert {
        return $this->topMunicipalityPrograms(
            municipality: $municipality,
            limit: 1,
            minMatchScore: $minMatchScore,
            statuses: $statuses,
            visibleOnly: $visibleOnly,
        )->first();
    }

    public function adminStats(): array
    {
        if ($this->legacyExists()) {
            return [
                'total' => FederalProgramAlert::count(),
                'published' => FederalProgramAlert::where('status', ResourceOpportunityStatus::Published->value)->count(),
                'closing_soon' => FederalProgramAlert::where('status', ResourceOpportunityStatus::ClosingSoon->value)->count(),
                'monitoring' => FederalProgramAlert::where('status', ResourceOpportunityStatus::Monitoring->value)->count(),
                'closed_recently' => FederalProgramAlert::where('status', ResourceOpportunityStatus::ClosedRecently->value)->count(),
                'high_match' => FederalProgramAlert::where('match_score', '>=', 0.85)->count(),
                'last_sync' => Municipality::whereNotNull('data_last_synced_at')->max('data_last_synced_at'),
            ];
        }

        if (!$this->canonicalEnabled()) {
            return [
                'total' => 0,
                'published' => 0,
                'closing_soon' => 0,
                'monitoring' => 0,
                'closed_recently' => 0,
                'high_match' => 0,
                'last_sync' => Municipality::whereNotNull('data_last_synced_at')->max('data_last_synced_at'),
            ];
        }

        $cycles = ResourceOpportunityCycle::query()
            ->where('is_current', true)
            ->get();

        return [
            'total' => $cycles->count(),
            'published' => $cycles->where('status', ResourceOpportunityStatus::Published->value)->count(),
            'closing_soon' => $cycles->where('status', ResourceOpportunityStatus::ClosingSoon->value)->count(),
            'monitoring' => $cycles->where('status', ResourceOpportunityStatus::Monitoring->value)->count(),
            'closed_recently' => $cycles->where('status', ResourceOpportunityStatus::ClosedRecently->value)->count(),
            'high_match' => $cycles->filter(fn (ResourceOpportunityCycle $cycle) => (float) data_get($cycle->cycle_metadata, 'match_score', 0) >= 0.85)->count(),
            'last_sync' => Municipality::whereNotNull('data_last_synced_at')->max('data_last_synced_at'),
        ];
    }

    public function municipalityProgramStats(): Collection
    {
        if ($this->legacyExists()) {
            return FederalProgramAlert::selectRaw(
                "municipality_id, count(*) as total, avg(match_score) as avg_score,
                 sum(case when status in ('published', 'closing_soon', 'reopened') then 1 else 0 end) as active_count,
                 max(updated_at) as last_updated"
            )
                ->groupBy('municipality_id')
                ->get()
                ->keyBy('municipality_id');
        }

        if (!$this->canonicalEnabled()) {
            return collect();
        }

        return ResourceOpportunityCycle::query()
            ->where('is_current', true)
            ->get()
            ->filter(fn (ResourceOpportunityCycle $cycle) => filled(data_get($cycle->cycle_metadata, 'municipality_id')))
            ->groupBy(fn (ResourceOpportunityCycle $cycle) => (int) data_get($cycle->cycle_metadata, 'municipality_id'))
            ->map(function (Collection $cycles, int $municipalityId) {
                $avgScore = $cycles->avg(fn (ResourceOpportunityCycle $cycle) => (float) data_get($cycle->cycle_metadata, 'match_score', 0));
                $activeCount = $cycles->filter(fn (ResourceOpportunityCycle $cycle) => in_array($cycle->status, [
                    ResourceOpportunityStatus::Published->value,
                    ResourceOpportunityStatus::ClosingSoon->value,
                    ResourceOpportunityStatus::Reopened->value,
                ], true))->count();

                return (object) [
                    'municipality_id' => $municipalityId,
                    'total' => $cycles->count(),
                    'avg_score' => $avgScore,
                    'active_count' => $activeCount,
                    'last_updated' => $cycles->max('updated_at'),
                ];
            });
    }

    public function municipalityProgramsPayload(Municipality $municipality): Collection
    {
        return $this->municipalityRadarPrograms($municipality, visibleOnly: false)
            ->map(function (FederalProgramAlert $program) {
                return [
                    'id' => $program->id,
                    'program_name' => $program->program_name,
                    'ministry' => $program->ministry,
                    'area' => $program->area,
                    'match_score' => $program->match_score,
                    'match_reason' => $program->match_reason,
                    'status' => $program->status,
                    'max_value' => $program->max_value,
                    'deadline' => $program->deadline,
                    'source_url' => $program->source_url,
                    'source_platform' => $program->source_platform,
                    'source_key' => $program->source_key,
                    'source_name' => $program->resourceSource?->name
                        ?? $program->source_name
                        ?? $program->source_key
                        ?? $program->source_platform,
                    'read_mode' => $program->read_mode ?? ($program->exists ? 'legacy' : 'canonical'),
                ];
            })
            ->values();
    }

    public function resolveProgramForMunicipality(
        Municipality $municipality,
        ?int $legacyProgramId = null,
        ?int $canonicalCycleId = null,
        ?int $canonicalOpportunityId = null,
    ): ?FederalProgramAlert {
        if ($legacyProgramId) {
            $legacy = FederalProgramAlert::query()
                ->with(['resourceSource:id,key,name'])
                ->where('municipality_id', $municipality->id)
                ->find($legacyProgramId);

            if ($legacy instanceof FederalProgramAlert) {
                return $legacy;
            }
        }

        if ($canonicalCycleId && $this->canonicalEnabled()) {
            $cycle = ResourceOpportunityCycle::query()
                ->with(['opportunity.resourceSource'])
                ->find($canonicalCycleId);

            if ($cycle instanceof ResourceOpportunityCycle
                && (int) data_get($cycle->cycle_metadata, 'municipality_id') === (int) $municipality->id) {
                return $this->synthesizeLegacyAlert($municipality, $cycle);
            }
        }

        if ($canonicalOpportunityId && $this->canonicalEnabled()) {
            $opportunity = ResourceOpportunity::query()
                ->with(['resourceSource', 'currentCycles'])
                ->find($canonicalOpportunityId);

            if ($opportunity instanceof ResourceOpportunity) {
                $cycle = $opportunity->currentCycles
                    ->first(fn (ResourceOpportunityCycle $item) => (int) data_get($item->cycle_metadata, 'municipality_id') === (int) $municipality->id);

                if ($cycle instanceof ResourceOpportunityCycle) {
                    return $this->synthesizeLegacyAlert($municipality, $cycle);
                }
            }
        }

        return null;
    }

    public function resolveCanonicalEntitiesForMunicipality(
        Municipality $municipality,
        ?int $legacyProgramId = null,
        ?int $canonicalCycleId = null,
        ?int $canonicalOpportunityId = null,
    ): ?array {
        if ($canonicalCycleId && $this->canonicalEnabled()) {
            $cycle = ResourceOpportunityCycle::query()
                ->with(['opportunity.resourceSource'])
                ->find($canonicalCycleId);

            if ($cycle instanceof ResourceOpportunityCycle
                && (int) data_get($cycle->cycle_metadata, 'municipality_id') === (int) $municipality->id) {
                return [
                    'opportunity' => $cycle->opportunity,
                    'cycle' => $cycle,
                    'program' => $this->synthesizeLegacyAlert($municipality, $cycle),
                ];
            }
        }

        if ($canonicalOpportunityId && $this->canonicalEnabled()) {
            $opportunity = ResourceOpportunity::query()
                ->with(['resourceSource', 'currentCycles'])
                ->find($canonicalOpportunityId);

            if ($opportunity instanceof ResourceOpportunity) {
                $cycle = $opportunity->currentCycles
                    ->first(fn (ResourceOpportunityCycle $item) => (int) data_get($item->cycle_metadata, 'municipality_id') === (int) $municipality->id)
                    ?? $opportunity->currentCycles->first();

                if ($cycle instanceof ResourceOpportunityCycle) {
                    return [
                        'opportunity' => $opportunity,
                        'cycle' => $cycle,
                        'program' => $this->synthesizeLegacyAlert($municipality, $cycle),
                    ];
                }
            }
        }

        if ($legacyProgramId) {
            $legacy = FederalProgramAlert::query()
                ->where('municipality_id', $municipality->id)
                ->find($legacyProgramId);

            if ($legacy instanceof FederalProgramAlert) {
                $canonical = $this->findCanonicalEntitiesForLegacy($legacy, $municipality)
                    ?? $this->syncLegacyIntoCanonical($legacy, $municipality);

                if ($canonical !== null) {
                    return $canonical;
                }
            }
        }

        return null;
    }

    public function canonicalDetailPayloadForMunicipality(
        Municipality $municipality,
        ?int $legacyProgramId = null,
        ?int $canonicalCycleId = null,
        ?int $canonicalOpportunityId = null,
        ?User $user = null,
    ): ?array {
        try {
            $resolved = $this->resolveCanonicalEntitiesForMunicipality(
                municipality: $municipality,
                legacyProgramId: $legacyProgramId,
                canonicalCycleId: $canonicalCycleId,
                canonicalOpportunityId: $canonicalOpportunityId,
            );
        } catch (\Throwable $e) {
            $resolved = null;
        }

        if (!is_array($resolved) && $legacyProgramId) {
            $legacyProgram = FederalProgramAlert::query()
                ->with(['resourceSource:id,key,name,capture_method,refresh_frequency'])
                ->where('municipality_id', $municipality->id)
                ->find($legacyProgramId);

            if ($legacyProgram instanceof FederalProgramAlert) {
                return $this->legacyDetailPayload($legacyProgram);
            }
        }

        if (!is_array($resolved)) {
            return null;
        }

        /** @var ResourceOpportunity $opportunity */
        $opportunity = $resolved['opportunity'];
        /** @var ResourceOpportunityCycle $cycle */
        $cycle = $resolved['cycle'];
        /** @var FederalProgramAlert $program */
        $program = $resolved['program'];

        $source = $opportunity->resourceSource;
        $isSaved = false;
        $isReopenNotifying = false;

        if ($user instanceof User) {
            $isSaved = ResourceUserSave::query()
                ->where('user_id', $user->id)
                ->where('municipality_id', $user->municipality_id)
                ->where('resource_opportunity_id', $opportunity->id)
                ->exists();

            $isReopenNotifying = ResourceReopenNotification::query()
                ->where('user_id', $user->id)
                ->where('municipality_id', $user->municipality_id)
                ->where('resource_opportunity_id', $opportunity->id)
                ->whereNull('cancelled_at')
                ->where('status', 'active')
                ->exists();
        }

        return [
            'legacy_program_id' => $program->exists ? $program->id : null,
            'canonical_opportunity_id' => $opportunity->id,
            'canonical_cycle_id' => $cycle->id,
            'title' => $program->program_name,
            'short_title' => $program->short_title,
            'official_title' => $opportunity->official_title,
            'status' => ResourceOpportunityStatus::normalize($program->status, $program->deadline),
            'status_label' => ResourceOpportunityStatus::labelFor($program->status, $program->deadline),
            'match_score' => $program->match_score,
            'match_percentage' => $program->match_score !== null ? (int) round(((float) $program->match_score) * 100) : null,
            'match_reason' => $program->match_reason,
            'viability_level' => $program->viability_level,
            'viability_reason' => $program->viability_reason,
            'funding_type' => $program->funding_type,
            'resource_scope' => $opportunity->resource_scope,
            'resource_type' => $opportunity->resource_type,
            'estimated_size' => $cycle->estimated_size ?: $opportunity->estimated_size,
            'counterpart_percentage' => $cycle->counterpart_percentage,
            'total_value' => $cycle->total_value,
            'min_value' => $cycle->min_value,
            'area' => $program->area,
            'ministry' => $program->ministry,
            'issuing_body' => $opportunity->issuing_body,
            'source_name' => $source?->name ?? $program->source_name ?? $program->source_key ?? $program->source_platform,
            'source_key' => $source?->key ?? $program->source_key,
            'capture_method' => $source?->capture_method,
            'refresh_frequency' => $source?->refresh_frequency,
            'source_url' => $cycle->notice_url ?: $opportunity->source_url,
            'application_url' => $cycle->application_url,
            'publication_reference' => $cycle->publication_reference,
            'published_at' => $cycle->published_at?->toDateString(),
            'opens_at' => $cycle->opens_at?->toDateString(),
            'deadline_at' => $cycle->deadline_at?->toDateString(),
            'closed_at' => $cycle->closed_at?->toDateString(),
            'closed_visibility_until' => $cycle->closed_visibility_until?->toDateString(),
            'description' => $opportunity->description,
            'summary' => $opportunity->summary,
            'eligibility_rules' => $opportunity->eligibility_rules ?? [],
            'documentation_requirements' => $opportunity->documentation_requirements ?? [],
            'compatibility_factors' => $opportunity->compatibility_factors_template ?? [],
            'viability_factors' => $opportunity->viability_factors_template ?? [],
            'thematic_tags' => $opportunity->thematic_tags ?? [],
            'source_metadata' => $opportunity->source_metadata ?? [],
            'read_mode' => $program->read_mode ?? ($program->exists ? 'legacy' : 'canonical'),
            'is_saved' => $isSaved,
            'is_reopen_notifying' => $isReopenNotifying,
            'can_subscribe_reopen' => in_array(ResourceOpportunityStatus::normalize($program->status, $program->deadline), [
                ResourceOpportunityStatus::ClosedRecently->value,
                ResourceOpportunityStatus::Archived->value,
                ResourceOpportunityStatus::Reopened->value,
            ], true),
            'supports_canonical_actions' => true,
        ];
    }

    private function legacyDetailPayload(FederalProgramAlert $program): array
    {
        $source = $program->resourceSource;
        $status = ResourceOpportunityStatus::normalize($program->status, $program->deadline);

        return [
            'legacy_program_id' => $program->id,
            'canonical_opportunity_id' => null,
            'canonical_cycle_id' => null,
            'title' => $program->program_name,
            'short_title' => $program->short_title,
            'official_title' => $program->program_name,
            'status' => $status,
            'status_label' => ResourceOpportunityStatus::labelFor($program->status, $program->deadline),
            'match_score' => $program->match_score,
            'match_percentage' => $program->match_score !== null ? (int) round(((float) $program->match_score) * 100) : null,
            'match_reason' => $program->match_reason,
            'viability_level' => $program->viability_level,
            'viability_reason' => $program->viability_reason,
            'funding_type' => $program->funding_type,
            'resource_scope' => $program->resource_scope,
            'resource_type' => null,
            'estimated_size' => $program->estimated_size,
            'counterpart_percentage' => $program->counterpart_percentage,
            'total_value' => $program->max_value,
            'min_value' => $program->min_value,
            'area' => $program->area,
            'ministry' => $program->ministry,
            'issuing_body' => $program->ministry,
            'source_name' => $source?->name ?? $program->source_key ?? $program->source_platform,
            'source_key' => $source?->key ?? $program->source_key ?? $program->source_platform,
            'capture_method' => $source?->capture_method ?? $program->capture_method,
            'refresh_frequency' => $source?->refresh_frequency,
            'source_url' => $program->source_url,
            'application_url' => $program->source_url,
            'publication_reference' => $program->program_code,
            'published_at' => $program->published_at?->toDateString(),
            'opens_at' => $program->open_date?->toDateString(),
            'deadline_at' => $program->deadline?->toDateString(),
            'closed_at' => $program->closed_at?->toDateString(),
            'closed_visibility_until' => $program->closed_visibility_until?->toDateString(),
            'description' => $program->description,
            'summary' => $program->description,
            'eligibility_rules' => $program->eligibility_criteria ?? [],
            'documentation_requirements' => $program->documentation_requirements ?? [],
            'compatibility_factors' => $program->compatibility_factors ?? [],
            'viability_factors' => $program->viability_factors ?? [],
            'thematic_tags' => array_values(array_filter([$program->area, $program->funding_type, $program->resource_scope])),
            'source_metadata' => $program->source_metadata ?? [],
            'read_mode' => 'legacy',
            'is_saved' => false,
            'is_reopen_notifying' => false,
            'can_subscribe_reopen' => false,
            'supports_canonical_actions' => false,
        ];
    }

    private function legacyMunicipalityPrograms(Municipality $municipality, bool $visibleOnly): Collection
    {
        $query = FederalProgramAlert::query()
            ->where('municipality_id', $municipality->id)
            ->with(['resourceSource:id,key,name'])
            ->orderByDesc('match_score')
            ->orderBy('deadline', 'DESC');

        if ($visibleOnly) {
            $query->visibleInRadar();
        }

        return $query->get();
    }

    private function overlayCanonicalData(Collection $legacyPrograms): Collection
    {
        if (!$this->canonicalEnabled() || $legacyPrograms->isEmpty()) {
            return $legacyPrograms;
        }

        $opportunitiesByKey = ResourceOpportunity::query()
            ->with(['resourceSource:id,key,name', 'currentCycles'])
            ->whereIn('canonical_key', $legacyPrograms->map(fn (FederalProgramAlert $alert) => $this->canonicalSync->canonicalKeyForAlert($alert))->unique()->values())
            ->get()
            ->keyBy('canonical_key');

        return $legacyPrograms->map(function (FederalProgramAlert $alert) use ($opportunitiesByKey) {
            $canonical = $opportunitiesByKey->get($this->canonicalSync->canonicalKeyForAlert($alert));

            if (!$canonical instanceof ResourceOpportunity) {
                $alert->setAttribute('read_mode', 'legacy');
                return $alert;
            }

            $cycle = $canonical->currentCycles
                ->first(fn (ResourceOpportunityCycle $item) => (int) data_get($item->cycle_metadata, 'legacy_alert_id') === (int) $alert->id)
                ?? $canonical->currentCycles->first();

            if ($canonical->resourceSource) {
                $alert->setRelation('resourceSource', $canonical->resourceSource);
            }

            $alert->setAttribute('source_name', $canonical->resourceSource?->name ?? $alert->source_key ?? $alert->source_platform);
            $alert->setAttribute('canonical_opportunity_id', $canonical->id);
            $alert->setAttribute('canonical_cycle_id', $cycle?->id);
            $alert->setAttribute('read_mode', 'hybrid');

            if ($cycle !== null) {
                $alert->status = $cycle->status ?: $alert->status;
                $alert->deadline = $cycle->deadline_at ?: $alert->deadline;
                $alert->closed_at = $cycle->closed_at ?: $alert->closed_at;
                $alert->closed_visibility_until = $cycle->closed_visibility_until ?: $alert->closed_visibility_until;
            }

            return $alert;
        });
    }

    private function findCanonicalEntitiesForLegacy(FederalProgramAlert $alert, Municipality $municipality): ?array
    {
        if (!$this->canonicalEnabled()) {
            return null;
        }

        $canonicalKey = $this->canonicalSync->canonicalKeyForAlert($alert);

        $opportunity = ResourceOpportunity::query()
            ->with(['resourceSource', 'currentCycles'])
            ->where('canonical_key', $canonicalKey)
            ->first();

        if (!$opportunity instanceof ResourceOpportunity) {
            return null;
        }

        $cycle = $opportunity->currentCycles
            ->first(fn (ResourceOpportunityCycle $item) => (int) data_get($item->cycle_metadata, 'legacy_alert_id') === (int) $alert->id)
            ?? $opportunity->currentCycles
                ->first(fn (ResourceOpportunityCycle $item) => (int) data_get($item->cycle_metadata, 'municipality_id') === (int) $municipality->id)
            ?? $opportunity->currentCycles->first();

        if (!$cycle instanceof ResourceOpportunityCycle) {
            return null;
        }

        return [
            'opportunity' => $opportunity,
            'cycle' => $cycle,
            'program' => $this->synthesizeLegacyAlert($municipality, $cycle),
        ];
    }

    private function syncLegacyIntoCanonical(FederalProgramAlert $alert, Municipality $municipality): ?array
    {
        $synced = $this->canonicalSync->syncFromAlert($alert);

        if (!is_array($synced)) {
            return null;
        }

        $opportunity = $synced['opportunity'] ?? null;
        $cycle = $synced['cycle'] ?? null;

        if (!$opportunity instanceof ResourceOpportunity || !$cycle instanceof ResourceOpportunityCycle) {
            return null;
        }

        $opportunity->loadMissing('resourceSource');

        return [
            'opportunity' => $opportunity,
            'cycle' => $cycle,
            'program' => $this->synthesizeLegacyAlert($municipality, $cycle),
        ];
    }

    private function canonicalMunicipalityPrograms(Municipality $municipality, bool $visibleOnly): Collection
    {
        $cycles = ResourceOpportunityCycle::query()
            ->with(['opportunity.resourceSource'])
            ->where('is_current', true)
            ->where('cycle_metadata->municipality_id', $municipality->id)
            ->get();

        if ($visibleOnly) {
            $cycles = $cycles->filter(fn (ResourceOpportunityCycle $cycle) => in_array(
                $cycle->status,
                ResourceOpportunityStatus::userVisible(),
                true
            ));
        }

        return $cycles
            ->sortByDesc(fn (ResourceOpportunityCycle $cycle) => (float) data_get($cycle->cycle_metadata, 'match_score', 0))
            ->sortByDesc(fn (ResourceOpportunityCycle $cycle) => optional($cycle->deadline_at)?->timestamp ?? 0)
            ->map(fn (ResourceOpportunityCycle $cycle) => $this->synthesizeLegacyAlert($municipality, $cycle))
            ->values();
    }

    private function synthesizeLegacyAlert(Municipality $municipality, ResourceOpportunityCycle $cycle): FederalProgramAlert
    {
        $opportunity = $cycle->opportunity;
        $metadata = is_array($cycle->cycle_metadata) ? $cycle->cycle_metadata : [];

        $program = new FederalProgramAlert([
            'municipality_id' => $municipality->id,
            'resource_source_id' => $opportunity?->resource_source_id,
            'program_name' => $opportunity?->title,
            'short_title' => $opportunity?->short_title,
            'ministry' => $opportunity?->issuing_body,
            'program_code' => $cycle->publication_reference,
            'description' => $opportunity?->description,
            'area' => $opportunity?->thematic_area,
            'max_value' => $cycle->total_value,
            'min_value' => $cycle->min_value,
            'funding_type' => $opportunity?->funding_type,
            'estimated_size' => $cycle->estimated_size ?: $opportunity?->estimated_size,
            'counterpart_percentage' => $cycle->counterpart_percentage,
            'eligibility_criteria' => $opportunity?->eligibility_rules,
            'documentation_requirements' => $opportunity?->documentation_requirements,
            'open_date' => $cycle->opens_at,
            'deadline' => $cycle->deadline_at,
            'status' => $cycle->status,
            'match_score' => data_get($metadata, 'match_score'),
            'match_reason' => data_get($metadata, 'match_reason'),
            'compatibility_factors' => $opportunity?->compatibility_factors_template,
            'viability_level' => data_get($metadata, 'viability_level'),
            'viability_reason' => data_get($metadata, 'viability_reason'),
            'viability_factors' => $opportunity?->viability_factors_template,
            'source_url' => $cycle->notice_url ?: $opportunity?->source_url,
            'source_platform' => $opportunity?->resourceSource?->key,
            'source_key' => $opportunity?->resourceSource?->key,
            'capture_method' => $opportunity?->resourceSource?->capture_method,
            'resource_scope' => $opportunity?->resource_scope,
            'curation_status' => $opportunity?->curation_status,
            'published_at' => $cycle->published_at,
            'closed_at' => $cycle->closed_at,
            'closed_visibility_until' => $cycle->closed_visibility_until,
            'source_metadata' => $opportunity?->source_metadata,
        ]);

        $program->exists = false;
        $program->setAttribute('read_mode', 'canonical');
        $program->setAttribute('source_name', $opportunity?->resourceSource?->name);
        $program->setAttribute('canonical_opportunity_id', $opportunity?->id);
        $program->setAttribute('canonical_cycle_id', $cycle->id);

        if ($opportunity?->resourceSource) {
            $program->setRelation('resourceSource', $opportunity->resourceSource);
        }

        return $program;
    }

    private function canonicalEnabled(): bool
    {
        return $this->canonicalSync->isEnabled();
    }

    private function legacyExists(): bool
    {
        return Schema::hasTable('federal_program_alerts') && FederalProgramAlert::query()->exists();
    }
}
