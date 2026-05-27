<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceUserSave extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'municipality_id',
        'resource_opportunity_id',
        'resource_opportunity_cycle_id',
        'saved_from',
        'notes',
        'preferences',
        'last_viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'preferences' => 'array',
            'last_viewed_at' => 'datetime',
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

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(ResourceOpportunityCycle::class, 'resource_opportunity_cycle_id');
    }
}
