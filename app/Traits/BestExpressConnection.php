<?php


namespace App\Traits;


use App\Models\StoreLocator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class BestExpressConnection
{
    /**
     * @var string
     */
    private static $username = 'V9Cus404397779';

    /**
     * @var string
     */
    private static $password = 'P654321';

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

            $package = self::customPackage($data['Weight']);

            $rawData = [
                'UserName' => self::$username,
                'ProductPrice' => floatval($data['ProductPrice']),
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
            ])->post('https://ems.vncpost.com/api/Service/EstimateFee', $rawData)
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


    public static function customPackage($weight)
    {
        $package = [
            'Weight' => $weight < 1000 ? 1000 : $weight,
            'Length' => 0,
            'Width' => 0,
            'Height' => 0
        ];

        // >5kg + 0,5kg
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


}
