<?php

namespace App\Models;

use App\Enums\AdminRole;
use App\Enums\Permission;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * Admin account. Shoppers never have user accounts.
 *
 * @property AdminRole $role
 * @property Carbon|null $last_login_at
 * @property Carbon|null $two_factor_confirmed_at
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'password' => 'hashed',
            'role' => AdminRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function hasPermission(Permission $permission): bool
    {
        return $this->is_active && $this->role->can($permission);
    }

    /** @return list<string> */
    public function permissionNames(): array
    {
        return $this->is_active
            ? array_map(fn (Permission $p) => $p->value, $this->role->permissions())
            : [];
    }

    public function hasTwoFactorEnabled(): bool
    {
        // Read raw attributes: a freshly created model may not have the 2FA
        // columns loaded, and strict mode forbids reading missing attributes.
        return ($this->attributes['two_factor_secret'] ?? null) !== null
            && ($this->attributes['two_factor_confirmed_at'] ?? null) !== null;
    }
}
