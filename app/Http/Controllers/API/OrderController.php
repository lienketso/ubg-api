<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Repositories\AddressRepository;
use App\Repositories\OrderAddressRepository;
use App\Repositories\OrderProductRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    protected $orderRepository;
    protected $addressRepository;
    protected $orderAddressRepository;
    protected $productRepository;
    protected $orderProductRepository;
    public function __construct(OrderRepository $orderRepository,
                                AddressRepository $addressRepository,
                                OrderAddressRepository $orderAddressRepository,
                                ProductRepository $productRepository,
                                OrderProductRepository $orderProductRepository
    )

    {
        $this->orderRepository = $orderRepository;
        $this->addressRepository = $addressRepository;
        $this->orderAddressRepository = $orderAddressRepository;
        $this->productRepository = $productRepository;
        $this->orderProductRepository = $orderProductRepository;
    }

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

    public function getSingleOrder(Request $request){
        $order = Order::where('id',$request->id)
            ->where('user_id',$request->user_id)
            ->with(['products','address'])->first();
        return response()->json($order,200);
    }

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
            'sub_total'       => $request->input('subtotal',0),
            'coupon_code'     => $request->applied_coupon_code,
            'discount_amount' => 0,
            'status'          => 'pending',
            'is_finished'     => false,
        ]);
        $order = Order::create($request->input());
        $sessionData['created_order'] = true;
        $sessionData['created_order_id'] = $order->id;
        //lấy ra address từ id
        $address_id = $request->address_id;
        $sessionData['address_id'] = $address_id;
        // nếu chọn thêm mới địa chỉ
        if($request->address_id=='new'){
                    $dataNewAddress = [
                        'name'=>$request->name,
                        'phone'=>$request->phone,
                        'address'=>$request->address,
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
    public function listAddress(Request $request){
        try{
            $data = $this->addressRepository->findWhere(['customer_id'=>$request->customer_id])->all();
            return response()->json($data);
        }catch (\Exception $e){
            return response()->json(['errors'=>$e->getMessage(),'message'=>'Lỗi !']);
        }

    }

}