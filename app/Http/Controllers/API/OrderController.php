<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\NewOrders;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderHistory;
use App\Models\StoreLocator;
use App\Models\Stores;
use App\Repositories\AddressRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\DiscountRepository;
use App\Repositories\OrderAddressRepository;
use App\Repositories\OrderProductRepository;
use App\Repositories\OrderRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\ProductRepository;
use App\Repositories\SettingRepository;
use App\Repositories\ShipmentRepository;
use App\Repositories\UsersRepository;
use App\Traits\BestExpressConnection;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Swagger\Annotations as SWG;

class OrderController extends Controller
{
    protected $orderRepository;
    protected $addressRepository;
    protected $orderAddressRepository;
    protected $productRepository;
    protected $orderProductRepository;
    protected $paymentRepository;
    protected $discountRepository;
    protected $usersRepository;
    protected $settingRepository;
    protected $customerRepository;
    protected $shipmentRepository;

    public function __construct(OrderRepository $orderRepository,
                                AddressRepository $addressRepository,
                                OrderAddressRepository $orderAddressRepository,
                                ProductRepository $productRepository,
                                OrderProductRepository $orderProductRepository,
                                PaymentRepository $paymentRepository,
                                DiscountRepository $discountRepository,
                                UsersRepository $usersRepository,
                                SettingRepository $settingRepository,
                                CustomerRepository $customerRepository,
                                ShipmentRepository $shipmentRepository
    )

    {
        $this->orderRepository = $orderRepository;
        $this->addressRepository = $addressRepository;
        $this->orderAddressRepository = $orderAddressRepository;
        $this->productRepository = $productRepository;
        $this->orderProductRepository = $orderProductRepository;
        $this->paymentRepository = $paymentRepository;
        $this->discountRepository = $discountRepository;
        $this->usersRepository = $usersRepository;
        $this->settingRepository = $settingRepository;
        $this->customerRepository = $customerRepository;
        $this->shipmentRepository = $shipmentRepository;
    }

