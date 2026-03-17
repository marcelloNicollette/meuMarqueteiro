<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MorningBriefing extends Model
{
    use HasFactory;

    protected $fillable = [
        'municipality_id',
        'date',
        'content',         // conteúdo gerado em markdown
        'sections',        // JSON — agenda, comunicacao, alertas, contexto_politico
        'delivered_at',
        'delivery_channel', // app | whatsapp
        'read_at',
        'ai_provider',
        'tokens_used',
    ];

    protected function casts(): array
    {
        return [
            'date'         => 'date',
            'sections'     => 'array',
            'delivered_at' => 'datetime',
            'read_at'      => 'datetime',
            'tokens_used'  => 'integer',
        ];
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }
}
