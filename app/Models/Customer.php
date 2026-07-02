<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
  protected $fillable = [
        'type',
        'name',
        'company_name',
        'tax_id',
        'email',
        'phone',
        'address',
    ];
}
