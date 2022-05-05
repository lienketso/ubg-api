<?php


namespace App\Repositories;


use App\Models\Province;
use Prettus\Repository\Eloquent\BaseRepository;

class ProvinceRepository extends BaseRepository
{
    public function model()
    {
        // TODO: Implement model() method.
        return Province::class;
    }
}
