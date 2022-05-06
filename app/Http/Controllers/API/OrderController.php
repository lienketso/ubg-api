<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\StoreLocator;
use App\Repositories\AddressRepository;
use App\Repositories\DiscountRepository;
use App\Repositories\OrderAddressRepository;
use App\Repositories\OrderProductRepository;
use App\Repositories\OrderRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UsersRepository;
use App\Traits\BestExpressConnection;
use Illuminate\Http\Request;
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

    public function __construct(OrderRepository $orderRepository,
                                AddressRepository $addressRepository,
                                OrderAddressRepository $orderAddressRepository,
                                ProductRepository $productRepository,
                                OrderProductRepository $orderProductRepository,
                                PaymentRepository $paymentRepository,
                                DiscountRepository $discountRepository,
                                UsersRepository $usersRepository
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
     *         description="pending, canceled, completed",
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
            if($request->status){
                $q = $q->where('status',$request->status);
            }
            $orders = $q->orderBy('created_at','desc')
                ->where('user_id','=',$request->user_id)
                ->paginate(10);
            return response()->json($orders);
        }catch (\Exception $e){
            return $e->getMessage();
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
        $request->merge([
            'amount'          => $request->amount,
            'currency_id'     => 3,
            'user_id'         => $currentUserId,
            'shipping_method' => $request->input('shipping_method', 'default'),
            'shipping_option' => $request->input('shipping_option'),
            'shipping_amount' => 0,
            'tax_amount'      => 0,
            'sub_total'       => $request->input('sub_total',0),
            'coupon_code'     => $request->applied_coupon_code,
            'discount_amount' => 0,
            'status'          => 'pending',
            'is_finished'     => false,
        ]);
        $order = Order::create($request->input());
        $sessionData['created_order'] = true;
        $sessionData['created_order_id'] = $order->id;
        //trừ đi mã discount
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
            foreach ($cartData as $d) {
                $product = $this->productRepository->find($d->product_id);
                if ($product) {
                    if ($product->weight) {
                        $weight += $product->weight * $d->qty;
                    }
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

            //thanh toán
            $paymentData = [
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
                    $this->paymentRepository->create($paymentData);
                    break;
                case 'bank_transfer':
                    $paymentData['charge_id'] = 'Bank-'.Str::upper(Str::random(10));
                    $paymentData['payment_channel'] = $paymentMethod;
                    $this->paymentRepository->create($paymentData);
                    break;
                case 'vnpay':
                    $paymentData['charge_id'] = $order->id.'-'.now().'-VNP-'.Str::random(6);
                    $paymentData['payment_channel'] = $paymentMethod;
                    $this->paymentRepository->create($paymentData);
                    break;
                default:
                    $paymentData['charge_id'] = Str::upper(Str::random(10));
                    $paymentData['payment_channel'] = 'cod';
                    $this->paymentRepository->create($paymentData);
                    break;
            }

            //Trừ số lượng sản phẩm từ trong kho

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
        $storeLocator = new StoreLocator();
        if($storeLocatorSelected == null){
            $customer = $this->usersRepository->find($request->customer_id);
            if(!empty($customer)) {
                if ($customer->store_locator_id != null) {
                    $defaultStore = $storeLocator->find($customer->store_locator_id);
                } else {
                    $defaultStore = $storeLocator->where('is_primary', 1)
                        ->where('is_shipping_location', 1)
                        ->first();
                }
            }else{
                $defaultStore = $storeLocator->where('is_primary', 1)
                    ->where('is_shipping_location', 1)
                    ->first();
            }
        }else {
            $defaultStore = $storeLocator->find($storeLocatorSelected);
        }

        $shippingData = [
            'ProductPrice' => $request->order_total,
            'COD' => 0,
            'ServiceId' => 12491,
            "DestCity"  =>  $request->city,
            "DestDistrict"  => $request->state,
            "SourceCity" => $defaultStore['city'],
            "SourceDistrict" => $defaultStore['state'],
            "Weight" => $request->weight,
        ];
        $bestExpressShippingFee = BestExpressConnection::calculateShippingPrice($shippingData);
        $result['bestexpress'] = [
                'name'  => 'BestExpress',
                'price' => $bestExpressShippingFee['TotalFeeVATWithDiscount']
        ];

        return response()->json($result);


    }


}
