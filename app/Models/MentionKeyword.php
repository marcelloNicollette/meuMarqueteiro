<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MentionKeyword extends Model
{
    protected $fillable = [
        'municipality_id',
        'keyword',
        'type',
        'is_active',
        'alert_negative',
    ];

    protected function casts(): array
    {
        return [
            'is_active'      => 'boolean',
            'alert_negative' => 'boolean',
        ];
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'city'      => 'Cidade',
            'mayor'     => 'Prefeito(a)',
            'secretary' => 'Secretaria',
            'topic'     => 'Tema',
            'hashtag'   => 'Hashtag',
            default     => 'Geral',
        };
    }
}
