<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use Illuminate\Http\Request;

class FlashSaleController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/api/flash-sale",
     *     summary="Danh sách sản phẩm flash sale",
     *     description="Get flash sale",
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
    public function getFlashSale(Request $request){
        try{
            $flashSale = FlashSale::where('status','published')
                ->notExpired()
                ->with(['products'=>function($query){
                    return $query->where('status','published')->get();
                }])->get(['id','name','end_date','status']);
            if (!$flashSale) {
                return response()->json(['message'=>'No product in flash sale']);
            }
            return response()->json($flashSale);
        }catch (\Exception $e){
            return response()->json($e->getMessage());
        }

    }

}
