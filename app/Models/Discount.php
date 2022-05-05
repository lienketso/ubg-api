<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory;
    /**
     * @var string
     */
    protected $table = 'ec_discounts';

    /**
     * @var array
     */
    protected $fillable = [
        'title',
        'code',
        'start_date',
        'end_date',
        'quantity',
        'total_used',
        'value',
        'type',
        'can_use_with_promotion',
        'type_option',
        'target',
        'min_order_price',
        'discount_on',
        'product_quantity',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'start_date',
        'end_date',
        'created_at',
        'updated_at',
    ];

    /**
     * @return bool
     */
    public function isExpired()
    {
        if ($this->end_date && strtotime($this->end_date) < strtotime(now()->toDateTimeString())) {
            return true;
        }

        return false;
    }
}
