<?php
/**
 * BestExpressConnection.php
 * Created by: trainheartnet
 * Created at: 06/01/2022
 * Contact me at: longlengoc90@gmail.com
 */


namespace Botble\Ecommerce\Traits;

use App\Models\StoreLocator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;

class BestExpressConnection
{
    /**
     * @var string
     * API cus324593 - Best123456
     * V9Cus404397779 - P654321
     */
    private static $username = 'cus324593';


    /**
     * @var string
     */
    private static $password = 'Best123456';

    /**
     * authen get token
     * @return mixed|string
     * @throws \Illuminate\Http\Client\RequestException
     */
    public static function getToken()
    {
        if (Session::get('bestexpress_token') != null) {
            $res = Http::post('https://ems.vncpost.com/User/Login', [
                'USERNAME' => self::$username,
                'PASSWORD' => self::$password
            ])
                ->throw(function ($response, $e) {
                    return '';
                });

            if ($res->successful()) {
                Session::put('bestexpress_token', $res->json()['token']);
                return $res->json()['token'];
            }
        } else {
            return Session::get('bestexpress_token');
        }
    }

    public static function calculateShippingPrice($data)
    {
        try {
            //$token = self::getToken();

//            $SourceCity = self::mapProviceWithViettelApi($data['SourceCity']);
//            $SourceDistrict = self::mapDistrictWithViettelApi($data['SourceDistrict'], $SourceCity);
//
//            $DestCity = self::mapProviceWithViettelApi($data['DestCity']);
//            $DestDistrict = self::mapDistrictWithViettelApi($data['DestDistrict'], $DestCity);

            $package = self::customPackage($data['Weight']);

            $rawData = [
                'UserName' => self::$username,
                'ProductPrice' => $data['ProductPrice'],
                'COD' => $data['COD'],
                'ServiceId' => 12491,
                "DestCity"  => $data['DestCity'],
                "DestDistrict"  => $data['DestDistrict'],
                "SourceCity" => $data['SourceCity'],
                "SourceDistrict" => $data['SourceDistrict'],
                "Weight" => intval($package['Weight']),
            ];

            if ($package['Length'] != 0) {
                $rawData['Length'] = $package['Length'];
                $rawData['Width'] = $package['Width'];
                $rawData['Height'] = $package['Height'];
            }

            $res = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->post('http://sgp-seaedi.800best.com/VietNamV3/v3/api/process/sears/Service/EstimateFee', $rawData)
                ->throw(function ($response, $e) {
                    return ['TotalFeeVATWithDiscount' => 0];
                });

            if ($res->successful()) {
                $resData =  $res->json();
                return $res['Result'] == 1 ? [
                    'TotalFeeVATWithDiscount' => $resData['data']['TotalFeeVATWithDiscount']] : ['TotalFeeVATWithDiscount' => 0];
            } else {
                return ['TotalFeeVATWithDiscount' => 0];
            }

        } catch (\Exception $e) {
            return ['TotalFeeVATWithDiscount' => 0];
        }
    }

    /**
     * @return StoreLocator|StoreLocator[]|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Collection|Model|object|null
     */
    public static function getDefaultStoreLocation()
    {
        $storeLocatorSelected = session('store_location');
        $storeLocator = new StoreLocator();
        if ($storeLocatorSelected == null) {
            if (auth('customer')->check()) {
                $customer = auth('customer')->user();
                if ($customer->store_locator_id != null) {
                    $default = $storeLocator->find($customer->store_locator_id);
                } else {
                    $default = $storeLocator->where('is_primary', 1)
                        ->where('is_shipping_location', 1)
                        ->first();
                }
            } else {
                $default = $storeLocator->where('is_primary', 1)
                    ->where('is_shipping_location', 1)
                    ->first();
            }
        } else {
            $default = $storeLocator->find($storeLocatorSelected);
        }

        return $default;
    }

