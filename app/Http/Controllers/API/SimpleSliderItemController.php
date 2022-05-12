<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SimpleSliderItem;
use Illuminate\Http\Request;

class SimpleSliderItemController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/api/main-slider",
     *     summary="Main slider",
     *     tags={"Slider"},
     *     description="Get Main slider",
     *     security = { { "basicAuth": {} } },
     *     @SWG\Parameter(
     *      name="slide_id",
     *      in="query",
     *      type="string",
     *      description="Loại slider ( main slider value 9 )",
     *      required=true
     *      ),
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
    public function getMainSlider(Request $request){
        $id = $request->slide_id;
        try{
            $slider = SimpleSliderItem::orderBy('order','asc')
                ->where('simple_slider_id','=',$id)
                ->get(['id','title','image','link','order']);
            $slider['base_url'] = 'https://ubgmart.com/storage/';
            return response()->json($slider);
        }catch (\Exception $e){
            return response()->json($e->getMessage());
        }

    }
}