    /**
     * @SWG\Get(
     *     path="/api/list-order",
     *     summary="Lấy ra danh sách đơn hàng",
     *     tags={"Order"},
     *     description="Danh sách đơn hàng ",
     *     security = { { "basicAuth": {} } },
     *     @SWG\Parameter(
     *         name="user_id",
     *         in="query",
     *         type="string",
     *         description="ID custommer login",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="status",
     *         in="query",
     *         type="string",
     *         description="Trạng thái đơn hàng : canceled : Huỷ , pending: Chưa xử lý, processing: Đang xử lý, completed: Đang giao hàng",
     *         required=false,
     *     ),
     *     @SWG\Parameter(
     *         name="is_confirmed",
     *         in="query",
     *         type="string",
     *         description="Xac nhan don hang : 1 : Đã xác nhận ,0 : Chưa xác nhận",
     *         required=false,
     *     ),
     *     @SWG\Parameter(
     *         name="is_finished",
     *         in="query",
     *         type="string",
     *         description="Don hang hoan thanh : 1 : Đã hoàn thành ,0 : Chưa hoàn thành",
     *         required=false,
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
    public function getListOrder(Request $request){

        $q = Order::query();
        try{
            if(!is_null($request->status) && $request->status){
                $q->where('status',$request->status);
            }
            if(!is_null($request->is_confirmed) && $request->is_confirmed){
                $q->where('is_confirmed',$request->is_confirmed);
            }
            if(!is_null($request->is_finished) && $request->is_finished){
                $q->where('is_finished',$request->is_finished);
            }
            $product = [];
            $orders = $q->orderBy('created_at','desc')
                ->where('user_id','=',$request->user_id)
                ->with('products')
                ->paginate(10);
            foreach($orders as $d){
                $product = $d->products;
                foreach ($product as $p){
                    $single = $this->productRepository->find($p->product_id);
                    $p['image'] = $single->images;
                }
            }
            return response()->json($orders);
        }catch (\Exception $e){
            return $e->getMessage();
        }

    }

    /**
     * @SWG\Get(
     *     path="/api/auth/get-order-delivering",
     *     summary="Đơn hàng đang vận chuyển",
     *     tags={"Order"},
     *     description="Lấy ra danh sách đơn hàng đang vận chuyển",
     *     security = { { "Bearer Token": {} } },
     *    @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         type="string",
     *         description="Bearer Auth",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *     name="status",
     *     in="query",
     *     type="string",
     *     description="Trạng thái ( delevering : đang vận chuyển )",
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
    public function getOrderDelivering(Request $request){
        $user = $request->user();
        if($user && !is_null($user)){
            $shipment = $this->shipmentRepository->with(['order'=>function($e) use($user){
                return $e->where('user_id',$user->id)->get();
            }])->findWhere(['status'=>$request->status])->all();
            $order = [];
            foreach($shipment as $d){
                $order[] = $d->order->toArray();
            }
            return response()->json(array_filter($order));
        }else{
            return response()->json(['message'=>'Vui long login !']);
        }

    }

    /**
     * @SWG\Get(
     *     path="/api/single-order",
     *     summary="Thông tin chi tiết đơn hàng",
     *     tags={"Order"},
     *     description="Chi tiết đơn hàng",
     *     security = { { "basicAuth": {} } },
     *     @SWG\Parameter(
     *         name="id",
     *         in="query",
     *         type="string",
     *         description="ID đơn hàng",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="user_id",
     *         in="query",
     *         type="string",
     *         description="Id customer Login",
     *         required=false,
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
    public function getSingleOrder(Request $request){
        $order = Order::where('id',$request->id)
            ->where('user_id',$request->user_id)
            ->with(['products','address'])->first();
        foreach ($order->products as $p){
            $single = $this->productRepository->find($p->product_id);
            if($single && $single!=null){
                $p['image'] = $single->images;
            }

        }
        return response()->json($order,200);
    }

    /**
     * @SWG\Post(
     *     path="/api/process-order-data",
     *     summary="Xử lý check out đơn hàng",
     *     tags={"Order"},
     *     description="Insert đơn hàng vào data, xử lý đơn hàng",
     *     security = { { "basicAuth": {} } },
     *     @SWG\Parameter(
     *         name="amount",
     *         in="query",
     *         type="double",
     *         description="Tổng tiền đơn hàng sau khi đã cộng trừ các phí, mã",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="sub_total",
     *         in="query",
     *         type="double",
     *         description="Tổng tiền đơn hàng",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="shipping_option",
     *         in="query",
     *         type="string",
     *         description="Default bestexpress",
     *         required=false,
     *     ),
     *     @SWG\Parameter(
     *         name="shipping_amount",
     *         in="query",
     *         type="string",
     *         description="Tiền ship",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="discount_amount",
     *         in="query",
     *         type="string",
     *         description="Tiền khuyến mại",
     *         required=false,
     *     ),
     *     @SWG\Parameter(
     *         name="ubg_xu_checkout",
     *         in="query",
     *         type="string",
     *         description="on, off tuỳ chọn sử dụng xu",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="coupon_code",
     *         in="query",
     *         type="string",
     *         description="Default null",
     *         required=false,
     *     ),
     *     @SWG\Parameter(
     *         name="address_id",
     *         in="query",
     *         type="string",
     *         description="Nếu value là 'new' thì thêm mới địa chỉ",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="name",
     *         in="query",
     *         type="string",
     *         description="Tên khách hàng",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="phone",
     *         in="query",
     *         type="string",
     *         description="Số điện thoại",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="address",
     *         in="query",
     *         type="string",
     *         description="Địa chỉ chi tiết",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="city",
     *         in="query",
     *         type="string",
     *         description="Tên tỉnh thành",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="state",
     *         in="query",
     *         type="string",
     *         description="Tên quận huyện",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="ward",
     *         in="query",
     *         type="string",
     *         description="Tên xã phường",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="payment_method",
     *         in="query",
     *         type="string",
     *         description="Phương thức thanh toán : cod, bank_transfer, vnpay",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="data",
     *         in="query",
     *         type="json",
     *         description="Thông tin đơn hàng",
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
    public function processInsertCart(Request $request){
        $currentUserId = $request->user()->id;
        if(!$currentUserId){
            $currentUserId = 0;
        }
        $validation = Validator::make($request->all(),[
            'amount'=>'required',
            'sub_total'=>'required',
            'name'=>'required',
            'phone'=>'required',
            'data'=>'required',
            'payment_method'=>'required',
            'city'=>'required',
            'state'=>'required',
            'shipping_amount'=>'required'
        ],[
            'amount.required'=>'Chưa nhập tổng số tiền đơn hàng',
            'sub_total.required'=>'Chưa nhập số tiền hàng',
            'name.required'=>'Vui lòng nhập họ tên',
            'phone.required'=>'Vui lòng nhập số điện thoại',
            'data.required'=>'Sản phẩm đơn hàng chưa có',
            'payment_method.required'=>'Chưa chọn phương thức thanh toán',
            'city.required'=>'Chưa chọn thành phố',
            'state.required'=>'Chưa chọn Quận Huyện',
            'shipping_amount'=>'Chưa có phí ship'
        ]);
        if($validation->fails()){
            return response()->json(['error'=>$validation->errors()]);
        }
        $orderAmount = $request->amount;
        $rawTotal = $request->sub_total;
        $shippingAmount = $request->shipping_amount;
        if($shippingAmount<=0 || is_null($shippingAmount)){
            $shippingAmount = 25000;
        }
        $couponCode = $request->coupon_code;
        $discountAmount = $request->discount_amount;
        $paidWithUbgXu = $request->ubg_xu_checkout == 'on';
        $paidUbgXuAmount = 0;

        if ($paidWithUbgXu) {
            $ubgxu = $request->user()->ubgxu;
            if ($ubgxu >= $orderAmount) {
                $orderAmount -= $ubgxu;
                $paidUbgXuAmount = $request->amount;
            } else {
                $orderAmount -= $ubgxu;
                $paidUbgXuAmount = $ubgxu;
            }
        }

        //check ctv
        $affiliateId = 0;
        if ($request->user()) {
            $affiliateId = $request->user()->presenter_id;
        }

        $request->merge([
            'amount'          => $orderAmount,
            'currency_id'     => 3,
            'user_id'         => $currentUserId,
            'shipping_method' => $request->input('shipping_method', 'default'),
            'shipping_option' => $request->input('shipping_option'),
            'shipping_amount' => (float)$shippingAmount,
            'tax_amount'      => 0,
            'sub_total'       => $request->input('sub_total',0),
            'coupon_code'     => $couponCode,
            'discount_amount' => $discountAmount,
            'status'          => 'pending',
            'is_finished'     => 1,
            'is_confirmed'    =>0,
            'affliate_user_id' => $affiliateId,
            'paid_by_ubgxu'  => $paidUbgXuAmount,
            'order_resource'  => 'app'
        ]);
        $order = Order::create($request->input());
        $sessionData['created_order'] = true;
        $sessionData['created_order_id'] = $order->id;
        $sessionData['paid_by_ubgxu'] = $paidUbgXuAmount;
        //Trừ xu trong tài khoản
        if ($paidWithUbgXu) {
            DB::beginTransaction();
            try{
                //Trừ xu
                DB::table('ec_customers')
                    ->where('id', $currentUserId)
                    ->decrement('ubgxu', $paidUbgXuAmount);
                //Ghi log giao dịch xu
                DB::table('ubgxu_pay_log')->insert([
                    'content' => 'Bạn vừa sử dụng '.number_format($paidUbgXuAmount).' xu cho việc thanh toán đơn hàng số '.$order->id,
                    'customer_id' => $currentUserId,
                    'created_at' => Carbon::now()
                ]);
                //Tạo giao dịch xu
                DB::table('ubgxu_transaction')->insert([
                    'customer_id' => $currentUserId,
                    'amount' => $paidUbgXuAmount,
                    'description' => 'Bạn vừa sử dụng '.number_format($paidUbgXuAmount).' xu cho việc thanh toán đơn hàng số '.$order->id,
                    'transaction_type' => 'decrease',
                    'transaction_source' => 'https://ubgmart.com',
                    'total_day_refund' => 0,
                    'rest_cashback_amount' => 0,
                    'compare_code' => $order->id,
                    'created_at' => Carbon::now(),
                    'status' => 'completed'
                ]);
            }catch (\Exception $e){
                DB::rollBack();
            }
            DB::commit();
        }

        //trừ đi mã discount
        $discount = $this->discountRepository->scopeQuery(function($q) use($couponCode){
            return $q->where('code',$couponCode )
                ->where('type','coupon')
                ->where('start_date','<=',now())
                ->where(function($query){
                    return $query->whereNull('end_date')
                        ->orWhere('end_date','>',now());
                });
        })->first();

        if (!empty($discount)) {
            $discount->total_used++;
            $this->discountRepository->updateOrCreate($discount);
        }
        //lấy ra address từ id
        $address_id = $request->address_id;
        $sessionData['address_id'] = $address_id;
        // nếu chọn thêm mới địa chỉ
        if($request->address_id=='new'){
                    $dataNewAddress = [
                        'name'=>$request->name,
                        'phone'=>$request->phone,
                        'address'=>$request->address,
                        'city'=>$request->city,
                        'state'=>$request->state,
                        'ward'=>$request->ward,
                        'country'=>'VN',
                        'customer_id'=>$currentUserId
                    ];
                    $rule = [
                        'name'=>'required|max:255',
                        'phone'=>'required|numeric',
                        'customer_id'=>'required'
                    ];
                    $validate = Validator::make($dataNewAddress,$rule);
                        if($validate->fails()){
                            return response()->json($validate->errors());
                        }
                    $newAddress = $this->addressRepository->create($dataNewAddress);
                    $address_id = $newAddress->id;

                    $sessionData['created_new_address'] = true;
                    $sessionData['created_new_address_id'] = $address_id;
        }

            $address = $this->addressRepository->find($address_id);
            //nếu đã có địa chỉ
            if(!empty($address)){
                $dataAddress = [
                    'name'     => $address->name,
                    'phone'    => $address->phone,
                    'email'    => $address->email,
                    'country'  => $address->country,
                    'state'    => $address->state,
                    'city'     => $address->city,
                    'address'  => $address->address,
                    'zip_code' => $address->zip_code,
                    'order_id' => $order->id,
                ];
                $createdOrderAddress = $this->createOrderAddress($dataAddress, $order->id);
                $sessionData['created_order_address'] = true;
            }
            //data cart
        try {
            $cartData = json_decode($request->data);
            $weight = 0;
            $storeIds = [];
            foreach ($cartData as $d) {
                $product = $this->productRepository->find($d->product_id);
                if ($product) {
                    if ($product->weight) {
                        $weight += $product->weight * $d->qty;
                    }
                    $storeIds[] = $product->store_id;
                }
            }
            $weight = $weight < 0.1 ? 0.1 : $weight;
            foreach ($cartData as $d) {
                $data = [
                    'order_id' => $order->id,
                    'product_id' => $d->product_id,
                    'product_name' => $d->product_name,
                    'qty' => $d->qty,
                    'weight' => $weight,
                    'price' => $d->price,
                    'tax_amount' => 0,
                    'options' => '[]'
                ];
                $this->orderProductRepository->create($data);
            }
            $sessionData['created_order_product'] = true;
            //create shipment order
            $ship = [
                'order_id'=>$order->id,
                'user_id'=>$currentUserId,
                'weight'=>$weight,
                'status'=>'pending',
                'cod_amount'=>$orderAmount,
                'cod_status'=>'pending',
                'price'=>$order->shipping_amount,
            ];
            $this->shipmentRepository->create($ship);
            //thanh toán
            $paymentData = [
                'order_id'=>$order->id,
                'customer_id'=>$currentUserId,
                'error'=>false,
                'message'=>false,
                'amount'=>$order->amount,
                'currency'=>'VND',
                'type'=>$request->payment_method,
                'charge_id'=>null,
                'status'=>'pending',
                'payment_channel'=>null
            ];
            if($request->get('vnp_ResponseCode') == '00'){
                $paymentMethod = 'vnpay';
            }else{
                $paymentMethod = $request->payment_method;
            }

            switch ($paymentMethod){
                case 'cod':
                    $paymentData['charge_id'] = Str::upper(Str::random(10));
                    $paymentData['payment_channel'] = $paymentMethod;
                    $paymentCreate = $this->paymentRepository->create($paymentData);
                    break;
                case 'bank_transfer':
                    $paymentData['charge_id'] = 'Bank-'.Str::upper(Str::random(10));
                    $paymentData['payment_channel'] = $paymentMethod;
                    $paymentCreate = $this->paymentRepository->create($paymentData);
                    break;
                case 'vnpay':
                    $paymentData['charge_id'] = $order->id.'-'.now().'-VNP-'.Str::random(6);
                    $paymentData['payment_channel'] = $paymentMethod;
                    $paymentCreate = $this->paymentRepository->create($paymentData);
                    break;
                default:
                    $paymentData['charge_id'] = Str::upper(Str::random(10));
                    $paymentData['payment_channel'] = 'cod';
                    $paymentCreate = $this->paymentRepository->create($paymentData);
                    break;
            }
            //update order payment & store
            $storeIds = array_unique($storeIds);
            if (count($storeIds) <= 1){
                $storeIds = implode ("",$storeIds);
            }
            $order->store_id = $storeIds;
            $order->payment_id = $paymentCreate->id;
            $order->save();

            //Trừ số lượng sản phẩm từ trong kho

            //Gửi email thông báo đơn hàng
            $customer = $this->customerRepository->find($currentUserId);
            $customer_name = $customer->name;
            $customer_phone = $customer->phone;
            $customer_address = $address->address;
            $setting = $this->settingRepository->getSetting('admin_email');
            $emailTo = is_array($setting->value) ? $setting->value : (array)json_decode($setting->value, true);
            $emailTo = collect(array_filter($emailTo));
            $product_list = $cartData;
            $shipping_method = $request->input('shipping_method', 'default');
            if($paymentMethod=='cod'){
                $paymentMethod = 'Thanh toán khi nhận hàng ( COD )';
            }
            $payment_method = $paymentMethod;
            $created_order = Carbon::now();
            $total_amount = $orderAmount;
            $shipping_amount = $shippingAmount;
            $discount_amount = $discountAmount;

            Mail::to($emailTo)->send(new NewOrders(
                $product_list,
                $customer_name,
                $customer_phone,
                $customer_address,
                $shipping_method,
                $payment_method,
                $created_order,
                $total_amount,
                $shipping_amount,
                $discount_amount
            ));

            return response()->json($sessionData);

        }catch (\Exception $e){
                return response()->json($e->getMessage());
        }
    }

    //create address order
    public function createOrderAddress(array $data, $orderAddressId = null){
        if ($orderAddressId) {
            $this->orderAddressRepository->updateOrCreate($data,['id' => $orderAddressId]);
        }
        $rules = [
            'name'    => 'required|max:255',
            'email'   => 'email|nullable|max:60',
            'phone'   => 'required|numeric',
            'state'   => 'required|max:120',
            'city'    => 'required|max:120',
            'address' => 'required|max:120',
        ];
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return false;
        }
        return $this->orderAddressRepository->create($data);
    }

    // danh sách địa chỉ của khách hàng
    /**
     * @SWG\Get(
     *     path="/api/address-list",
     *     summary="Danh sách địa chỉ của user",
     *     tags={"Order"},
     *     description="Danh sách địa chỉ của user, hiển thị trong lựa chọn đơn hàng",
     *     security = { { "basicAuth": {} } },
     *     @SWG\Parameter(
     *         name="customer_id",
     *         in="query",
     *         type="string",
     *         description="ID custommer",
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
    public function listAddress(Request $request){
        try{
            $data = $this->addressRepository->findWhere(['customer_id'=>$request->customer_id])->all();
            return response()->json($data);
        }catch (\Exception $e){
            return response()->json(['errors'=>$e->getMessage(),'message'=>'Lỗi !']);
        }

    }

    //get discount by coupon
    /**
     * @SWG\Get(
     *     path="/api/get-coupon-code",
     *     summary="Lấy ra thông tin giảm giá từ mã coupon",
     *     tags={"Promotion & Discount"},
     *     description="Mã coupon",
     *     security = { { "basicAuth": {} } },
     *     @SWG\Parameter(
     *         name="applied_coupon_code",
     *         in="query",
     *         type="string",
     *         description="Mã coupon",
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
    public function getDiscountByCode(Request $request){
        $code = $request->applied_coupon_code;
        $discount = $this->discountRepository->scopeQuery(function($q) use($code){
            return $q->where('code',$code)
                ->where('type','coupon')
                ->where('start_date','<=',now())
                ->where(function($query){
                    return $query->whereNull('end_date')
                        ->orWhere('end_date','>',now());
                });
        })->first();
        return response()->json($discount);
    }
    //get coupon code
    /**
     * @SWG\Get(
     *     path="/api/get-list-coupon-code",
     *     summary="Danh sách mã giảm giá",
     *     tags={"Promotion & Discount"},
     *     description="Mô tả - code : mã áp dụng khuyến mãi khi người dùng nhập từ input, start_date : Ngày bắt đầu áp dụng mã khuyến mãi, end_date : Ngày hiết hiệu lực (null thì vô hạn), quantity : số lượng mã khuyến mại, total_used: Tổng số mã đã được áp dụng, value: giá trị khuyến mại, type_option : Kiểu giá trị giảm giá ( percentage : giảm theo %, amount : giảm tiền, shipping : free ship, same-price : đồng giá ), type: Kiểu mã (coupon : theo mã khuyến mại, promotion : Chương trình khuyến mại ),target: Áp dụng khuyến mại cho ( all-orders : Tất cả đơn hàng, amount-minimum-order: tiền đơn hàng từ, group-products: Mua chung, specific-product: Áp dụng cho sản phẩm, customer : Áp dụng cho khách hàng, product-variant: sản phẩm có biến thể)",
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
    public function getAllDiscountCode(Request $request){
        $listDiscount = $this->discountRepository->getModel()
            ->where('type','coupon')
            ->where('start_date','<=',now())
            ->whereNull('end_date')
            ->orWhere('end_date','>',now())->get();
        return response()->json($listDiscount);
    }

    //Get promotion
    //get discount by coupon
    /**
     * @SWG\Get(
     *     path="/api/get-promotion-in-cart-value",
     *     summary="Lấy ra số tiền nếu có chương trình promotion",
     *     description="Promotion trừ tiền trong đơn hàng, sử dụng để trừ đi tổng số tiền đơn hàng",
     *     tags={"Promotion & Discount"},
     *     security = { { "basicAuth": {} } },
     *     @SWG\Parameter(
     *         name="cart_items",
     *         in="query",
     *         type="json",
     *         description="danh sách đơn hàng",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *     name="raw_total",
     *     in="query",
     *     type="string",
     *     description="Tổng tiền đơn hàng",
     *     required=true,
     *     ),
     *     @SWG\Parameter(
     *     name="total_qty",
     *     in="query",
     *     type="string",
     *     description="Tổng số lượng sản phẩm",
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
    public function getPromotionValue(Request $request){
        $cartItems = $request->cart_items; //giỏ hàng
        $promotionDiscountAmount = 0;
        $rawTotal = $request->raw_total; // Tổng tiền
        $countCart = $request->total_qty; //Tổng số lượng sản phẩm
        $promotions = $this->discountRepository->scopeQuery(function($q){
            return $q->where('type','promotion')
                ->where('start_date','<=',now())
                ->where(function ($query){
                    return $query->whereNull('end_date')->orWhere('end_date','>',now());
                });
        })->all();

        if(!empty($promotions)){
            foreach($promotions as $promotion){
                switch ($promotion->type_option){
                    case 'amount' :
                        switch ($promotion->target){
                            case 'amount-minimum-order':
                                if($promotion->min_order_price <= $rawTotal){
                                    $promotionDiscountAmount += $promotion->value;
                                }
                                break;
                            case 'all-orders':
                                $promotionDiscountAmount += $promotion->value;
                                break;
                            default:
                                if ($countCart >= $promotion->product_quantity) {
                                    $promotionDiscountAmount += $promotion->value;
                                }
                                break;
                        }
                        break;
                    case 'percentage':
                        switch ($promotion->target){
                            case 'amount-minimum-order':
                                if ($promotion->min_order_price <= $rawTotal) {
                                    $promotionDiscountAmount += $rawTotal * $promotion->value / 100;
                                }
                                break;
                            case 'all-orders':
                                $promotionDiscountAmount += $rawTotal * $promotion->value / 100;
                                break;
                            default:
                                if ($countCart >= $promotion->product_quantity) {
                                    $promotionDiscountAmount += $rawTotal * $promotion->value / 100;
                                }
                                break;
                        }
                        break;
                    case 'same-price':
                        if ($promotion->product_quantity > 1 && $countCart >= $promotion->product_quantity) {
                            $cartItems = json_decode($cartItems);
                            foreach ($cartItems as $item) {
                                if ($item->qty >= $promotion->product_quantity) {
                                    if (in_array($promotion->target, ['specific-product', 'product-variant']) &&
                                        in_array($item->id, $promotion->products()->pluck('product_id')->all())
                                    ) {
                                        $promotionDiscountAmount += ($item->price - $promotion->value) * $item->qty;
                                    } elseif ($product = $this->productRepository->findById($item->id)) {
                                        $productCollections = $product
                                            ->productCollections()
                                            ->pluck('ec_product_collections.id')->all();

                                        $discountProductCollections = $promotion
                                            ->productCollections()
                                            ->pluck('ec_product_collections.id')
                                            ->all();

                                        if (!empty(array_intersect($productCollections,
                                            $discountProductCollections))) {
                                            $promotionDiscountAmount += ($item->price - $promotion->value) * $item->qty;
                                        }
                                    }
                                }
                            }
                        }
                        break;
                }
            }
        }

        return $promotionDiscountAmount;
    }
    /**
     * @SWG\Get(
     *     path="/api/get-fee-shipping",
     *     summary="Lấy ra phí ship",
     *     description="Phí ship đơn hàng",
     *     tags={"Shipping"},
     *     security = { { "basicAuth": {} } },
     *     @SWG\Parameter(
     *         name="store_location",
     *         in="query",
     *         type="string",
     *         description="ID của store locator mặc định khi vào app",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *     name="customer_id",
     *     in="query",
     *     type="string",
     *     description="ID của khách hàng",
     *     required=true,
     *     ),
     *     @SWG\Parameter(
     *     name="order_total",
     *     in="query",
     *     type="string",
     *     description="Tổng tiền đơn hàng",
     *     required=true,
     *     ),
     *     @SWG\Parameter(
     *     name="weight",
     *     in="query",
     *     type="string",
     *     description="Tổng trọng lượng",
     *     required=true,
     *     ),
     *     @SWG\Parameter(
     *     name="city",
     *     in="query",
     *     type="string",
     *     description="Tên tỉnh thành",
     *     required=true,
     *     ),
     *     @SWG\Parameter(
     *     name="state",
     *     in="query",
     *     type="string",
     *     description="Tên quận huyện",
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
    public function getFeeShipping(Request $request){

        $storeLocatorSelected = $request->store_location;
        $store = new Stores();
        if($storeLocatorSelected == null){
            $customer = $this->usersRepository->find($request->customer_id);
            if(!empty($customer)) {
                if ($customer->store_locator_id != null) {
                    $defaultStore = $store->find($customer->store_locator_id);
                } else {
                    $defaultStore = $store->find(9);
                }
            }else{
                $defaultStore = $store->find(9);
            }
        }else {
            $defaultStore = $store->find($storeLocatorSelected);
        }

        $shippingData = [
            'ProductPrice' => $request->order_total,
            'COD' => 0,
            'ServiceId' => 12491,
            "DestCity"  =>  $request->city,
            "DestDistrict"  => $request->state,
            "SourceCity" => $defaultStore->city,
            "SourceDistrict" => $defaultStore->state,
            "Weight" => $request->weight,
        ];
        $bestExpressShippingFee = BestExpressConnection::calculateShippingPrice($shippingData);
        $result['bestexpress'] = [
                'name'  => 'BestExpress',
                'price' => $bestExpressShippingFee['TotalFeeVATWithDiscount']
        ];

        return response()->json($result);


    }

    //payment vnpay status
    /**
     * @SWG\Get(
     *     path="/api/get-vnpay-status",
     *     summary="Vnpay response",
     *     description="Trạng thái thanh toán qua vnpay",
     *     tags={"Payment"},
     *     security = { { "Bearer Token": {} } },
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         type="string",
     *         description="Bearer Auth",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="created_order_id",
     *         in="header",
     *         type="string",
     *         description="Id đơn hàng đã tạo khi create order",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="vnp_ResponseCode",
     *         in="header",
     *         type="string",
     *         description="Mã vnpay trả về",
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
    public function vnPayStatus(Request $request){
        $payment = $this->paymentRepository
            ->findWhere(['order_id'=>$request->order_id])
            ->first();
        if($request->vnp_ResponseCode=='00'){
            $payment->status = 'completed';
        }else{
            $payment->status = 'failed';
        }
        $payment->save();
        return response()->json(['message'=>'200']);
    }

    //get ubgxu with user login
    /**
     * @SWG\Get(
     *     path="/api/auth/get-ubgxu-by-user",
     *     summary="Lấy ra số xu khả dụng của user",
     *     description="Phí ship đơn hàng",
     *     tags={"Order"},
     *     security = { { "Bearer Token": {} } },
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
    public function getUbgXu(Request $request){
        $user = $request->user();
        if($user && !empty($user)){
            $ubgxu = intval($user->ubgxu);
            return response()->json(['ubgxu'=>$ubgxu]);
        }else{
            return response()->json(['error'=>'Không tồn tại người dùng']);
        }
    }

    //danh sach don hang ap dung ubgxu
    /**
     * @SWG\Get(
     *     path="/api/auth/get-order-paid-by-ubgxu",
     *     summary="Danh sách đơn hàng áp dụng ubgxu",
     *     description="Xác thực Bearer Token",
     *     tags={"Order"},
     *     security = { { "Bearer Token": {} } },
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         type="string",
     *         description="Bearer Auth",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="status",
     *         in="header",
     *         type="string",
     *         description="Theo trạng thái ( pending: Chưa xử lý, canceled : đã huỷ, processing: đang xử lý, completed: đã hoàn thành )",
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
    public function getListOrderPaidByXu(Request $request){
        $status = $request->status;
        try{
            $user = $request->user();
            if($user && !empty($user)){
                $listOrder = $this->orderRepository->getModel()
                    ->where('user_id',$user->id)
                    ->where('paid_by_ubgxu','>',0)
                    ->where('status',$status)
                    ->with(['products'])
                    ->get();

                foreach($listOrder as $key => $d){
                    $products = $d->products[0];
                    $infoProduct = $this->productRepository->find($products->product_id);
                    $listOrder[$key]['product_name'] = $products->product_name;
                    $listOrder[$key]['product_price'] = $products->price;
                    $listOrder[$key]['image'] = $infoProduct->images;
                }

                return response()->json($listOrder);
        }

        }catch (\Exception $e){
            return response()->json(['error'=>'authentication fail','exception'=>$e->getMessage()]);
        }
    }
    /**
     * @SWG\Get(
     *     path="/api/auth/confirm-order-received",
     *     summary="Đổi trạng thái đơn hàng ",
     *     description="Xác nhận đã nhận hàng hoặc hủy đơn hàng",
     *     tags={"Order"},
     *     security = { { "Bearer Token": {} } },
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         type="string",
     *         description="Bearer Auth",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="id",
     *         in="header",
     *         type="string",
     *         description="Id của đơn hàng",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="status",
     *         in="header",
     *         type="string",
     *         description="completed : Xác nhận đã nhận hàng , canceled : Hủy đơn hàng",
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
    public function ChangeOrderStatus(Request $request){
        try{
            $id = $request->id;
            $status = $request->status;
            $user = $request->user();
            if($user && !empty($user)){
                $order = $this->orderRepository->find($id);
                $order->status = $status;
                $order->save();

                //order history
                if($status=='completed'){
                    $data = [
                        'action'=>'update_status',
                        'description'=>'Xác nhận đã nhận được hàng từ khách hàng '.$user->name,
                        'order_id'=>$id,
                        'user_id'=>$user->id
                    ];
                }else if($status=='canceled'){
                    $data = [
                        'action'=>'update_status',
                        'description'=>'Xác nhận hủy đơn hàng từ khách hàng '.$user->name,
                        'order_id'=>$id,
                        'user_id'=>$user->id
                    ];
                }

                OrderHistory::create($data);
                return response()->json([
                    'message'=>'Xác nhận thành công',
                    'data'=>$order
                ]);
            }
        }catch (\Exception $e){
            return response()->json(['error'=>'authentication fail','exception'=>$e->getMessage()]);
        }
    }

}
