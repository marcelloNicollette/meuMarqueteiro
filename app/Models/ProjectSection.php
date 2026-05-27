<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'section_key',
        'section_order',
        'title',
        'description',
        'content',
        'is_required',
        'needs_review',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'needs_review' => 'boolean',
            'section_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function editHistory(): HasMany
    {
        return $this->hasMany(ProjectEditHistory::class)->latest('created_at');
    }
}
