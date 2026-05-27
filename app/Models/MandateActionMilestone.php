<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MandateActionMilestone extends Model
{
    protected $fillable = [
        'mandate_action_id',
        'title',
        'due_date',
        'order',
        'completed_at',
        'completed_by',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'order' => 'integer',
        ];
    }

    public function action(): BelongsTo
    {
        return $this->belongsTo(MandateAction::class, 'mandate_action_id');
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function progressLogs(): HasMany
    {
        return $this->hasMany(MandateActionProgressLog::class, 'mandate_action_milestone_id')
            ->orderByDesc('occurred_at');
    }
}
