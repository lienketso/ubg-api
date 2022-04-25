<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\StoreLocator;
use Illuminate\Http\Request;


class StoreLocatorController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/api/list-locator",
     *     description="List danh sách tất cả các địa điểm kho",
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
    public function getListStore(){
       $store = StoreLocator::all();
       if(empty($store)){
           return response()->json(['message'=>'Empty data','resultCode'=>400]);
       }else{
           return response()->json($store);
       }

    }
}
