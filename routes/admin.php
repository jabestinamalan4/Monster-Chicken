<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\AuthController;

/*
|--------------------------------------------------------------------------
| ADMIN Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Here the routes will be appended with api/admin in-front of the specified routes

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware'=>['decrypt']], function(){
    Route::post('login', [AuthController::class, 'login']);

    Route::post('product/store', [ProductController::class, 'store']);
    Route::post('product/list', [ProductController::class, 'productList']);

    Route::post('product/category-store', [ProductController::class, 'storeCategory']);
    Route::post('product/category-list', [ProductController::class, 'categoryList']);

    Route::post('productCategory/change-status', [ProductController::class, 'changeStatus']);
    Route::post('product/change-status', [ProductController::class, 'productChangeStatus']);

});
