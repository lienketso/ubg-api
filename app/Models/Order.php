<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $table = 'ec_orders';
    protected $fillable = [
        'status',
        'user_id',
        'amount',
        'currency_id',
        'tax_amount',
        'shipping_method',
        'shipping_option',
        'shipping_amount',
        'description',
        'coupon_code',
        'discount_amount',
        'sub_total',
        'is_confirmed',
        'discount_description',
        'is_finished',
        'token',
        'store_id',
        'affliate_user_id',
        'commit_no_refund',
        'process_affiliate',
        'order_type',
        'group_buy_product_id',
        'group_buy_order_id',
        'order_province',
        'order_district',
        'paid_by_ubgxu'
    ];

    public function products(){
        return $this->hasMany(OrderProduct::class,'order_id');
    }
    public function address(){
        return $this->hasMany(OrderAddress::class,'order_id');
    }

    public function shipment(){

    }

}
