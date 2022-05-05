<?php


namespace App\Repositories;


use App\Models\Ward;
use Prettus\Repository\Eloquent\BaseRepository;

class WardRepository extends BaseRepository
{
    public function model()
    {
        // TODO: Implement model() method.
        return Ward::class;
    }
}
