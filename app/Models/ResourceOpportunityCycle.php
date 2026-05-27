<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResourceOpportunityCycle extends Model
{
    use HasFactory;

    protected $fillable = [
        'resource_opportunity_id',
        'reopened_from_cycle_id',
        'external_cycle_key',
        'publication_reference',
        'status',
        'is_current',
        'notice_url',
        'application_url',
        'published_at',
        'opens_at',
        'deadline_at',
        'closed_at',
        'closed_visibility_until',
        'total_value',
        'min_value',
        'counterpart_percentage',
        'estimated_size',
        'cycle_metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
            'published_at' => 'datetime',
            'opens_at' => 'datetime',
            'deadline_at' => 'datetime',
            'closed_at' => 'datetime',
            'closed_visibility_until' => 'datetime',
            'total_value' => 'decimal:2',
            'min_value' => 'decimal:2',
            'counterpart_percentage' => 'decimal:2',
            'cycle_metadata' => 'array',
        ];
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(ResourceOpportunity::class, 'resource_opportunity_id');
    }

    public function reopenedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reopened_from_cycle_id');
    }

    public function reopenedChildren(): HasMany
    {
        return $this->hasMany(self::class, 'reopened_from_cycle_id')->latest('published_at');
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
        return $this->hasMany(ResourceReopenNotification::class, 'last_cycle_id')->latest('created_at');
    }
}
