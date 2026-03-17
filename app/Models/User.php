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
        'phone',
        'is_active',
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ─── Relacionamentos ─────────────────────────────────

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function generatedContents(): HasMany
    {
        return $this->hasMany(GeneratedContent::class);
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

    public function getDashboardRoute(): string
    {
        return match ($this->role) {
            UserRole::Admin => 'admin.dashboard',
            UserRole::Mayor => 'mayor.dashboard',
        };
    }
}