    /**
     * @param $provinceName
     * @return mixed|string
     * @throws \Illuminate\Http\Client\RequestException
     */
    public static function mapProviceWithViettelApi($provinceName)
    {
        $provinces = self::getProvince();
        $provinceNameSlug = Str::slug($provinceName);
        try {
            foreach ($provinces as $province) {
                $provinceSlug = Str::slug($province['PROVINCE_NAME']);
                if (strlen($provinceSlug) >= strlen($provinceNameSlug)) {
                    if (strpos($provinceSlug, $provinceNameSlug) !== false) {
                        return $province['PROVINCE_ID'];
                    }
                } else {
                    if (strpos($provinceNameSlug, $provinceSlug) !== false) {
                        return $province['PROVINCE_ID'];
                    }
                }
            }
        } catch (\Exception $e) {
            return 1;
        }
    }

    /**
     * @param $districtName
     * @return mixed
     * @throws \Illuminate\Http\Client\RequestException
     */
    public static function mapDistrictWithViettelApi($districtName, $provinceId)
    {
        $districts = self::getDistricts($provinceId);
        $districtNameSlug = Str::slug($districtName);

        try {
            foreach ($districts as $district) {
                $districtSlug = Str::slug($district['DISTRICT_NAME']);
                if (strlen($districtSlug) >= strlen($districtNameSlug)) {
                    if (strpos($districtSlug, $districtNameSlug) !== false) {
//                        if ($district['DISTRICT_ID'] == 1231) {
//                            return 50;
//                        }
                        return $district['DISTRICT_ID'];
                    }
                } else {
                    if (strpos($districtNameSlug, $districtSlug) !== false) {
//                        if ($district['DISTRICT_ID'] == 1231) {
//                            return 50;
//                        }
                        return $district['DISTRICT_ID'];
                    }
                }
            }
        } catch (\Exception $e) {
            return 1;
        }
    }

    /**
     * @return mixed|string
     * @throws \Illuminate\Http\Client\RequestException
     */
    public static function getProvince()
    {
        $res = Http::get('https://partner.viettelpost.vn/v2/categories/listProvinceById?provinceId=-1')
            ->throw(function ($response, $e) {
                return false;
            })->json();

        return isset($res['data']) ? $res['data'] : false;
    }

    /**
     * Get districts By provinceId
     * @param $provinceId
     * @return false|mixed
     * @throws \Illuminate\Http\Client\RequestException
     */
    public static function getDistricts($provinceId)
    {
        $res = Http::get('https://partner.viettelpost.vn/v2/categories/listDistrict?provinceId='.$provinceId)
            ->throw(function ($response, $e) {
                return [];
            })->json();

        return isset($res['data']) ? $res['data'] : [];
    }

    /**
     * Get wards by districtId
     * @param $districtId
     * @return false|mixed
     * @throws \Illuminate\Http\Client\RequestException
     */
    public static function getWards($districtId)
    {
        $res = Http::get('https://partner.viettelpost.vn/v2/categories/listWards?districtId='.$districtId)
            ->throw(function ($response, $e) {
                return false;
            })->json();


        return isset($res['data']) ? $res['data'] : false;
    }

    /**
     * @param $weight
     * @return array|int[]
     */
    public static function customPackage($weight)
    {
        $package = [
            'Weight' => $weight < 1000 ? 1000 : $weight,
            'Length' => 0,
            'Width' => 0,
            'Height' => 0
        ];

        //vậy mình set như vầy giúp em đc ko >5kg + 0,5kg
        //Từ 10-15kg thì tăng 1kg
        //Từ >20kg thì tăng 1,5kg

        if ($weight >= 5000 & $weight < 10000) {
            $package = [
                'Weight' => $weight + 1000,
                'Length' => 0,
                'Width' => 0,
                'Height' => 0
            ];
        } elseif ($weight >= 10000 & $weight < 15000) {
            $package = [
                'Weight' => $weight + 1500,
                'Length' => 0,
                'Width' => 0,
                'Height' => 0
            ];
        } elseif ($weight > 20000) {
            $package = [
                'Weight' => $weight + 2000,
                'Length' => 0,
                'Width' => 0,
                'Height' => 0
            ];
        }

        return $package;
    }

    public static function createOrder()
    {
        $token = self::getToken();

        $rawData = [];

        $res = Http::withToken($token)->withHeaders([
            'Content-Type' => 'application/json'
        ])->post('http://sgp-seaedi.800best.com/VietNamV3/v3/api/process/sears/Order/Add', $rawData)
            ->throw(function ($response, $e) {

            });
    }
}
