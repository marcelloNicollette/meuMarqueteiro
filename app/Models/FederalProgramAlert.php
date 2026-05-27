<?php

namespace App\Models;

use App\Enums\ResourceOpportunityStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FederalProgramAlert extends Model
{
    protected $table = 'federal_program_alerts';

    protected $fillable = [
        'municipality_id',
        'resource_source_id',
        'program_name',
        'short_title',
        'ministry',
        'program_code',
        'description',
        'area',
        'max_value',
        'min_value',
        'funding_type',
        'estimated_size',
        'counterpart_percentage',
        'eligibility_criteria',
        'documentation_requirements',
        'open_date',
        'deadline',
        'status',
        'applied_at',
        'ai_matched',
        'match_score',
        'match_reason',
        'compatibility_factors',
        'viability_level',
        'viability_reason',
        'viability_factors',
        'source_url',
        'source_platform',
        'source_key',
        'capture_method',
        'resource_scope',
        'curation_status',
        'published_at',
        'closed_at',
        'archived_at',
        'closed_visibility_until',
        'source_metadata',
    ];

    protected function casts(): array
    {
        return [
            'eligibility_criteria' => 'array',
            'documentation_requirements' => 'array',
            'open_date'            => 'date',
            'deadline'             => 'date',
            'applied_at'           => 'datetime',
            'published_at'         => 'datetime',
            'closed_at'            => 'datetime',
            'archived_at'          => 'datetime',
            'closed_visibility_until' => 'datetime',
            'max_value'            => 'decimal:2',
            'min_value'            => 'decimal:2',
            'counterpart_percentage' => 'decimal:2',
            'match_score'          => 'decimal:2',
            'ai_matched'           => 'boolean',
            'compatibility_factors' => 'array',
            'viability_factors'    => 'array',
            'source_metadata'      => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $alert): void {
            $alert->status = ResourceOpportunityStatus::normalize(
                $alert->status,
                $alert->deadline,
            );

            if (in_array($alert->status, [
                ResourceOpportunityStatus::Published->value,
                ResourceOpportunityStatus::ClosingSoon->value,
                ResourceOpportunityStatus::Monitoring->value,
                ResourceOpportunityStatus::Reopened->value,
            ], true) && $alert->published_at === null) {
                $alert->published_at = now();
            }

            if ($alert->status === ResourceOpportunityStatus::ClosedRecently->value) {
                $alert->closed_at ??= $alert->deadline ?? now();
                $alert->closed_visibility_until ??= $alert->closed_at?->copy()->addDays(60);
            }

            if ($alert->status === ResourceOpportunityStatus::Archived->value) {
                $alert->archived_at ??= now();
            }
        });
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function resourceSource(): BelongsTo
    {
        return $this->belongsTo(ResourceSource::class);
    }

    public function scopeVisibleInRadar(Builder $query): Builder
    {
        return $query->whereIn('status', ResourceOpportunityStatus::userVisible());
    }

    public function scopeActionable(Builder $query): Builder
    {
        return $query->whereIn('status', ResourceOpportunityStatus::actionableForProjects());
    }

    public function normalizedStatus(): ResourceOpportunityStatus
    {
        return ResourceOpportunityStatus::tryFromNormalized($this->status, $this->deadline);
    }

    public function statusLabel(): string
    {
        return $this->normalizedStatus()->label();
    }

    public function statusTone(): string
    {
        return $this->normalizedStatus()->tone();
    }
}
