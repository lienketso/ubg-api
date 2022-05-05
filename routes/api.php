<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


//Customer route
Route::group([
    'prefix' => 'auth'
], function () {
    Route::post('/register', [App\Http\Controllers\API\AuthController::class, 'register'])
        ->middleware('authbasic');
    Route::post('/login', [App\Http\Controllers\API\AuthController::class, 'login'])
        ->name('login')
        ->middleware('authbasic');
    Route::post('/update-user', [App\Http\Controllers\API\AuthController::class, 'updateProfile'])
        ->name('update-profile')->middleware('authbasic');
    Route::post('/reset-password',[\App\Http\Controllers\API\AuthController::class,'ForgotPassword'])
        ->name('reset-password')->middleware('authbasic');
    Route::group([
        'middleware' => 'auth:api'
    ], function() {
        Route::get('/logout', [\App\Http\Controllers\API\AuthController::class,'logout']);
        Route::get('/user', [\App\Http\Controllers\API\AuthController::class,'user']);
        //insert order
        Route::post('/process-order-data',[\App\Http\Controllers\API\OrderController::class,'processInsertCart']);

    });
});
/* danh sách đơn hàng của khách hàng */
Route::get('/list-order',[\App\Http\Controllers\API\OrderController::class,'getListOrder'])->middleware('authbasic');
/* chi tiết đơn hàng của khách hàng */
Route::get('/single-order',[\App\Http\Controllers\API\OrderController::class,'getSingleOrder'])->middleware('authbasic');

//Store route
Route::get('/list-locator', [App\Http\Controllers\API\StoreLocatorController::class, 'getListStore'])->middleware('authbasic');
//menu route
/* main menu top */
Route::get('/main-menu',[\App\Http\Controllers\API\MenuNodeController::class,'getMainMenu'])->middleware('authbasic');
/* main category top */
Route::get('/main-product-category',[\App\Http\Controllers\API\ProductCategoryController::class,'getProductCategory'])
    ->middleware('authbasic');
//Slider route
Route::get('/main-slider',[\App\Http\Controllers\API\SimpleSliderItemController::class,'getMainSlider'])
    ->middleware('authbasic');

//Route flash sale
/* main flash sale home */
Route::get('/flash-sale',[\App\Http\Controllers\API\FlashSaleController::class,'getFlashSale'])
    ->middleware('authbasic');
//route product
/* get product by collection (Khuyến mại, discount, bán chạy) */
Route::post('/product-collection',[\App\Http\Controllers\API\ProductCollectionController::class,'getProductByCollection'])
    ->middleware('authbasic');
/* get product by category */
Route::post('product-by-category',[\App\Http\Controllers\API\ProductCategoryController::class,'getProductByCategory'])
    ->middleware('authbasic');
/* get single product */
Route::get('/single-product',[\App\Http\Controllers\API\ProductController::class,'getSingleProduct'])
    ->middleware('authbasic');
/* Sản phẩm liên quan */
Route::get('/related-product',[\App\Http\Controllers\API\ProductController::class,'getRelatedProduct'])
    ->middleware('authbasic');
/* Sản phẩm mua chung */
Route::get('/group-product-list',[\App\Http\Controllers\API\GroupByProductController::class,'getMuachungList'])
    ->middleware('authbasic');
/* Chi tiết Sản phẩm mua chung */
Route::get('/group-product-single',[\App\Http\Controllers\API\GroupByProductController::class,'getMuachungSingle'])
    ->middleware('authbasic');

//route cart

Route::post('/list-item-cart',[\App\Http\Controllers\API\CartController::class,'getCartList'])
    ->middleware('authbasic');

//danh sách địa chỉ khách hàng
Route::get('/address-list',[\App\Http\Controllers\API\OrderController::class,'listAddress'])
    ->middleware('authbasic');

//chi tiết mã coupon khuyến mại
Route::get('/get-coupon-code',[\App\Http\Controllers\API\OrderController::class,'getDiscountByCode'])
    ->middleware('authbasic');
//chi tiết promotion
Route::get('/get-promotion-in-cart-value',[\App\Http\Controllers\API\OrderController::class,'getPromotionValue'])
    ->middleware('authbasic');

//route location
/* Tỉnh thành */
Route::get('get-province',[\App\Http\Controllers\API\LocationController::class,'getProvince'])
    ->middleware('authbasic');
/* Quan huyen */
Route::get('get-districts',[\App\Http\Controllers\API\LocationController::class,'getDistrictByProvince'])
    ->middleware('authbasic');
/* Phường xã */
Route::get('get-wards',[\App\Http\Controllers\API\LocationController::class,'getWardByDistrict'])
    ->middleware('authbasic');

//route get fee ship
Route::get('get-fee-shipping',[\App\Http\Controllers\API\OrderController::class,'getFeeShipping'])
    ->middleware('authbasic');
