<?php


namespace App\Repositories;


use App\Models\UbgxuPayLog;
use Prettus\Repository\Eloquent\BaseRepository;

class UbgxuPaylogRepository extends BaseRepository
{
    public function model()
    {
        return UbgxuPayLog::class;
    }
}
