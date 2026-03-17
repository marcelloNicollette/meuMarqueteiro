<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GovernmentCommitment extends Model
{
    use HasFactory;

    protected $fillable = [
        'municipality_id',
        'title',
        'description',
        'area',             // saude | educacao | infraestrutura | social | seguranca | outros
        'status',           // prometido | em_andamento | entregue | em_risco | cancelado
        'priority',         // alta | media | baixa
        'deadline',
        'budget_allocated',
        'budget_spent',
        'progress_percent',
        'responsible_secretary',
        'responsible_contact',
        'budget_source',
        'delivered_at',
        'notes',
        'source_document',
    ];

    protected function casts(): array
    {
        return [
            'deadline'          => 'date',
            'delivered_at'      => 'date',
            'budget_allocated'  => 'decimal:2',
            'budget_spent'      => 'decimal:2',
            'progress_percent'  => 'integer',
        ];
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'prometido'    => 'yellow',
            'em_andamento' => 'blue',
            'entregue'     => 'green',
            'em_risco'     => 'orange',
            'cancelado'    => 'red',
            default        => 'gray',
        };
    }
}
