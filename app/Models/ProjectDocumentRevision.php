<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDocumentRevision extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'previous_revision_id',
        'user_id',
        'revision_number',
        'trigger_action',
        'summary',
        'status',
        'approved_by_user_id',
        'approved_at',
        'published_by_user_id',
        'published_at',
        'restored_from_revision_id',
        'approval_steps',
        'approval_reason',
        'publication_reason',
        'publication_signature_name',
        'publication_signature_role',
        'snapshot',
        'comparison_summary',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'comparison_summary' => 'array',
            'revision_number' => 'integer',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
            'approval_steps' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function previousRevision(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_revision_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by_user_id');
    }

    public function restoredFromRevision(): BelongsTo
    {
        return $this->belongsTo(self::class, 'restored_from_revision_id');
    }
}
