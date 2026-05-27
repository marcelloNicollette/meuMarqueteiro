<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'municipality_id',
        'user_id',
        'name',
        'kind',
        'channel',
        'format',
        'tone',
        'description',
        'instruction',
        'default_tones',
        'default_payload',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_tones' => 'array',
            'default_payload' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
