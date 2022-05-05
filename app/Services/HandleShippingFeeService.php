<?php


namespace App\Services;


use Illuminate\Support\Arr;

class HandleShippingFeeService
{

    public function execute(array $data,$method=null){

    }

    public function getShippingFee(array $data, $method){
        $weight = Arr::get($data, 'weight', 0.1);
        $weight = $weight ?: 0.1;
        $orderTotal = Arr::get($data, 'order_total', 0);
        $country = Arr::get($data, 'country');
        $result = [];

    }

}
