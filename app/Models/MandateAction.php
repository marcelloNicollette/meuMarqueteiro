<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MandateAction extends Model
{
    protected $fillable = [
        'municipality_id',
        'mandate_axis_id',
        'project_id',
        'title',
        'description',
        'secretaria',
        'status',
        'start_date',
        'end_date',
        'physical_progress',
        'uses_milestones_progress',
        'investment',
        'funding_source',
        'region',
        'beneficiaries',
        'proof_url',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'start_date'        => 'date',
            'end_date'          => 'date',
            'investment'        => 'decimal:2',
            'is_public'         => 'boolean',
            'physical_progress' => 'integer',
            'uses_milestones_progress' => 'boolean',
        ];
    }

    public function axis(): BelongsTo
    {
        return $this->belongsTo(MandateAxis::class, 'mandate_axis_id');
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function promises(): BelongsToMany
    {
        return $this->belongsToMany(MandatePromise::class, 'mandate_action_promise')
            ->withPivot('fulfillment_level', 'fulfillment_justification')
            ->withTimestamps();
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(MandateActionMilestone::class)->orderBy('order');
    }

    public function progressLogs(): HasMany
    {
        return $this->hasMany(MandateActionProgressLog::class)->orderByDesc('occurred_at');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'planejado'    => 'Planejado',
            'nao_iniciado' => 'Não iniciado',
            'em_andamento' => 'Em andamento',
            'concluido'    => 'Concluído',
            'suspenso'     => 'Suspenso',
            default        => 'Em andamento',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'concluido'    => '#1e7e48',
            'em_andamento' => '#b8902a',
            'nao_iniciado' => '#6b7280',
            'planejado'    => '#1e3a5f',
            'suspenso'     => '#b52b2b',
            default        => '#b8902a',
        };
    }

    public function getInvestmentFormattedAttribute(): string
    {
        if (!$this->investment) return '—';
        return 'R$ ' . number_format($this->investment, 2, ',', '.');
    }
}
