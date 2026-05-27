<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceReopenNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'municipality_id',
        'resource_opportunity_id',
        'last_cycle_id',
        'channel',
        'status',
        'criteria',
        'subscribed_at',
        'last_notified_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'criteria' => 'array',
            'subscribed_at' => 'datetime',
            'last_notified_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(ResourceOpportunity::class, 'resource_opportunity_id');
    }

    public function lastCycle(): BelongsTo
    {
        return $this->belongsTo(ResourceOpportunityCycle::class, 'last_cycle_id');
    }
}
