<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResourceOpportunity extends Model
{
    use HasFactory;

    protected $fillable = [
        'resource_source_id',
        'canonical_key',
        'source_fingerprint',
        'title',
        'short_title',
        'official_title',
        'issuing_body',
        'thematic_area',
        'resource_type',
        'funding_type',
        'resource_scope',
        'summary',
        'description',
        'thematic_tags',
        'eligibility_rules',
        'documentation_requirements',
        'counterpart_rules',
        'estimated_size',
        'curation_status',
        'latest_status',
        'source_url',
        'compatibility_factors_template',
        'viability_factors_template',
        'source_metadata',
        'first_seen_at',
        'last_seen_at',
        'last_published_at',
    ];

    protected function casts(): array
    {
        return [
            'thematic_tags' => 'array',
            'eligibility_rules' => 'array',
            'documentation_requirements' => 'array',
            'counterpart_rules' => 'array',
            'compatibility_factors_template' => 'array',
            'viability_factors_template' => 'array',
            'source_metadata' => 'array',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'last_published_at' => 'datetime',
        ];
    }

    public function resourceSource(): BelongsTo
    {
        return $this->belongsTo(ResourceSource::class);
    }

    public function cycles(): HasMany
    {
        return $this->hasMany(ResourceOpportunityCycle::class)->latest('deadline_at');
    }

    public function currentCycles(): HasMany
    {
        return $this->hasMany(ResourceOpportunityCycle::class)
            ->where('is_current', true)
            ->latest('deadline_at');
    }

    public function curationQueueEntries(): HasMany
    {
        return $this->hasMany(ResourceCurationQueue::class)->latest('created_at');
    }

    public function saves(): HasMany
    {
        return $this->hasMany(ResourceUserSave::class)->latest('created_at');
    }

    public function reopenNotifications(): HasMany
    {
        return $this->hasMany(ResourceReopenNotification::class)->latest('created_at');
    }
}
