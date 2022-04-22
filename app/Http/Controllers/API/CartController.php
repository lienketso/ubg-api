<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Cart;


class CartController extends Controller
{
    public function addToCart(Request $request){
        $validator = Validator::make($request->all(), [
            'product_id' => 'required',
            'quantity' => 'required|numeric|min:1|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 400);
        }
        $userID = $request->user()->id;

        $productID = $request->product_id;
        $quantity = $request->quantity;

        $product = Product::find($productID);

        if(!$product){
            return response()->json(['message'=>'Product not found']);
        }

        $cart = \Cart::session($userID)->add([
            'id'=>$productID,
            'name'=>$product->name,
            'price'=>$product->price,
            'quantity'=>$quantity
        ]);
        $cartItems = \Cart::getContent();
        return response()->json(['data'=>$cartItems,'message'=>'success full add product in cart ']);

    }

    public function getCartList(Request $request){
        $cartItems = \Cart::getContent();
        return response()->json($cartItems);
    }

    public function updateCart(Request $request){
        \Cart::update($request->id, array(
            'quantity' => $request->quantity,
        ));
        $cartItems = \Cart::getContent();
        return response()->json($cartItems);
    }




}
