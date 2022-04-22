<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MenuNode;
use Illuminate\Http\Request;

class MenuNodeController extends Controller
{
    public function getMainMenu(Request $request){
        $menu = MenuNode::where('menu_id','=',1)->where('parent_id','=',0)
            ->with('childs')->get(['id','parent_id','title','url','has_child']);
        return response()->json($menu);
    }

}
