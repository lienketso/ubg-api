<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\NewOrders;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Repositories\SettingRepository;
use Carbon\Carbon;
use http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
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
            return $q->where('ec_products.status', 'published')->limit(200);
        }])->whereIn('id', [103,52,100,95,104,181,104,92])->get();

        $settings = [
            'featured_categories' => $featuredCategories
        ];

        return response()->json($settings);
    }

    public function sendMailTest(SettingRepository $settingRepository){
        $setting = $settingRepository->getSetting('admin_email');
        $email = is_array($setting->value) ? $setting->value : (array)json_decode($setting->value, true);
        $email = collect(array_filter($email));
        $product_list = array(['product_name'=>'test','product_price'=>100000]);
        $customer_name = 'Nguyễn Thành An';
        $customer_phone = '0979 823 452';
        $customer_address = 'T3 Thăng Long Victory';
        $payment_method = 'Cod';
        $shipping_method = 'BestExpress';
        $created_order = Carbon::now();
        Mail::to($email)->send(new NewOrders(
            $product_list,
            $customer_name,
            $customer_phone,
            $customer_address,
            $shipping_method,
            $payment_method,
            $created_order
        ));
        return response()->json(
            [
                'success' => true,
                'message' => "Thank you for subscribing to our email, please check your inbox"
            ],
            200
        );


    }


}
