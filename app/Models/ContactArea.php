<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactArea extends Model
{
    protected $fillable = [
        'municipality_id',
        'name',
        'contact_name',
        'email',
        'notification_email',
        'backup_contact_name',
        'backup_email',
        'backup_phone',
        'phone',
        'notes',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function demands(): HasMany
    {
        return $this->hasMany(Demand::class, 'contact_area_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'contact_area_id');
    }
}
