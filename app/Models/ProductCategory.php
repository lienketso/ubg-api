<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductCategory extends Model
{
    use HasFactory;

    protected $table = 'ec_product_categories';

    protected $fillable = [
        'name',
        'parent_id',
        'description',
        'order',
        'status',
        'image',
        'is_featured',
    ];

    public function childs()
    {
        return $this->hasMany(ProductCategory::class,'parent_id','id')->orderBy('order','asc');
    }

    public function subproducts()
    {
        return $this->hasManyThrough(Product::class, self::class, 'parent_id', 'category_id');
    }

    public function products() : BelongsToMany
    {
        return $this->belongsToMany(Product::class,'ec_product_category_product','category_id','product_id');
    }
}
