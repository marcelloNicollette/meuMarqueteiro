<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectIntakeQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'question_key',
        'question_order',
        'question_text',
        'help_text',
        'input_type',
        'placeholder',
        'is_required',
        'answer',
        'answered_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'question_order' => 'integer',
            'is_required' => 'boolean',
            'answered_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
