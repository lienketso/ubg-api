<?php


namespace App\Repositories;


use App\Models\User;
use Prettus\Repository\Eloquent\BaseRepository;

class UsersRepository extends BaseRepository
{
    public function model()
    {
        // TODO: Implement model() method.
        return User::class;
    }
}
