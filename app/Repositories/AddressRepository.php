<?php


namespace App\Repositories;


use App\Models\Address;
use Prettus\Repository\Eloquent\BaseRepository;

class AddressRepository extends BaseRepository
{
    public function model()
    {
        return Address::class;
    }
}
