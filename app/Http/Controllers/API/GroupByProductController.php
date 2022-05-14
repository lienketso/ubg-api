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
     *     summary="Danh sách sản phẩm mua chung",
     *     tags={"Products"},
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
     *     summary="Chi tiết sản phẩm mua chung",
     *     tags={"Products"},
     *     description="Chi tiết sản phẩm mua chung",
     *     security = { { "basicAuth": {} } },
     *     @SWG\Parameter(
     *         name="id",
     *         in="query",
     *         type="string",
     *         description="ID sản phẩm mua chung",
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
        $single = $product['getProduct'];
        $single['type'] = 'muachung';
        return response()->json($single);
    }
    /**
     * @SWG\Get(
     *     path="/api/group-product-related",
     *     summary="sản phẩm mua chung khác",
     *     tags={"Products"},
     *     description="Danh sách sản phẩm mua chung khác",
     *     security = { { "basicAuth": {} } },
     *     @SWG\Parameter(
     *         name="group_id",
     *         in="query",
     *         type="string",
     *         description="ID sản phẩm mua chung single",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="limit",
     *         in="query",
     *         type="string",
     *         description="Số sản phẩm cần lấy",
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
    public function getRelateMuaChung(Request $request){
        $group_id = $request->group_id;
        $limit = $request->limit;
        $product = GroupBuyProduct::with('getProduct')
            ->where('id','!=',$group_id)
            ->where('start_date','<=',Carbon::now())
            ->where('end_date','>=',Carbon::now())
            ->paginate($limit);
        return response()->json($product);
    }

}
