<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;

class Customer extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'type',
        'name',
        'company_name',
        'tax_id',
        'email',
        'phone',
        'address',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /** The portal account linked to this customer, if any. */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function displayName(): string
    {
        return $this->company_name ?: $this->name;
    }

    /** Order status emails go to the customer's address on file. */
    public function routeNotificationForMail(): ?string
    {
        return $this->email;
    }
}
