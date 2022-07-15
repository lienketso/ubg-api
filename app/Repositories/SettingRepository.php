<?php


namespace App\Repositories;

use App\Models\Setting;
use Prettus\Repository\Eloquent\BaseRepository;

class SettingRepository extends BaseRepository
{
    public function model()
    {
        return Setting::class;
    }

    public function getSetting($key){
        $setting = $this->findWhere(['key'=>$key])->first();
        return $setting;
    }

}
