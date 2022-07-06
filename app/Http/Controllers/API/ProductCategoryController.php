<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Swagger\Annotations as SWG;

class ProductCategoryController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/api/main-product-category",
     *     summary="Danh sách danh mục sản phẩm",
     *     tags={"Products"},
     *     description="Get list main category",
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
    public function getProductCategory(Request $request){
        try{
            $categories = ProductCategory::orderBy('order','asc')
                ->where('parent_id','=',0)
                ->where('status','published')
                ->with('childs')
                ->get(['id','name','parent_id','status','order','image','is_featured']);
            return response()->json($categories);
        }catch (\Exception $e){
            return response()->json($e->getMessage());
        }

    }
    /**
     * @SWG\Post(
     *     path="/api/product-by-category",
     *     summary="Danh sách sản phẩm theo danh mục",
     *     tags={"Products"},
     *     description="Get list products by category",
     *     security = { { "basicAuth": {} } },
     *     @SWG\Parameter(
     *         name="id",
     *         in="query",
     *         type="string",
     *         description="input ID of category",
     *         required=true,
     *     ),
     *      @SWG\Parameter(
     *         name="limit",
     *         in="query",
     *         type="string",
     *         description="Limit sản phẩm",
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
    public function getProductByCategory(Request $request){
        $limit = $request->limit;
        $categories = ProductCategory::where('id',$request->id)
            ->with(['products'=>function($query) use($limit) {
                return $query->with(['stores'])->where('ec_products.status','published')
                    ->get()
                    ->take($limit);
            }])
            ->first();
        $categories['base_url'] ='https://ubgmart.com/storage/';


        return response()->json($categories);
    }

}
