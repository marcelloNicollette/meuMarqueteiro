<?php

namespace App\Services\Radar;

use App\Models\FederalProgramAlert;
use App\Models\ResourceOpportunity;
use App\Models\ResourceOpportunityCycle;
use App\Models\ResourceSource;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CanonicalResourceSyncService
{
    public function syncFromAlert(FederalProgramAlert $alert): ?array
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $canonicalKey = $this->canonicalKeyForAlert($alert);
        $fingerprint = $this->sourceFingerprintForAlert($alert);
        $resourceSourceId = $this->resolveResourceSourceId($alert);

        $opportunity = ResourceOpportunity::query()->firstOrNew([
            'canonical_key' => $canonicalKey,
        ]);

        $opportunity->fill([
            'resource_source_id' => $resourceSourceId,
            'source_fingerprint' => $opportunity->source_fingerprint ?: $fingerprint,
            'title' => $alert->program_name,
            'short_title' => $alert->short_title,
            'official_title' => $alert->program_name,
            'issuing_body' => $alert->ministry,
            'thematic_area' => $alert->area,
            'resource_type' => $this->resourceTypeFromAlert($alert),
            'funding_type' => $alert->funding_type,
            'resource_scope' => $alert->resource_scope,
            'summary' => Str::limit((string) $alert->description, 240, ''),
            'description' => $alert->description,
            'thematic_tags' => array_values(array_filter([$alert->area, $alert->funding_type, $alert->resource_scope])),
            'eligibility_rules' => $alert->eligibility_criteria,
            'documentation_requirements' => $alert->documentation_requirements,
            'counterpart_rules' => [
                'percentage' => $alert->counterpart_percentage,
            ],
            'estimated_size' => $alert->estimated_size,
            'curation_status' => $alert->curation_status ?? 'auto_published',
            'latest_status' => $alert->status,
            'source_url' => $alert->source_url,
            'compatibility_factors_template' => $alert->compatibility_factors,
            'viability_factors_template' => $alert->viability_factors,
            'source_metadata' => $alert->source_metadata,
            'first_seen_at' => $opportunity->first_seen_at ?? $alert->created_at ?? now(),
            'last_seen_at' => now(),
            'last_published_at' => $alert->published_at,
        ]);
        $opportunity->save();

        $cycleKey = $this->cycleKeyForAlert($alert, $canonicalKey);

        $cycle = ResourceOpportunityCycle::query()->firstOrNew([
            'resource_opportunity_id' => $opportunity->id,
            'external_cycle_key' => $cycleKey,
        ]);

        if (!$cycle->exists) {
            $reopenedFrom = $this->resolveReopenedFromCycle($opportunity->id, $alert->status);
            $cycle->reopened_from_cycle_id = $reopenedFrom?->id;
        }

        $cycle->fill([
            'publication_reference' => $alert->program_code,
            'status' => $alert->status,
            'is_current' => !in_array($alert->status, ['archived'], true),
            'notice_url' => $alert->source_url,
            'application_url' => $alert->source_url,
            'published_at' => $alert->published_at,
            'opens_at' => $alert->open_date,
            'deadline_at' => $alert->deadline,
            'closed_at' => $alert->closed_at,
            'closed_visibility_until' => $alert->closed_visibility_until,
            'total_value' => $alert->max_value,
            'min_value' => $alert->min_value,
            'counterpart_percentage' => $alert->counterpart_percentage,
            'estimated_size' => $alert->estimated_size,
            'cycle_metadata' => array_filter([
                'legacy_alert_id' => $alert->id,
                'municipality_id' => $alert->municipality_id,
                'match_score' => $alert->match_score,
                'match_reason' => $alert->match_reason,
                'viability_level' => $alert->viability_level,
                'viability_reason' => $alert->viability_reason,
                'source_platform' => $alert->source_platform,
                'source_key' => $alert->source_key,
            ], fn ($value) => $value !== null),
        ]);
        $cycle->save();

        ResourceOpportunityCycle::query()
            ->where('resource_opportunity_id', $opportunity->id)
            ->where('id', '!=', $cycle->id)
            ->update(['is_current' => false]);

        return [
            'opportunity' => $opportunity,
            'cycle' => $cycle,
        ];
    }

    public function isEnabled(): bool
    {
        return Schema::hasTable('resource_opportunities')
            && Schema::hasTable('resource_opportunity_cycles');
    }

    public function canonicalKeyForAlert(FederalProgramAlert $alert): string
    {
        if (filled($alert->program_code)) {
            return implode(':', [
                'resource',
                $alert->source_key ?: $alert->source_platform ?: 'sem_fonte',
                Str::slug((string) $alert->program_code, '_'),
            ]);
        }

        return implode(':', [
            'resource',
            $alert->source_key ?: $alert->source_platform ?: 'sem_fonte',
            Str::slug((string) $alert->program_name, '_'),
            Str::slug((string) $alert->ministry, '_'),
        ]);
    }

    public function sourceFingerprintForAlert(FederalProgramAlert $alert): string
    {
        $base = implode('|', array_filter([
            $alert->source_key ?: $alert->source_platform,
            $alert->program_code,
            $alert->source_url,
            Str::slug((string) $alert->program_name, '_'),
        ]));

        return hash('sha256', $base);
    }

    public function cycleKeyForAlert(FederalProgramAlert $alert, string $canonicalKey): string
    {
        if (filled($alert->program_code) && filled($alert->deadline)) {
            return implode(':', [
                $canonicalKey,
                'deadline',
                $alert->deadline?->format('Ymd'),
            ]);
        }

        $base = implode('|', [
            $canonicalKey,
            optional($alert->open_date)->format('Y-m-d'),
            optional($alert->deadline)->format('Y-m-d'),
            $alert->status,
            $alert->source_url,
        ]);

        return hash('sha256', $base);
    }

    private function resolveReopenedFromCycle(int $opportunityId, ?string $status): ?ResourceOpportunityCycle
    {
        if ($status !== 'reopened') {
            return null;
        }

        return ResourceOpportunityCycle::query()
            ->where('resource_opportunity_id', $opportunityId)
            ->whereIn('status', ['closed_recently', 'archived'])
            ->latest('closed_at')
            ->first();
    }

    private function resourceTypeFromAlert(FederalProgramAlert $alert): ?string
    {
        return match ($alert->funding_type) {
            'transferencia' => 'transfer',
            'convenio' => 'convenio',
            'credito' => 'credit',
            'emenda' => 'emenda',
            default => null,
        };
    }

    private function resolveResourceSourceId(FederalProgramAlert $alert): int
    {
        if ((int) $alert->resource_source_id > 0) {
            return (int) $alert->resource_source_id;
        }

        $sourceKey = $this->normalizeSourceKey(
            $alert->source_key ?: $alert->source_platform
        );

        $resourceSourceId = ResourceSource::query()
            ->where('key', $sourceKey)
            ->value('id');

        if ($resourceSourceId) {
            return (int) $resourceSourceId;
        }

        $fallbackId = ResourceSource::query()
            ->where('key', 'portal_transparencia')
            ->value('id');

        if ($fallbackId) {
            return (int) $fallbackId;
        }

        throw new \RuntimeException('Nenhuma fonte de recurso cadastrada para espelhar a oportunidade canônica.');
    }

    private function normalizeSourceKey(?string $value): string
    {
        $normalized = Str::of((string) ($value ?? ''))
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->value();

        return match ($normalized) {
            '', 'portal', 'transparencia', 'portal_da_transparencia', 'portal_transparencia_federal', 'transparencia_convenio' => 'portal_transparencia',
            'transparencia_emenda', 'emenda_parlamentar', 'emendas', 'emendas_parlamentar' => 'emendas_parlamentares',
            'diario_oficial', 'dou' => 'diario_oficial_uniao',
            default => $normalized ?: 'portal_transparencia',
        };
    }
}
