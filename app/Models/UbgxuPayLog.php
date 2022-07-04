<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UbgxuPayLog extends Model
{
    use HasFactory;
    /**
     * @var string
     */
    protected $table = 'ubgxu_pay_log';

    /**
     * @var string[]
     */
    protected $fillable = [
        'content',
        'comeback',
        'recomeback',
        'current_coin',
        'customer_id'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customer()
    {
        return $this->belongsTo(Customers::class);
    }
}
