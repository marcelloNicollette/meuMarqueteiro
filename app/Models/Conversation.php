<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'municipality_id',
        'title',
        'context',      // JSON — contexto persistente da conversa
        'ai_provider',  // openai | anthropic | gemini
        'ai_model',
        'token_count',
        'is_active',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'context'         => 'array',
            'token_count'     => 'integer',
            'is_active'       => 'boolean',
            'last_message_at' => 'datetime',
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

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }
}
