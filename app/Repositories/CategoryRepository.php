<?php


namespace App\Repositories;


use App\Models\Category;
use Prettus\Repository\Eloquent\BaseRepository;

class CategoryRepository extends BaseRepository
{
    public function model()
    {
        // TODO: Implement model() method.
        return Category::class;
    }
}
