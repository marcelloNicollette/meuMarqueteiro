<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'role',           // user | assistant | system
        'content',
        'input_type',     // text | voice
        'voice_transcript',
        'rag_sources',    // JSON — fontes usadas pelo RAG
        'tokens_used',
        'feedback',       // thumbs_up | thumbs_down | null
        'feedback_note',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'rag_sources' => 'array',
            'tokens_used' => 'integer',
            'metadata'    => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function isFromUser(): bool
    {
        return $this->role === 'user';
    }

    public function isFromAssistant(): bool
    {
        return $this->role === 'assistant';
    }
}
