<?php


namespace App\Repositories;


use App\Models\Product;
use Prettus\Repository\Eloquent\BaseRepository;

class ProductRepository extends BaseRepository
{
    public function model()
    {
        // TODO: Implement model() method.
        return Product::class;
    }
}
