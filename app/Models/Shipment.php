<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    /**
     * @var string
     */

    use HasFactory;

    protected $table = 'ec_shipments';

    /**
     * @var array
     */
    protected $fillable = [
        'order_id',
        'user_id',
        'weight',
        'shipment_id',
        'note',
        'status',
        'cod_amount',
        'cod_status',
        'cross_checking_status',
        'price',
        'store_id',
        'tracking_id',
        'shipping_company_name',
        'tracking_link',
        'estimate_date_shipped',
        'date_shipped',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'estimate_date_shipped',
        'date_shipped',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class)->withDefault();
    }

}
