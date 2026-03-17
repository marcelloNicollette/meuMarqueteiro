<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'municipality_id',
        'user_id',
        'conversation_id',
        'type',          // post_instagram | post_facebook | whatsapp | discurso | comunicado | resposta_crise
        'channel',
        'tone',          // celebratorio | tecnico | empatico | informativo
        'title',
        'content',
        'variations',    // JSON — variações alternativas
        'published_at',
        'published_url',
        'status',        // draft | approved | published | archived
        'tags',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'variations'   => 'array',
            'published_at' => 'datetime',
            'tags'         => 'array',
            'metadata'     => 'array',
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

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
