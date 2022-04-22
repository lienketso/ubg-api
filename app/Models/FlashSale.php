<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FlashSale extends Model
{
    use HasFactory;
    protected $table = 'ec_flash_sales';
    protected $fillable = ['name','end_date','status'];

    public function products() : BelongsToMany {
        return $this->belongsToMany(Product::class,'ec_flash_sale_products','flash_sale_id', 'product_id')
            ->withPivot(['price','quantity','sold']);
    }

    public function scopeNotExpired($query)
    {
        return $query->where('end_date', '>=', now()->toDateString());
    }
    public function scopeExpired($query)
    {
        return $query->where('end_date', '<', now()->toDateString());
    }


}
