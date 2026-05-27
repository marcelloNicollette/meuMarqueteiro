<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResourceSource extends Model
{
    protected $fillable = [
        'key',
        'name',
        'resource_scope',
        'capture_method',
        'pipeline_group',
        'refresh_frequency',
        'operational_status',
        'source_url',
        'access_guide',
        'index_fields',
        'operational_tags',
        'source_metadata',
        'maintenance_notes',
        'is_active',
        'requires_human_curation',
        'supports_municipality_sync',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'index_fields' => 'array',
            'operational_tags' => 'array',
            'source_metadata' => 'array',
            'is_active' => 'boolean',
            'requires_human_curation' => 'boolean',
            'supports_municipality_sync' => 'boolean',
        ];
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(FederalProgramAlert::class);
    }

    public function canonicalOpportunities(): HasMany
    {
        return $this->hasMany(ResourceOpportunity::class)->latest('updated_at');
    }

    public function curationQueueEntries(): HasMany
    {
        return $this->hasMany(ResourceCurationQueue::class)->latest('created_at');
    }
}
