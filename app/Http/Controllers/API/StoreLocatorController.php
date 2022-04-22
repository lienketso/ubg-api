<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\StoreLocator;
use Illuminate\Http\Request;


class StoreLocatorController extends Controller
{
    //api get list all locator
    public function getListStore(){
       $store = StoreLocator::all();
       if(empty($store)){
           return response()->json(['message'=>'Empty data','resultCode'=>400]);
       }else{
           return response()->json(['data'=>$store,'status'=>200]);
       }

    }
}
