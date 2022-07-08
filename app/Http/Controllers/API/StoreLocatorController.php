<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\StoreLocator;
use App\Models\Stores;
use Illuminate\Http\Request;


class StoreLocatorController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/api/list-locator",
     *     summary="Danh sách kho hàng",
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
       $store = Stores::where('status','published')->get();
       if(empty($store)){
           return response()->json(['message'=>'Empty data','resultCode'=>400]);
       }else{
           return response()->json($store);
       }

    }
    /**
     * @SWG\Get(
     *     path="/api/single-locator",
     *     summary="Chi tiết kho hàng",
     *     description="Thông tin chi tiết của kho hàng",
     *     security = { { "basicAuth": {} } },
     *     @SWG\Parameter(
     *     name="id",
     *     in="query",
     *     type="string",
     *     description="Nhập id của kho hàng",
     *     required=true,
     *     ),
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
    public function getSingleStore(Request $request){
        $id = $request->id;
        $singleStore = Stores::find(['id'=>$id]);
        return response()->json($singleStore);
    }
}
