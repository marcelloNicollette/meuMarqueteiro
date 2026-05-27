<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MandateActionProgressLog extends Model
{
    protected $fillable = [
        'mandate_action_id',
        'mandate_action_milestone_id',
        'event_type',
        'description',
        'from_progress',
        'to_progress',
        'from_status',
        'to_status',
        'performed_by',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'from_progress' => 'integer',
            'to_progress' => 'integer',
        ];
    }

    public function action(): BelongsTo
    {
        return $this->belongsTo(MandateAction::class, 'mandate_action_id');
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(MandateActionMilestone::class, 'mandate_action_milestone_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
