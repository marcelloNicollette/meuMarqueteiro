<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationMemory extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'municipality_id',
        'user_id',
        'user_message_id',
        'assistant_message_id',
        'memory_type',
        'source',
        'content',
        'embedding',
        'metadata',
        'token_count',
        'importance_score',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'token_count' => 'integer',
            'importance_score' => 'float',
            'last_used_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'user_message_id');
    }

    public function assistantMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'assistant_message_id');
    }
}
