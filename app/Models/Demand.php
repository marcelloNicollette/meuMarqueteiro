<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Demand extends Model
{
    protected $fillable = [
        'municipality_id',
        'registered_by',
        'input_type',
        'raw_input',
        'title',
        'description',
        'area',
        'locality',
        'responsible_secretary',
        'priority',
        'is_urgent',
        'status',
        'resolution_note',
        'resolved_at',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return [
            'is_urgent'   => 'boolean',
            'resolved_at' => 'datetime',
            'latitude'    => 'decimal:7',
            'longitude'   => 'decimal:7',
        ];
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }
}
