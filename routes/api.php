<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\General\FileController;
use App\Http\Controllers\Customer\AuthController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\UserController;
use App\Http\Controllers\General\EncryptController;
use App\Http\Controllers\Customer\ProductController;

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

Route::get('unauthorized', [EncryptController::class,'unauthorized'])->name('unauthenticated');
Route::post('decrypt', [EncryptController::class, 'decrypt'])->middleware(['decrypt']);
Route::post('encrypt', [EncryptController::class, 'encrypt']);

Route::post('file/upload', [FileController::class,'upload']);

Route::group(['middleware'=>['decrypt','deviceMap']], function(){
    Route::get('test', [EncryptController::class,'test'])->middleware(['auth:api']);

    Route::post('register', [AuthController::class,'register']);
    Route::post('login', [AuthController::class,'login']);

    Route::post('profile', [UserController::class,'profile']);
    Route::post('dashboard', [UserController::class,'dashboard']);

    Route::post('product/list', [ProductController::class,'productList']);
    Route::get('product/category/list', [ProductController::class,'categoryList']);
    Route::post('product/view', [ProductController::class,'productDetails']);
    Route::post('product/wishlist/add', [ProductController::class,'wishlistStore']);
    Route::post('product/wishlist/list', [ProductController::class,'wishlist']);

    Route::post('cart/store', [CartController::class,'cartStore']);
    Route::post('cart/list', [CartController::class,'cartList']);
});
