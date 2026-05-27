<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectThesis extends Model
{
    use HasFactory;

    protected $fillable = [
        'municipality_id',
        'project_thesis_template_id',
        'title',
        'category',
        'justification',
        'potential_impact',
        'funding_source',
        'estimated_size',
        'urgency',
        'execution_complexity',
        'reference_municipalities',
        'government_alignment',
        'resource_deadline',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'resource_deadline' => 'date',
            'metadata' => 'array',
        ];
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ProjectThesisTemplate::class, 'project_thesis_template_id');
    }

    public function userStates(): HasMany
    {
        return $this->hasMany(ProjectThesisUserState::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(ProjectThesisShare::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(ProjectThesisNotification::class)->latest('created_at');
    }

    public function sourceProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'source_thesis_id');
    }

    public function trackingStatus(): string
    {
        $project = $this->sourceProjects()->latest('id')->first();

        if (!$project) {
            return 'disponível';
        }

        return match ((string) $project->status) {
            'em_elaboração' => 'em_elaboração',
            'concluido' => 'projeto_concluido',
            'captacao_em_andamento' => 'captacao_em_andamento',
            'em_execução' => 'executado',
            default => 'em_elaboração',
        };
    }
}
