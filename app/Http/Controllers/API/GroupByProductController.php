<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\GroupBuyProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Swagger\Annotations as SWG;

class GroupByProductController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/api/group-product-list",
     *     description="Sản phẩm mua chung",
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
    public function getMuachungList(){
        $products = GroupBuyProduct::with('getProduct')
            ->with('getOrders')
            ->where('start_date','<=',Carbon::now())
            ->where('end_date','>=',Carbon::now())
            ->paginate(20);
        return response()->json($products);
    }
    /**
     * @SWG\Get(
     *     path="/api/group-product-single",
     *     description="Chi tiết sản phẩm mua chung",
     *     security = { { "basicAuth": {} } },
     *     @SWG\Parameter(
     *         name="id",
     *         in="query",
     *         type="string",
     *         description="ID sản phẩm",
     *         required=true,
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
    public function getMuaChungSingle(Request $request){
        $product = GroupBuyProduct::where('id',$request->id)
            ->with('getProduct')
            ->with('getOrders')
            ->first();
        return response()->json($product);
    }
}
