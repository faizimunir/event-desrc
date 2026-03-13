<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Rider;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'whatsapp',
        'activated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'activated_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function riders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Rider::class);
    }

    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** Organizer-organizer yang dikelola user ini (admin organizer). */
    public function managedOrganizers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Organizer::class, 'user_id');
    }

    /** Cek apakah akun sudah aktivasi (bisa login dengan email + password). */
    public function isActivated(): bool
    {
        return $this->activated_at !== null;
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Session key for active role
     */
    private const ACTIVE_ROLE_SESSION_KEY = 'active_role';

    /**
     * Get the currently active role (for UI context when user has multiple roles)
     */
    public function activeRole(): ?Role
    {
        $name = session(self::ACTIVE_ROLE_SESSION_KEY);

        if (! $name || ! $this->hasRole($name)) {
            return null;
        }

        return $this->roles->first(fn (Role $r) => $r->name === $name);
    }

    /**
     * Set the active role (store in session)
     */
    public function setActiveRole(string $roleName): void
    {
        if (! $this->hasRole($roleName)) {
            return;
        }

        session()->put(self::ACTIVE_ROLE_SESSION_KEY, $roleName);
    }

    /**
     * Ensure session has a valid active role; set default if missing
     */
    public function resolveDefaultActiveRole(): void
    {
        $current = session(self::ACTIVE_ROLE_SESSION_KEY);

        if ($current && $this->hasRole($current)) {
            return;
        }

        $first = $this->roles()->orderBy('name')->first();
        session()->put(self::ACTIVE_ROLE_SESSION_KEY, $first?->name);
    }

    /**
     * Check if user has more than one role
     */
    public function hasMultipleRoles(): bool
    {
        return $this->roles()->count() > 1;
    }

    /**
     * Check if the given role is the currently active role
     */
    public function isActiveRole(string $role): bool
    {
        return $this->activeRole()?->name === $role;
    }

    /**
     * Check permission based on ACTIVE role only (not all roles).
     * Use this instead of can()/hasPermissionTo() for active-role-aware authorization.
     *
     * - super_admin bypass: always true
     * - No active role: false
     * - Active role lacks permission: false
     */
    public function canAs(string $permission): bool
    {
        if ($this->hasRole('super_admin')) {
            return true;
        }

        $role = $this->activeRole();
        if (! $role) {
            return false;
        }

        try {
            return $role->hasPermissionTo($permission);
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist) {
            return false;
        }
    }

    /**
     * Authorize using active role; abort 403 if not allowed
     */
    public function authorizeAs(string $permission): void
    {
        if (! $this->canAs($permission)) {
            abort(403);
        }
    }
}
