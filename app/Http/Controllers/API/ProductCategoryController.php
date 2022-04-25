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
     *     description="Get list products by category",
     *     security = { { "basicAuth": {} } },
     *     @SWG\Parameter(
     *         name="id",
     *         in="query",
     *         type="string",
     *         description="input ID of category",
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
        $categories = ProductCategory::where('id',$request->id)
            ->with(['products'=>function($query) {
                return $query->where('ec_products.status','published')
                    ->paginate(10);
            }])
            ->first();
//        $allProducts = $categories->products->merge($categories->subproducts);
        return response()->json($categories);
    }

}
