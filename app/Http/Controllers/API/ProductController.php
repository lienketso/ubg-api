<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Swagger\Annotations as SWG;

class ProductController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/api/single-product",
     *     summary="Chi tiết sản phẩm",
     *     tags={"Products"},
     *     description="Chi tiết sản phẩm",
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
    public function getSingleProduct(Request $request){
        $id = $request->id;
        $single = Product::where('id',$id)->first();
        if(!empty($single)){
            return response()->json($single);
        }else{
            return response()->json(['message'=>'No data !']);
        }

    }

    //sản phẩm liên quan
    /**
     * @SWG\Get(
     *     path="/api/related-product",
     *     summary="Sản phẩm liên quan",
     *     tags={"Products"},
     *     description="Sản phẩm liên quan",
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
    public function getRelatedProduct(Request $request){
        $product = Product::where('id',$request->id)->first();
        $related = Product::where('name','LIKE',$product->name.'%')->where('id','!=',$product->id)->get();
        return response()->json($related);
    }

}
