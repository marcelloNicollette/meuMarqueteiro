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
        'user_id',
        'date',
        'content',         // conteúdo gerado em markdown
        'opening_text',
        'sections',        // JSON — agenda, comunicação, alertas, contexto_politico
        'cards',
        'scope_profile',
        'delivered_at',
        'delivery_channel', // app | whatsapp
        'read_at',
        'superseded_at',
        'ai_provider',
        'ai_model',
        'tokens_used',
        'rag_sources_count',
    ];

    protected function casts(): array
    {
        return [
            'date'         => 'date',
            'sections'     => 'array',
            'cards'        => 'array',
            'delivered_at' => 'datetime',
            'read_at'      => 'datetime',
            'superseded_at' => 'datetime',
            'tokens_used'  => 'integer',
            'rag_sources_count' => 'integer',
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
