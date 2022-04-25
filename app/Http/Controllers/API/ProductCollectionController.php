<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ProductCollection;
use Illuminate\Http\Request;
use Swagger\Annotations as SWG;

class ProductCollectionController extends Controller
{
    /**
     * @SWG\Post(
     *     path="/api/product-collection",
     *     description="Sản phẩm khuyến mại, discoun, bán chạy...",
     *     security = { { "basicAuth": {} } },
     *     @SWG\Parameter(
     *         name="id",
     *         in="query",
     *         type="string",
     *         description="id = 1 : Sản phẩm mới ; id=2 : SP bán chạy; id=3 : Khuyến mại ; id=4 : Gia vị; id=5: Thực phẩm; id=6: Nhóm bán chạy",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *      name="limit",
     *      in="query",
     *      type="string",
     *      description="Phân trang",
     *      required=true
     *      ),
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
    public function getProductByCollection(Request $request){
        $product_collection_id = $request->id;
        $limit = $request->limit;
        //id = 1 : Sản phẩm mới ; id=2 : SP bán chạy; id=3 : Khuyến mại ; id=4 : Gia vị; id=5: Thực phẩm; id=6: Nhóm bán chạy
        if($product_collection_id){
            $collection = ProductCollection::where('status','published')->where('id',$product_collection_id)
                ->with(['products'=>function($query) use($limit){
                    return $query->where('status','published')
                        ->take($limit)
                        ->get(['name','price']);
                }])->get(['id','name','description','status']);
            return response()->json($collection);
        }else{
            return response()->json(['message'=>'Param id not found !']);
        }

    }
}
