<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\GroupBuyProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class GroupByProductController extends Controller
{
    public function getMuachungList(){
        $products = GroupBuyProduct::with('getProduct')
            ->with('getOrders')
            ->where('start_date','<=',Carbon::now())
            ->where('end_date','>=',Carbon::now())
            ->paginate(20);
        return response()->json($products);
    }
    public function getMuaChungSingle(Request $request){
        $product = GroupBuyProduct::where('id',$request->id)
            ->with('getProduct')
            ->with('getOrders')
            ->first();
        return response()->json($product);
    }
}
