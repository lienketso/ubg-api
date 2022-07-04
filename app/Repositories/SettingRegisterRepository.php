<?php


namespace App\Repositories;


use App\Models\SettingRegister;
use Prettus\Repository\Eloquent\BaseRepository;

class SettingRegisterRepository extends BaseRepository
{
    public function model()
    {
        // TODO: Implement model() method.
        return SettingRegister::class;
    }
}
