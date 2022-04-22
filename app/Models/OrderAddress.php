<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderAddress extends Model
{
    use HasFactory;
    protected $table = 'ec_order_addresses';
    protected $fillable = [
        'name',
        'email',
        'phone',
        'country',
        'state',
        'city',
        'address',
        'zip_code',
        'order_id'
    ];
    public $timestamps = false;
}
