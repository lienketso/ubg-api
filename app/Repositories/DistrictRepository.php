<?php


namespace App\Repositories;


use App\Models\Districts;
use Prettus\Repository\Eloquent\BaseRepository;

class DistrictRepository extends BaseRepository
{
    public function model()
    {
        // TODO: Implement model() method.
        return Districts::class;
    }
}
