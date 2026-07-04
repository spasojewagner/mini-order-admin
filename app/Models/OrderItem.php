<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'unit_price',
        'quantity',
        'subtotal',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    // Stavka pripada jednoj porudžbini
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Stavka pokazuje na jedan proizvod
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}