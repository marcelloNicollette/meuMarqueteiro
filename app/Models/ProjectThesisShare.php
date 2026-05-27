<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectThesisShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_thesis_id',
        'shared_by_user_id',
        'shared_with_user_id',
        'viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
        ];
    }

    public function thesis(): BelongsTo
    {
        return $this->belongsTo(ProjectThesis::class, 'project_thesis_id');
    }

    public function sharedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by_user_id');
    }

    public function sharedWith(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_with_user_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(ProjectThesisNotification::class, 'project_thesis_share_id')->latest('created_at');
    }
}
