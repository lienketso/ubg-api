<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\CustomerRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Auth;
use Validator;
use App\Models\Customers;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    protected $customerRepository;
    public function __construct(CustomerRepository $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    public function register(Request $request){
        if(is_numeric($request->username)){
            $validator = Validator::make($request->all(), [
                'username'=>'required|numeric|unique:ec_customers,phone',
                'password' => 'required|string|confirmed|min:6',
            ]);
            if($validator->fails()){
                return response()->json($validator->errors());
            }
            $customer = Customers::create([
                'phone'=>$request->username,
                'password'=>Hash::make($request->password),
                'name'=>'User default',
            ]);

            }else if(filter_var($request->username, FILTER_VALIDATE_EMAIL)){
            $validator = Validator::make($request->all(), [
                'username'=>'required|email|unique:ec_customers,email',
                'password' => 'required|string|confirmed|min:6',
            ]);
            if($validator->fails()){
                return response()->json($validator->errors());
            }
            $customer = Customers::create([
                'email'=>$request->username,
                'password'=>Hash::make($request->password),
                'name'=>'User default',
            ]);
        }

        $token = $customer->createToken('auth_token')->plainTextToken;

        return response()
            ->json(['data' => $customer,'access_token' => $token, 'token_type' => 'Bearer']);
    }

    protected function credentials($username,$password)
    {
        if(is_numeric($username)){
            return ['phone'=>$username,'password'=>$password];
        }
        elseif (filter_var($username, FILTER_VALIDATE_EMAIL)) {
            return ['email' => $username, 'password'=>$password];
        }
        return ['phone' => $username, 'password'=>$password];
    }

    public function login(Request $request){
        $validator = Validator::make($request->all(),[
            'username' => 'required',
            'password' => 'required'
        ]);
        if($validator->fails()){
            return response()->json($validator->errors());
        }

//        $credentials = request(['phone', 'password']);
        $credentials = $this->credentials($request->username,$request->password);
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
            )->toDateTimeString(),
            'message'=>'Login success !'
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

    public function ForgotPassword(Request $request){
        $username = $request->username;
        $validator = Validator::make($request->all(),[
            'username' => 'required',
            'password' => 'required|confirmed|string|min:6'
        ]);
        if($validator->fails()){
            return response()->json($validator->errors());
        }

            $info = $this->customerRepository->scopeQuery(function ($e) use ($username){
                return $e->where('phone',$username)->orWhere('email',$username);
            })->first();

            if($info){
                $data = [
                  'password'=>Hash::make($request->password)
                ];
                $this->customerRepository->update($data,$info->id);
                return response()->json(['message'=>'Change password successful !']);
            }else{
                return response()->json(['error'=>'User not found !']);
            }

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
