<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    /**
     * @var string
     */
    protected $table = 'ec_customer_wallet';

    /**
     * @var string[]
     */
    protected $fillable = [
        'customer_id',
        'amount',
        'amount_temp',
        'created_at',
        'updated_at',
        'bank_info'
    ];


    /**
     * @return BelongsTo
     */
    public function customer() : BelongsTo
    {
        return $this->belongsTo(Customers::class, 'id', 'customer_id');
    }

}
