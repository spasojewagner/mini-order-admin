<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'company_name',
        'tax_id',
        'email',
        'phone',
        'address',
    ];
    // Kupac ima više porudžbina
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}