<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Staudenmeir\EloquentEagerLimit\HasEagerLimit;

class Product extends Model
{
    use HasFactory;
    use HasEagerLimit;
    protected $table = 'ec_products';

    protected $guarded = [];

    public function categories() : BelongsToMany{
        return $this->belongsToMany(
            ProductCategory::class,
            'ec_product_category_product',
            'product_id',
            'category_id'
        );
    }

    public function stores() : HasMany{
        return $this->hasMany(Stores::class,'id','store_id');
    }

}
