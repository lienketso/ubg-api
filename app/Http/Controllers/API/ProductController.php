<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
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
    public function getRelatedProduct(Request $request){
        $product = Product::where('id',$request->id)->first();
        $related = Product::where('name','LIKE',$product->name.'%')->where('id','!=',$product->id)->get();
        return response()->json($related);
    }

}
