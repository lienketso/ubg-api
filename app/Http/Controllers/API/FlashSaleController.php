<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use Illuminate\Http\Request;

class FlashSaleController extends Controller
{
    public function getFlashSale(Request $request){
        try{
            $flashSale = FlashSale::where('status','published')
                ->notExpired()
                ->with(['products'=>function($query){
                    return $query->where('status','published')->get();
                }])->get(['id','name','end_date','status']);
            if (!$flashSale) {
                return response()->json(['message'=>'No product in flash sale']);
            }
            return response()->json($flashSale);
        }catch (\Exception $e){
            return response()->json($e->getMessage());
        }

    }

}
