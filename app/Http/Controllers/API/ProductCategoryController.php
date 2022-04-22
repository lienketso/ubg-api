<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
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
