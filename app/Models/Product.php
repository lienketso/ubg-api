<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory;
    protected $table = 'ec_products';

    protected $fillable = [
        'name',
        'description',
        'content',
        'status',
        'images',
        'sku',
        'order',
        'quantity',
        'allow_checkout_when_out_of_stock',
        'with_storehouse_management',
        'is_featured',
        'options',
        'brand_id',
        'is_variation',
        'is_searchable',
        'is_show_on_list',
        'sale_type',
        'price',
        'sale_price',
        'start_date',
        'end_date',
        'length',
        'wide',
        'height',
        'weight',
        'barcode',
        'length_unit',
        'wide_unit',
        'height_unit',
        'weight_unit',
        'tax_id',
        'status',
        'views',
        'stock_status',
        'store_id',
        'category_id',
        'kiotviet_id',
        'kiotviet_branch_id',
        'extras',
        'origin'
    ];

    public function categories() : BelongsToMany{
        return $this->belongsToMany(
            ProductCategory::class,
            'ec_product_category_product',
            'product_id',
            'category_id'
        );
    }

}
