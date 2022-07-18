<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class OrderHistory extends Model
{
    protected $table = 'ec_order_histories';
    protected $fillable = ['action','description','user_id','order_id'];
}
