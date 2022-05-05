<?php


namespace App\Repositories;


use App\Models\Discount;
use Illuminate\Database\Eloquent\Model;
use Prettus\Repository\Eloquent\BaseRepository;
use Illuminate\Support\Collection;

class DiscountRepository extends BaseRepository
{
    public function model()
    {
        // TODO: Implement model() method.
        return Discount::class;
    }


}
