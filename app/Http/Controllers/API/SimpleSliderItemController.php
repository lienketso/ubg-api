<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SimpleSliderItem;
use Illuminate\Http\Request;

class SimpleSliderItemController extends Controller
{
    public function getMainSlider(Request $request){
        try{
            $slider = SimpleSliderItem::orderBy('order','asc')
                ->where('simple_slider_id','=',1)
                ->get(['id','title','image','link','order']);
            return response()->json($slider);
        }catch (\Exception $e){
            return response()->json($e->getMessage());
        }

    }
}
