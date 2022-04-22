<?php


namespace App\Repositories;


use App\Models\OrderAddress;
use Prettus\Repository\Eloquent\BaseRepository;

class OrderAddressRepository extends BaseRepository
{
    public function model()
    {
        return OrderAddress::class;
    }
}
