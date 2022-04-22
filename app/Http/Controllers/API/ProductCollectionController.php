<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ProductCollection;
use Illuminate\Http\Request;

class ProductCollectionController extends Controller
{
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
