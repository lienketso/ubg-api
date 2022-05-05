<?php


namespace App\Services;


use App\Repositories\DiscountRepository;
use App\Repositories\ProductRepository;
use http\Env\Request;

class HandleApplyPromotionsService
{
    protected $discountRepository;
    protected $productRepository;
    protected $promotions;

    public function __construct(DiscountRepository $discountRepository, ProductRepository $productRepository)
    {
        $this->discountRepository = $discountRepository;
        $this->productRepository = $productRepository;

    }

    public function getPromotionValue(Request $request){
        $cart = $request->cart_items;
        $promotionDiscountAmount = 0;
        $promotion = $this->discountRepository->scopeQuery(function($q) use($cart){
            return $q->where('type','promotion')
                ->where('start_date','<=',now())
                ->where(function ($query){
                    return $query->whereNull('end_date')->orWhere('end_date','>',now());
                });
        })->all();
    }

}
