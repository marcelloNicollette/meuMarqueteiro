<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Municipality extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'ibge_code',
        'name',
        'state',
        'state_code',
        'population',
        'gdp',
        'idhm',
        'area_km2',
        'region',
        'onboarding_status',   // pending | in_progress | completed
        'onboarding_completed_at',
        'subscription_tier',   // essencial | estrategico | parceiro
        'subscription_active',
        'data_last_synced_at',
        'settings',
        'voice_profile',       // JSON — perfil de voz do prefeito
        'political_map',       // JSON — mapa de forças da câmara
    ];

    protected function casts(): array
    {
        return [
            'population'               => 'integer',
            'gdp'                      => 'decimal:2',
            'idhm'                     => 'decimal:3',
            'area_km2'                 => 'decimal:2',
            'subscription_active'      => 'boolean',
            'onboarding_completed_at'  => 'datetime',
            'data_last_synced_at'      => 'datetime',
            'settings'                 => 'array',
            'voice_profile'            => 'array',
            'political_map'            => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty();
    }

    // ─── Relacionamentos ─────────────────────────────────

    public function mayor(): HasOne
    {
        return $this->hasOne(User::class)->where('role', 'mayor');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(MunicipalityDocument::class);
    }

    public function governmentCommitments(): HasMany
    {
        return $this->hasMany(GovernmentCommitment::class);
    }

    public function federalPrograms(): HasMany
    {
        return $this->hasMany(FederalProgramAlert::class);
    }

    public function fiscalData(): HasMany
    {
        return $this->hasMany(FiscalData::class);
    }

    public function generatedContents(): HasMany
    {
        return $this->hasMany(GeneratedContent::class);
    }

    public function morningBriefings(): HasMany
    {
        return $this->hasMany(MorningBriefing::class);
    }

    // ─── Helpers ─────────────────────────────────────────

    public function isOnboarded(): bool
    {
        return $this->onboarding_status === 'completed';
    }

    public function getTierLabel(): string
    {
        return match ($this->subscription_tier) {
            'essencial'   => 'Essencial',
            'estrategico' => 'Estratégico',
            'parceiro'    => 'Parceiro',
            default       => 'Indefinido',
        };
    }
}
