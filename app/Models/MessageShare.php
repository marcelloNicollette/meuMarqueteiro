<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'municipality_id',
        'conversation_id',
        'message_id',
        'owner_user_id',
        'recipient_user_id',
        'revoked_by_user_id',
        'share_token',
        'excerpt',
        'context_excerpt',
        'message_role',
        'note',
        'viewed_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by_user_id');
    }
}
