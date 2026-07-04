<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'status',
        'total_amount',
        'note',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];

    // Porudžbina pripada jednom kupcu
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Porudžbina ima više stavki
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}