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
        'address',
        'responsible_secretary',
        'contact_area_id',
        'priority',
        'due_date',
        'due_at',
        'is_urgent',
        'status',
        'resolution_note',
        'completion_note',
        'reopened_reason',
        'resolved_at',
        'acknowledged_at',
        'last_progress_at',
        'completion_requested_at',
        'confirmed_at',
        'reopened_at',
        'completion_attachment_path',
        'completion_attachment_name',
        'completion_attachment_mime',
        'completion_attachment_size',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return [
            'is_urgent'   => 'boolean',
            'resolved_at' => 'datetime',
            'due_date'    => 'date',
            'due_at'      => 'datetime',
            'acknowledged_at' => 'datetime',
            'last_progress_at' => 'datetime',
            'completion_requested_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'reopened_at' => 'datetime',
            'completion_attachment_size' => 'integer',
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

    public function contactArea(): BelongsTo
    {
        return $this->belongsTo(ContactArea::class, 'contact_area_id');
    }

    public function comments()
    {
        return $this->hasMany(DemandComment::class)->orderBy('created_at', 'desc');
    }

    public function events()
    {
        return $this->hasMany(DemandEvent::class)->latest('created_at');
    }

    public function notifications()
    {
        return $this->hasMany(DemandNotification::class)->latest('created_at');
    }
}
