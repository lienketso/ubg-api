<?php


namespace App\Repositories;


use App\Models\Order;
use Prettus\Repository\Eloquent\BaseRepository;

class OrderRepository extends BaseRepository
{
    public function model()
    {
        return Order::class;
    }
}
