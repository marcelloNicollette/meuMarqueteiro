<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MandatePromise extends Model
{
    protected $fillable = [
        'municipality_id',
        'mandate_axis_id',
        'text',
        'order',
        'score',
        'status',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'score'     => 'integer',
            'order'     => 'integer',
            'is_active' => 'boolean',
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

    public function actions(): BelongsToMany
    {
        return $this->belongsToMany(MandateAction::class, 'mandate_action_promise')
            ->withPivot('fulfillment_level', 'fulfillment_justification')
            ->withTimestamps();
    }

    /**
     * Recalcula o score do compromisso com base no maior nível de atendimento
     * de todas as ações vinculadas.
     */
    public function recalculateScore(): void
    {
        $maxLevel = $this->actions()
            ->max('mandate_action_promise.fulfillment_level') ?? 0;

        $status = match (true) {
            $maxLevel >= 100 => 'fulfilled',
            $maxLevel >= 75  => 'partial_75',
            $maxLevel >= 50  => 'partial_50',
            $maxLevel >= 25  => 'partial_25',
            default          => 'pending',
        };

        $this->update(['score' => $maxLevel, 'status' => $status]);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'fulfilled'  => 'Atendida',
            'partial_75' => 'Parcial 75%',
            'partial_50' => 'Parcial 50%',
            'partial_25' => 'Parcial 25%',
            default      => 'Pendente',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'fulfilled'  => '#1e7e48',
            'partial_75' => '#1e7e48',
            'partial_50' => '#b8902a',
            'partial_25' => '#b8902a',
            default      => '#b52b2b',
        };
    }
}
