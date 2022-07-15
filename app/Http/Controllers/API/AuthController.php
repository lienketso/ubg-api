<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SettingRegister;
use App\Models\User;
use App\Repositories\CustomerRepository;
use App\Repositories\SettingRegisterRepository;
use App\Repositories\UbgxuPaylogRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Auth;
use Swagger\Annotations as SWG;
use Validator;
use App\Models\Customers;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    protected $customerRepository;
    protected $ubgxuPaylogRepository;
    protected $settingRegisterRepository;
    public function __construct(
        CustomerRepository $customerRepository,
        UbgxuPaylogRepository $ubgxuPaylogRepository,
        SettingRegisterRepository $settingRegisterRepository
    )
    {
        $this->customerRepository = $customerRepository;
        $this->ubgxuPaylogRepository = $ubgxuPaylogRepository;
    }
    /**
     * @SWG\Post(
     *     path="/api/auth/register",
     *     summary="Đăng ký thành viên",
     *     tags={"Users"},
     *     description="Login user, sử dụng basic authentication bằng tài khoản được cung cấp",
     *     security = { { "basicAuth": {} } },
     *     @SWG\Parameter(
     *         name="username",
     *         in="query",
     *         type="string",
     *         description="Username as phone or email address",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *     name="password",
     *     in="query",
     *     type="string",
     *     description="Input password",
     *     required=true,
     *     ),
     *     @SWG\Parameter(
     *     name="password_confirmation",
     *     in="query",
     *     type="string",
     *     description="Confirm password",
     *     required=true,
     *     ),
     *     @SWG\Parameter(
     *     name="affiliation_id",
     *     in="query",
     *     type="string",
     *     description="Options nếu có mã giới thiệu",
     *     required=false,
     *     ),
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
    public function register(Request $request){
//        $settingRegister = SettingRegister::where('type','app')
//            ->where('expire_date','>=',now()->toDateString())
//            ->where('start_date','>=',now()->toDateString())
//            ->first();
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
                'register_resource'=>'app'
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
                'register_resource'=>'app'
            ]);
        }
        //nếu có chương trình tặng xu
//        if($settingRegister && $settingRegister!=null){
//            $upxu = ['ubgxu'=>$settingRegister->total_plus_ubgxu];
//            $this->customerRepository->update($upxu,$customer->id);
//            //lưu lịch sử cộng xu khi đăng ký qua app
//            $data = [
//                'content'=>'Bạn được cộng '. $settingRegister->total_plus_ubgxu. ' xu từ chương trình đăng ký qua App',
//                'comeback'=>$settingRegister->total_plus_ubgxu,
//                'customer_id'=>$customer->id
//            ];
//            $this->ubgxuPaylogRepository->create($data);
//        }

        //nếu có mã giới thiệu
        if($request->input('affiliation_id') != null){
            $presenterUser = $this->customerRepository->findWhere(['affiliation_id'=>$request->input('affiliation_id')])->first();
            $aff = ['presenter_id'=>$presenterUser->id];
            $this->customerRepository->update($aff,$customer->id);
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
    /**
     * @SWG\Post(
     *     path="/api/auth/login",
     *     summary="Đăng nhập",
     *     tags={"Users"},
     *     description="Login user, sử dụng basic authentication bằng tài khoản được cung cấp",
     *     security = { { "basicAuth": {} } },
     *     @SWG\Parameter(
     *         name="username",
     *         in="query",
     *         type="string",
     *         description="Username as phone or email address",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *     name="password",
     *     in="query",
     *     type="string",
     *     description="Input password",
     *     required=true,
     *     ),
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
    /**
     * @SWG\Get(
     *     path="/api/auth/logout",
     *     summary="Đăng xuất",
     *     tags={"Users"},
     *     description="Logout user",
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
    public function logout(Request $request){
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }
    /**
     * @SWG\Get(
     *     path="/api/auth/user",
     *     summary="Lấy thông tin user",
     *     tags={"Users"},
     *     description="Return a user's information",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         type="string",
     *         description="Bearer Auth",
     *         required=true,
     *     ),
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
    public function user(Request $request)
    {
        return response()->json($request->user());
    }
    /**
     * @SWG\Post(
     *     path="/api/auth/update-user",
     *     summary="Cập nhật thông tin user",
     *     tags={"Users"},
     *     description="Update profile",
     *     security = { { "basicAuth": {} } },
     *     @SWG\Parameter(
     *         name="id",
     *         in="query",
     *         type="string",
     *         description="User id",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *     name="name",
     *     in="query",
     *     type="string",
     *     description="Input name want to change",
     *     required=true,
     *     ),
     *     @SWG\Parameter(
     *     name="email",
     *     in="query",
     *     type="string",
     *     description="Input email want to change",
     *     required=true,
     *     ),
     *     @SWG\Parameter(
     *     name="phone",
     *     in="query",
     *     type="string",
     *     description="Input phone want to change",
     *     required=true,
     *     ),
     *     @SWG\Parameter(
     *     name="dob",
     *     in="query",
     *     type="string",
     *     description="Input date of birth want to change",
     *     required=true,
     *     ),
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
    public function updateProfile(Request $request){
        $customer = Customers::find($request->id);
        $customer->update($request->all());
        return response()->json($customer);
    }
    /**
     * @SWG\Post(
     *     path="/api/auth/reset-password",
     *     summary="Đổi mật khẩu",
     *     tags={"Users"},
     *     description="Reset password",
     *     security = { { "basicAuth": {} } },
     *     @SWG\Parameter(
     *         name="username",
     *         in="query",
     *         type="string",
     *         description="Username as phone or email address",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *     name="password",
     *     in="query",
     *     type="string",
     *     description="Input password want to change",
     *     required=true,
     *     ),
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
    public function ForgotPassword(Request $request){
        $username = $request->username;
        $validator = Validator::make($request->all(),[
            'username' => 'required',
            'password' => 'required|string|min:6'
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
    /**
     * @SWG\Get(
     *     path="/api/auth/get-presenter-by-me",
     *     summary="Lấy thông tin khách hàng của cộng tác viên",
     *     tags={"Users"},
     *     description="Danh sách khách hàng của cộng tác viên đang đăng nhập",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         type="string",
     *         description="Bearer Auth",
     *         required=true,
     *     ),
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
    public function getPresenterByAuth(Request $request){

        if(!$request->user()){
            return response()->json(['error'=>'Authentication fail']);
        }
        try{
            $currentUserId = $request->user()->id;
            $listPresenter = $this->customerRepository->getModel()
                ->where('is_verified',1)
                ->whereNull('is_affiliater')
                ->where('presenter_id',$currentUserId)
                ->get();
            return response()->json($listPresenter);
        }catch (\Exception $e){
            return $e->getMessage();
        }


    }
    /**
     * @SWG\Get(
     *     path="/api/auth/permanent-delete",
     *     summary="Delete permanent",
     *     tags={"Users"},
     *     description="Delete permanent",
     *     security = { { "basicAuth": {} } },
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
    public function getPermanentDelete(){
        return response('Success permanent delete ', 200)
            ->header('Content-Type', 'text/plain');
    }

}
