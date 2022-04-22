<?php


namespace App\Repositories;


use App\Models\Customers;
use Prettus\Repository\Eloquent\BaseRepository;

class CustomerRepository extends BaseRepository
{
    public function model()
    {
        // TODO: Implement model() method.
        return Customers::class;
    }
}
