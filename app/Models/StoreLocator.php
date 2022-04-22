<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreLocator extends Model
{
    use HasFactory;
    protected $table = 'ec_store_locators';
    protected $fillable = ['name','phone','email','address','state','city','is_primary','is_shipping_location'];
}
