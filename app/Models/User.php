<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\CausesActivity;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, CausesActivity;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'municipality_id',
        'contact_area_id',
        'phone',
        'is_active',
        'can_register_demands',
        'last_login_at',
        'preferences',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
            'can_register_demands' => 'boolean',
            'role'              => UserRole::class,
            'preferences'       => 'array',
        ];
    }

    // ─── Scopes ─────────────────────────────────────────

    public function scopeAdmins($query)
    {
        return $query->where('role', UserRole::Admin);
    }

    public function scopeMayors($query)
    {
        return $query->where('role', UserRole::Mayor);
    }

    public function scopeMunicipalOperators($query)
    {
        return $query->whereIn('role', [
            UserRole::Mayor,
            UserRole::Secretary,
            UserRole::Advisor,
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ─── Relacionamentos ─────────────────────────────────

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function contactArea(): BelongsTo
    {
        return $this->belongsTo(ContactArea::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function generatedContents(): HasMany
    {
        return $this->hasMany(GeneratedContent::class);
    }

    public function ownedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'owner_user_id');
    }

    public function projectCollaborations(): HasMany
    {
        return $this->hasMany(ProjectCollaborator::class);
    }

    public function resourceCurationAssignments(): HasMany
    {
        return $this->hasMany(ResourceCurationQueue::class, 'assigned_to_user_id')->latest('created_at');
    }

    public function reviewedResourceCurationEntries(): HasMany
    {
        return $this->hasMany(ResourceCurationQueue::class, 'reviewed_by_user_id')->latest('created_at');
    }

    public function savedResources(): HasMany
    {
        return $this->hasMany(ResourceUserSave::class)->latest('created_at');
    }

    public function resourceReopenNotifications(): HasMany
    {
        return $this->hasMany(ResourceReopenNotification::class)->latest('created_at');
    }

    public function morningBriefings(): HasMany
    {
        return $this->hasMany(MorningBriefing::class)->latest('date');
    }

    public function projectThesisStates(): HasMany
    {
        return $this->hasMany(ProjectThesisUserState::class)->latest('updated_at');
    }

    public function sentProjectThesisShares(): HasMany
    {
        return $this->hasMany(ProjectThesisShare::class, 'shared_by_user_id')->latest('created_at');
    }

    public function receivedProjectThesisShares(): HasMany
    {
        return $this->hasMany(ProjectThesisShare::class, 'shared_with_user_id')->latest('created_at');
    }

    public function projectThesisNotifications(): HasMany
    {
        return $this->hasMany(ProjectThesisNotification::class)->latest('created_at');
    }

    // ─── Helpers ─────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isMayor(): bool
    {
        return $this->role === UserRole::Mayor;
    }

    public function isSecretary(): bool
    {
        return $this->role === UserRole::Secretary;
    }

    public function isAdvisor(): bool
    {
        return $this->role === UserRole::Advisor;
    }

    public function canRegisterResolveAiDemands(): bool
    {
        return $this->isMayor() || $this->isSecretary() || (bool) $this->can_register_demands;
    }

    public function getDashboardRoute(): string
    {
        return match ($this->role) {
            UserRole::Admin => 'admin.dashboard',
            UserRole::Mayor => 'mayor.dashboard',
            UserRole::Secretary, UserRole::Advisor => 'resolve-ai.demands.index',
        };
    }
}
