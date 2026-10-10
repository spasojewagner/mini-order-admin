<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'customer_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** Only company staff use the back office; customers use the portal. */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->roleEnum()?->isStaff() ?? false;
    }

    public function roleEnum(): ?Role
    {
        return Role::tryFrom((string) $this->role);
    }

    public function hasRole(Role ...$roles): bool
    {
        return in_array($this->roleEnum(), $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::Admin);
    }

    public function isSales(): bool
    {
        return $this->hasRole(Role::Sales);
    }

    public function isWarehouse(): bool
    {
        return $this->hasRole(Role::Warehouse);
    }

    public function isCustomer(): bool
    {
        return $this->hasRole(Role::Customer);
    }

    public function isStaff(): bool
    {
        return $this->roleEnum()?->isStaff() ?? false;
    }

    /** A portal account belongs to a customer; orders are placed for the customer. */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
