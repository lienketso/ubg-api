<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupBuyProduct extends Model
{
    use HasFactory;
    protected $table = 'ec_group_buy_products';
    protected $fillable = [
        'product_id',
        'limit', // Số lượng đơn hàng để đạt,
        'price',
        'min_amount', // Thanh toán tối thiểu
        'start_date',
        'end_date',
        'max_buy' // Số đơn tối đa được phép mua
    ];

    public function getProduct(){
        return $this->hasOne(Product::class,'id','product_id');
    }

    public function getOrders(){
        return $this->hasMany(GroupBuyOrder::class,'group_buy_product_id','id');
    }

}
