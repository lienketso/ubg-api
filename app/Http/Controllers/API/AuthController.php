<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SettingRegister;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\AddressRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\SettingRegisterRepository;
use App\Repositories\UbgxuPaylogRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Auth;
use Illuminate\Support\Facades\Http;
use phpDocumentor\Reflection\Types\String_;
use Swagger\Annotations as SWG;
use Validator;
use App\Models\Customers;
use Laravel\Socialite\Facades\Socialite;
use function Symfony\Component\String\Slugger\slug;

class AuthController extends Controller
{
    protected $customerRepository;
    protected $ubgxuPaylogRepository;
    protected $settingRegisterRepository;
    protected $addressRepository;
    public function __construct(
        CustomerRepository $customerRepository,
        UbgxuPaylogRepository $ubgxuPaylogRepository,
        SettingRegisterRepository $settingRegisterRepository,
        AddressRepository $addressRepository
    )
    {
        $this->customerRepository = $customerRepository;
        $this->ubgxuPaylogRepository = $ubgxuPaylogRepository;
        $this->addressRepository = $addressRepository;
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
    public function register(Request $request)
    {
//        $settingRegister = SettingRegister::where('type','app')
//            ->where('expire_date','>=',now()->toDateString())
//            ->where('start_date','>=',now()->toDateString())
//            ->first();
        if ($request->username) {
            $validator = Validator::make($request->all(), [
                'username' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|unique:ec_customers,phone|min:10',
                'password' => 'required|string|confirmed|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors());
            }

            //nếu có mã giới thiệu
            $presenter = '';
            if ($request->input('affiliation_id') != null) {
                $presenterUser = $this->customerRepository->findWhere(['affiliation_id' => $request->input('affiliation_id')])->first();
                if($presenterUser && !empty($presenterUser)){
                    $presenter = $presenterUser->id;
                }else{
                    return response()->json(['message'=>'Mã giới thiệu không đúng !']);
                }
            }

            $customer = Customers::create([
                'phone' => $request->username,
                'password' => Hash::make($request->password),
                'name' => 'User default',
                'register_resource' => 'app',
                'presenter_id'=>$presenter
            ]);

            $customer->affiliation_id = intval(1000000 + $customer->id);

            //call voice OTP
            $generator = "1357902468";
            $result = "";
            for ($i = 1; $i <= 6; $i++) {
                $result .= substr($generator, (rand() % (strlen($generator))), 1);
            }
            $otp = $result;
            $customer->phone_code = $otp;

            $customer->save();

            Http::withoutVerifying()
                ->withBasicAuth('ubg', '3hYvwtnNV4NtFvDL')
                ->post('https://otpubg.ezcall.vn/api/voiceotp.php',[
                    'otpcode' => $otp,
                    'phone' => $customer->phone
                ]);
        }

        $token = $customer->createToken('auth_token')->plainTextToken;

        return response()
            ->json(['data' => $customer, 'access_token' => $token, 'token_type' => 'Bearer']);
    }
    /**
     * @SWG\Post(
     *     path="/api/auth/phone-verify",
     *     summary="Kích hoạt OTP",
     *     tags={"Users"},
     *     description="Kích hoạt OTP cho user",
     *     security = { { "basicAuth": {} } },
     *     @SWG\Parameter(
     *         name="phone",
     *         in="query",
     *         type="string",
     *         description="số điện thoại",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *     name="otp",
     *     in="query",
     *     type="string",
     *     description="Mật khẩu OTP",
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
    public function phoneVerify(Request $request){
        $phone = $request->phone;
        $otp = $request->otp;
        $customer = $this->customerRepository->findWhere(['phone'=>$phone,'phone_code'=>$otp])->first();
        if ($customer != null) {
            $customer->is_verified = 1;
            $customer->save();
            return response()->json(['message'=>'verify successful']);
        }
    }

    //OTP Voice
    /**
     * @SWG\Get(
     *     path="/api/auth/phone-verify-refresh",
     *     summary="Gọi lại mã OTP",
     *     tags={"Users"},
     *     description="Gọilại mã OTP cho user",
     *     security = { { "basicAuth": {} } },
     *     @SWG\Parameter(
     *         name="phone",
     *         in="query",
     *         type="string",
     *         description="số điện thoại",
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
    public function refreshOtp(Request $request)
    {
        $phone = $request->phone;
        $generator = "1357902468";
        $result = "";
        for ($i = 1; $i <= 6; $i++) {
            $result .= substr($generator, (rand() % (strlen($generator))), 1);
        }
        $customer = $this->customerRepository->findWhere(['phone'=>$phone])->first();
        $customer->phone_code = $result;
        $customer->voice_count = $customer->voice_count-1;
        $customer->save();

        if($customer->voice_count<=0){
            return response()->json(['message'=>'Đã hết số lần gọi OTP, vui lòng liên hệ Admin']);
        }else{
            Http::withoutVerifying()
                ->withBasicAuth('ubg', '3hYvwtnNV4NtFvDL')
                ->post('https://otpubg.ezcall.vn/api/voiceotp.php',[
                    'otpcode' => $result,
                    'phone' => $customer->phone
                ]);

            return response()->json(['otp'=>$result]);
        }

    }


    protected function credentials($username, $password)
    {
        if (is_numeric($username)) {
            return ['phone' => $username, 'password' => $password];
        } elseif (filter_var($username, FILTER_VALIDATE_EMAIL)) {
            return ['email' => $username, 'password' => $password];
        }
        return ['phone' => $username, 'password' => $password];
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
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

//        $credentials = request(['phone', 'password']);
        $credentials = $this->credentials($request->username, $request->password);
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }
        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;
        if ($request->remember_me) {
            $token->expires_at = Carbon::now()->addWeeks(1);
        }

        $token->save();
        return response()->json([
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse(
                $tokenResult->token->expires_at
            )->toDateTimeString(),
            'message' => 'Login success !'
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
    public function logout(Request $request)
    {
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
    public function updateProfile(Request $request){
        $validate = Validator::make($request->all(),[
            'id'=>'required',
            'phone'=>'required|numeric'
        ]);

        if ($validate->fails()) {
            return response()->json(['error'=>$validate->errors()], 401);
        }

        $customer = Customers::find($request->id);

        $customer->name = $request->name;
        $customer->phone = $request->phone;
        $customer->email = $request->email;
        $customer->dob = $request->dob;
        $customer->save();
//      $customer->update($request->all());
        return response()->json($customer);
    }
    /**
     * @SWG\Post(
     *     path="/api/auth/update-avatar",
     *     summary="Cập nhật thông tin avatar",
     *     tags={"Users"},
     *     description="Update avatar, base path https://ubgmart.com/",
     *     security = { { "Bearer Token": {} } },
     *    @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         type="string",
     *         description="Bearer Auth",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *     name="file",
     *     in="query",
     *     type="file",
     *     description="Avatar",
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
    public function updateAvatar(Request $request){
        $validate = Validator::make($request->all(),[
            'file'=>'required|mimes:jpg,png,jpeg,gif|max:2048'
        ]);
        if ($validate->fails()) {
            return response()->json(['error'=>$validate->errors()], 401);
        }
        $user = $request->user();
        $customer = Customers::find($user->id);
        if($user && !empty($user)){

            if ($file = $request->file('file')) {

                $image = $file;
                $path = date('Y').'/'.date('m').'/'.date('d');
                $newnname = time().'-'.$image->getClientOriginalName();
                $newnname = str_replace(' ','-',$newnname);
                $image->move('/home/ubgmart.com/public_html/ubg-v2/public/storage/customers/',$newnname);
                $customer->avatar = 'storage/customers/'.$newnname;
                $customer->save();

                return response()->json([
                    "success" => true,
                    "message" => "File successfully uploaded",
                    "file" => $newnname,
                    "path"=>'/home/ubgmart.com/public_html/ubg-v2/public/storage/customers/'.$newnname
                ]);

            }
        }else{
            return response()->json(['message'=>'chua login account']);
        }


    }

    //thêm địa chỉ mới cho user
    /**
     * @SWG\Post(
     *     path="/api/auth/add-customer-address",
     *     summary="Thêm địa chỉ cho khách hàng",
     *     tags={"Users"},
     *     description="Thêm địa chỉ mới cho tài khoản khách hàng",
     *     security = { { "Bearer Token": {} } },
     *    @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         type="string",
     *         description="Bearer Auth",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="name",
     *         in="query",
     *         type="string",
     *         description="Họ và tên",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *     name="email",
     *     in="query",
     *     type="string",
     *     description="Địa chỉ email",
     *     required=false,
     *     ),
     *     @SWG\Parameter(
     *     name="phone",
     *     in="query",
     *     type="string",
     *     description="Số điện thoại",
     *     required=false,
     *     ),
     *      @SWG\Parameter(
     *     name="state",
     *     in="query",
     *     type="string",
     *     description="Quận Huyện",
     *     required=true,
     *     ),
     *      @SWG\Parameter(
     *     name="city",
     *     in="query",
     *     type="string",
     *     description="Thành phố",
     *     required=true,
     *     ),
     *     @SWG\Parameter(
     *     name="ward",
     *     in="query",
     *     type="string",
     *     description="Xã Phường",
     *     required=false,
     *     ),
     *     @SWG\Parameter(
     *     name="address",
     *     in="query",
     *     type="string",
     *     description="Địa chỉ chi tiết",
     *     required=false,
     *     ),
     *     @SWG\Parameter(
     *     name="is_default",
     *     in="query",
     *     type="integer",
     *     description="Là địa chỉ mặc định ( 1 , 0)",
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
    public function addAddress(Request $request){
        $user = $request->user();
        $validate = Validator::make($request->all(),[
            'name'=>'required',
            'email'=>'required|email',
            'phone'=>'required|numeric',
            'state'=>'required',
            'city'=>'required',
            'address'=>'required'
        ]);
        if ($validate->fails()) {
            return response()->json(['error'=>$validate->errors()], 401);
        }
        if($user && !empty($user)){
            $data = [
                'name'=>$request->name,
                'email'=>$request->email,
                'phone'=>$request->phone,
                'country'=>'VN',
                'state'=>$request->state,
                'city'=>$request->city,
                'ward'=>$request->ward,
                'address'=>$request->address,
                'is_default' => $request->is_default,
                'customer_id'=>$user->id
            ];
            //Nếu thêm mới chọn địa chỉ mặc định => update tất cả địa chỉ cũ về Is_default = 0
            if($request->is_default == 1){

                $listAddress = $this->addressRepository->findWhere(['customer_id'=>$user->id])->all();

                if($listAddress){
                    $this->addressRepository->updateOrCreate(['is_default' => 0], ['customer_id' => $user->id]);
                }
            }

           $addressCreate = $this->addressRepository->create($data);

           return response()->json(['message'=>'Create address success','data'=>$addressCreate]);
        }
    }

    //Sửa địa chỉ
    /**
     * @SWG\Post(
     *     path="/api/auth/update-customer-address",
     *     summary="Cập nhật địa chỉ khách hàng",
     *     tags={"Users"},
     *     description="Sửa địa chỉ khách hàng theo id",
     *     security = { { "Bearer Token": {} } },
     *    @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         type="string",
     *         description="Bearer Auth",
     *         required=true,
     *     ),
     *    @SWG\Parameter(
     *         name="id",
     *         in="query",
     *         type="string",
     *         description="Id của địa chỉ",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="name",
     *         in="query",
     *         type="string",
     *         description="Họ và tên",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *     name="email",
     *     in="query",
     *     type="string",
     *     description="Địa chỉ email",
     *     required=false,
     *     ),
     *     @SWG\Parameter(
     *     name="phone",
     *     in="query",
     *     type="string",
     *     description="Số điện thoại",
     *     required=false,
     *     ),
     *      @SWG\Parameter(
     *     name="state",
     *     in="query",
     *     type="string",
     *     description="Quận Huyện",
     *     required=true,
     *     ),
     *      @SWG\Parameter(
     *     name="city",
     *     in="query",
     *     type="string",
     *     description="Thành phố",
     *     required=true,
     *     ),
     *     @SWG\Parameter(
     *     name="ward",
     *     in="query",
     *     type="string",
     *     description="Xã Phường",
     *     required=false,
     *     ),
     *     @SWG\Parameter(
     *     name="address",
     *     in="query",
     *     type="string",
     *     description="Địa chỉ chi tiết",
     *     required=false,
     *     ),
     *     @SWG\Parameter(
     *     name="is_default",
     *     in="query",
     *     type="integer",
     *     description="Là địa chỉ mặc định ( 1 , 0)",
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
    public function updateAddress(Request $request){
        $id = $request->id;
        $user = $request->user();
        $validate = Validator::make($request->all(),[
            'name'=>'required',
            'email'=>'required|email',
            'phone'=>'required|numeric',
            'state'=>'required',
            'city'=>'required',
            'address'=>'required'
        ]);
        if ($validate->fails()) {
            return response()->json(['error'=>$validate->errors()], 401);
        }
        if($user && !empty($user)){

            //Nếu thêm mới chọn địa chỉ mặc định => update tất cả địa chỉ cũ về Is_default = 0
            if($request->is_default == 1){

                $listAddress = $this->addressRepository->findWhere(['customer_id'=>$user->id])->all();

                if($listAddress){
//                    $this->addressRepository->updateOrCreate(['is_default' => 0], ['customer_id' => $user->id]);
                    foreach($listAddress as $d){
                        $d->is_default = 0;
                        $d->save();
                    }
                }
            }

            $addressInfo = $this->addressRepository->findWhere(['id'=>$id,'customer_id'=>$user->id])->first();
            $update = $this->addressRepository->update($request->all(),$id);
            return response()->json(['message'=>'Update address success','data'=>$update]);
        }
    }
    /**
     * @SWG\Get(
     *     path="/api/auth/remove-customer-address",
     *     summary="Xóa địa chỉ người dùng",
     *     tags={"Users"},
     *     description="Xóa địa chỉ",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         type="string",
     *         description="Bearer Auth",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *          name="id",
     *          in="query",
     *          type="integer",
     *          description="Id của địa chỉ",
     *          required=true,
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
    public function removeAddress(Request $request){
        $user = $request->user();
        if(!empty($user)){
            $address = $this->addressRepository->findWhere(['id'=>$request->id,'customer_id'=>$user->id])->first();
            if($address && !is_null($address)){
                $remove = $this->addressRepository->delete($request->id);
                return response()->json(['message'=>'Xóa địa chỉ thành công !','data'=>$remove]);
            }else{
                return response()->json(['error'=>'Không tồn tại địa chỉ !']);
            }

        }else{
            return response()->json(['error'=>'Không tồn tại người dùng']);
        }

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
    public function ForgotPassword(Request $request)
    {
        $username = $request->username;
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required|string|min:6'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        $info = $this->customerRepository->scopeQuery(function ($e) use ($username) {
            return $e->where('phone', $username)->orWhere('email', $username);
        })->first();

        if ($info) {
            $data = [
                'password' => Hash::make($request->password)
            ];
            $this->customerRepository->update($data, $info->id);
            return response()->json(['message' => 'Change password successful !']);
        } else {
            return response()->json(['error' => 'User not found !']);
        }

    }

    public function LoginWithGoogle($driver, Request $request)
    {
        $user = Socialite::driver($driver)->user();
        $existingUser = Customers::where('email', $user->getEmail())->first();
        if ($existingUser) {
            auth()->login($existingUser, true);
        } else {
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
    public function getPresenterByAuth(Request $request)
    {

        if (!$request->user()) {
            return response()->json(['error' => 'Authentication fail']);
        }
        try {
            $currentUserId = $request->user()->id;
            $listPresenter = $this->customerRepository->getModel()
                ->where('is_verified', 1)
                ->whereNull('is_affiliater')
                ->where('presenter_id', $currentUserId)
                ->get();
            return response()->json($listPresenter);
        } catch (\Exception $e) {
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
    public function getPermanentDelete()
    {
        return response('Success permanent delete ', 200)
            ->header('Content-Type', 'text/plain');
    }
    /**
     * @SWG\Get(
     *     path="/api/auth/get-customer-wallet",
     *     summary="Thông tin ví cộng tác viên",
     *     tags={"Users"},
     *     description="Chỉ cộng tác viên mới có ví",
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
    public function getCustomerWallet(Request $request){
        $user = $request->user();
        $wallet = new Wallet();
        $infoWallet = $wallet->where('customer_id',$user->id)->first();
        if(!is_null($infoWallet)){
            return response()->json($infoWallet);
        }else{
            return response()->json(['error'=>'Ví chưa kích hoạt']);
        }
    }

}
