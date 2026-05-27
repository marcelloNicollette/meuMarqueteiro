<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectThesisUserState extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_thesis_id',
        'user_id',
        'is_saved',
        'last_action_at',
    ];

    protected function casts(): array
    {
        return [
            'is_saved' => 'boolean',
            'last_action_at' => 'datetime',
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
}
