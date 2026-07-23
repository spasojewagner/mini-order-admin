<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    // Ko sme da pristupi Filament admin panelu — samo interne role, ne kupci
    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, ['admin', 'sales', 'warehouse']);
    }
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'customer_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
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
            'password' => 'hashed',
        ];
    }
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSales(): bool
    {
        return $this->role === 'sales';
    }

    public function isWarehouse(): bool
    {
        return $this->role === 'warehouse';
    }
    
    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    // Nalog kupca je povezan sa Customer zapisom (porudžbine idu na kupca, ne na nalog)
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }}
    
