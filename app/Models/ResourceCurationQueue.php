<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceCurationQueue extends Model
{
    use HasFactory;

    protected $table = 'resource_curation_queue';

    protected $fillable = [
        'resource_opportunity_id',
        'resource_opportunity_cycle_id',
        'resource_source_id',
        'municipality_id',
        'assigned_to_user_id',
        'reviewed_by_user_id',
        'queue_status',
        'priority',
        'source_payload_snapshot',
        'enrichment_payload',
        'decision_notes',
        'entered_queue_at',
        'sla_due_at',
        'review_started_at',
        'reviewed_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'source_payload_snapshot' => 'array',
            'enrichment_payload' => 'array',
            'entered_queue_at' => 'datetime',
            'sla_due_at' => 'datetime',
            'review_started_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(ResourceOpportunity::class, 'resource_opportunity_id');
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(ResourceOpportunityCycle::class, 'resource_opportunity_cycle_id');
    }

    public function resourceSource(): BelongsTo
    {
        return $this->belongsTo(ResourceSource::class);
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
