<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupBuyOrder extends Model
{
    use HasFactory;
    protected $table = 'ec_group_buy_orders';
    protected $fillable = [
        'group_buy_product_id',
        'customer_id',
        'amount',
        'payment_id',
        'order_id',
        'qty'
    ];
}
