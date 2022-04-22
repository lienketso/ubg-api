<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductCollection extends Model
{
    use HasFactory;
    protected $table = 'ec_product_collections';
    protected $fillable = ['name','slug','description','image','status'];

    public function products() : BelongsToMany{
        return $this->belongsToMany(Product::class,'ec_product_collection_products','product_collection_id','product_id');
    }

}
