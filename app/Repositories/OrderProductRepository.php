<?php


namespace App\Repositories;


use App\Models\OrderProduct;
use Prettus\Repository\Eloquent\BaseRepository;

class OrderProductRepository extends BaseRepository
{
    public function model()
    {
        // TODO: Implement model() method.
        return OrderProduct::class;
    }
}
