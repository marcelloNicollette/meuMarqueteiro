<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'municipality_id',
        'owner_user_id',
        'last_edited_by_user_id',
        'source_thesis_id',
        'title',
        'initial_idea',
        'project_type',
        'status',
        'responsible_secretariat',
        'current_phase',
        'generated_document_version',
        'metadata',
        'last_edited_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'last_edited_at' => 'datetime',
            'generated_document_version' => 'integer',
        ];
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function lastEditedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_edited_by_user_id');
    }

    public function sourceThesis(): BelongsTo
    {
        return $this->belongsTo(ProjectThesis::class, 'source_thesis_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ProjectSection::class)->orderBy('section_order');
    }

    public function collaborators(): HasMany
    {
        return $this->hasMany(ProjectCollaborator::class);
    }

    public function intakeQuestions(): HasMany
    {
        return $this->hasMany(ProjectIntakeQuestion::class)->orderBy('question_order');
    }

    public function editHistory(): HasMany
    {
        return $this->hasMany(ProjectEditHistory::class)->latest('created_at');
    }

    public function documentRevisions(): HasMany
    {
        return $this->hasMany(ProjectDocumentRevision::class)->latest('revision_number');
    }

    public function mandateActions(): HasMany
    {
        return $this->hasMany(MandateAction::class)->orderByDesc('updated_at');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'em_elaboração' => 'Em elaboração',
            'concluido' => 'Concluido',
            'em_execução' => 'Em execução',
            'captacao_em_andamento' => 'Captação em andamento',
            default => 'Em elaboração',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->project_type) {
            'infraestrutura' => 'Infraestrutura',
            'social' => 'Social',
            'ambiental' => 'Ambiental',
            'economico' => 'Economico',
            'institucional' => 'Institucional',
            default => 'A definir',
        };
    }
}
