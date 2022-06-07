<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Repositories\SettingRepository;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    protected $settingRepository;
    public function __construct(SettingRepository $settingRepository)
    {
        $this->settingRepository = $settingRepository;
    }

    public function getSettingUbg(){
        $ubg_exchange_vn = $this->settingRepository->getModel()->where('key','ubg_exchange_vnd')->first();
        $ubg_exchange_xu = $this->settingRepository->getModel()->where('key','ubg_exchange_xu')->first();
        $data = [
          'ubg_exchange_vnd'=>intval($ubg_exchange_vn->value),
          'ubg_exchange_xu'=>intval($ubg_exchange_xu->value)
        ];
        return response()->json($data);
    }

}
