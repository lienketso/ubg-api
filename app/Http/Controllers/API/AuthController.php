<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Auth;
use Validator;
use App\Models\Customers;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{

    public function register(Request $request){
        $validator = Validator::make($request->all(), [
            'phone'=>'required|min:9|max:12|unique:ec_customers',
            'password' => 'required|string|confirmed|min:6',
        ]);
        if($validator->fails()){
            return response()->json($validator->errors());
        }
        $customer = Customers::create([
           'phone'=>str_replace(' ','',$request->phone),
            'password'=>Hash::make($request->password),
            'name'=>'User default',
            'email'=>'ubg-default@ubg.vn'
        ]);
        $token = $customer->createToken('auth_token')->plainTextToken;

        return response()
            ->json(['data' => $customer,'access_token' => $token, 'token_type' => 'Bearer']);
    }

    public function login(Request $request){
        $validator = Validator::make($request->all(),[
            'phone' => 'required',
            'password' => 'required'
        ]);
        if($validator->fails()){
            return response()->json($validator->errors());
        }

        $credentials = request(['phone', 'password']);

        if(!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }
        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;
        if ($request->remember_me){
            $token->expires_at = Carbon::now()->addWeeks(1);
        }

        $token->save();
        return response()->json([
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse(
                $tokenResult->token->expires_at
            )->toDateTimeString()
        ]);
    }

    public function logout(Request $request){
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function updateProfile(Request $request){
        $customer = Customers::find($request->id);
        $customer->update($request->all());
        return response()->json($customer);
    }

    public function LoginWithGoogle($driver,Request $request){
            $user = Socialite::driver($driver)->user();
            $existingUser = Customers::where('email',$user->getEmail())->first();
            if($existingUser){
                auth()->login($existingUser,true);
            }else{
                $newUser = new Customers;
                $newUser->name = $user->getName();
                $newUser->email = $user->getEmail();
                $newUser->confirmed_at = now();
                $newUser->avatar = $user->getAvatar();
                $newUser->save();
                auth()->login($newUser, true);
            }
    }

}
