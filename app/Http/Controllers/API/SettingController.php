<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Repositories\SettingRepository;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * @var SettingRepository
     */
    protected $settingRepository;

    public function __construct(SettingRepository $settingRepository)
    {
        $this->settingRepository = $settingRepository;
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSettingUbg()
    {
        $ubg_exchange_vn = $this->settingRepository->getModel()->where('key','ubg_exchange_vnd')->first();
        $ubg_exchange_xu = $this->settingRepository->getModel()->where('key','ubg_exchange_xu')->first();
        $data = [
          'ubg_exchange_vnd'=>intval($ubg_exchange_vn->value),
          'ubg_exchange_xu'=>intval($ubg_exchange_xu->value)
        ];
        return response()->json($data);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGlobalSetting()
    {
        $featuredCategories = ProductCategory::with(['products' => function($q) {
            return $q->where('status', 'published')->take(6);
        }])->where('is_featured', 1)->where('status', 'published')->get();

        $settings = [
            'featured_caegories' => $featuredCategories
        ];

        return response()->json($settings);
    }
}
