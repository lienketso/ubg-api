<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Repositories\SettingRepository;
use Illuminate\Http\Request;
use Swagger\Annotations as SWG;

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
    /**
     * @SWG\Get(
     *     path="/api/get-ubgxu-exchange",
     *     summary="Thông tin cấu hình ubg xu",
     *     tags={"Settings"},
     *     description="Thông tin cấu hình ubg xu",
     *     security = { { "basicAuth": {} } },
     *     @SWG\Response(
     *         response=200,
     *         description="OK",
     *     ),
     *     @SWG\Response(
     *         response=422,
     *         description="Missing Data"
     *     )
     * )
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
    /**
     * @SWG\Get(
     *     path="/api/settings",
     *     summary="Thông tin cấu hình danh mục sản phẩm",
     *     tags={"Settings"},
     *     description="Thông tin cấu hình danh mục sản phẩm",
     *     security = { { "basicAuth": {} } },
     *     @SWG\Response(
     *         response=200,
     *         description="OK",
     *     ),
     *     @SWG\Response(
     *         response=422,
     *         description="Missing Data"
     *     )
     * )
     */
    public function getGlobalSetting()
    {
        $featuredCategories = ProductCategory::with(['products' => function($q) {
            return $q->where('ec_products.status', 'published')->limit(10);
        }])->whereIn('id', [103,52,100,95,104,181,104,92])->where('status', 'published')->get();

        $settings = [
            'featured_categories' => $featuredCategories
        ];

        return response()->json($settings);
    }


}
