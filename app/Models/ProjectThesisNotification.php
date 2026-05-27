<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectThesisNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_thesis_id',
        'user_id',
        'project_thesis_share_id',
        'event_type',
        'title',
        'message',
        'action_url',
        'fingerprint',
        'read_at',
        'delivered_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'delivered_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function thesis(): BelongsTo
    {
        return $this->belongsTo(ProjectThesis::class, 'project_thesis_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function share(): BelongsTo
    {
        return $this->belongsTo(ProjectThesisShare::class, 'project_thesis_share_id');
    }
}
