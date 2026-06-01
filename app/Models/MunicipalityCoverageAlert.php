<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MunicipalityCoverageAlert extends Model
{
    protected $fillable = [
        'municipality_id',
        'event_type',
        'severity',
        'title',
        'message',
        'action_url',
        'fingerprint',
        'status',
        'first_detected_at',
        'last_detected_at',
        'resolved_at',
        'last_pushed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'first_detected_at' => 'datetime',
            'last_detected_at' => 'datetime',
            'resolved_at' => 'datetime',
            'last_pushed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }
}
