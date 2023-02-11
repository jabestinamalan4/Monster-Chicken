<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\PriceController;

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

Route::group(['middleware'=>['decrypt','location']], function(){
    Route::post('login', [AuthController::class, 'login']);

    Route::post('forget-password', [AuthController::class,'forgetPassword']);
    Route::post('resend-otp', [AuthController::class,'resendOtp']);
    Route::post('change-password', [AuthController::class, 'changePassword'])->middleware('auth:api');
    Route::post('update-password', [AuthController::class, 'updatePassword']);

    Route::post('product/store', [ProductController::class, 'store'])->middleware(['role:admin','auth:api']);
    Route::post('product/list', [ProductController::class, 'productList'])->middleware(['role:admin|franchise','auth:api']);

    Route::post('product/category-store', [ProductController::class, 'storeCategory'])->middleware(['role:admin','auth:api']);
    Route::post('product/category-list', [ProductController::class, 'categoryList'])->middleware(['role:admin','auth:api']);

    Route::post('product/category-change-status', [ProductController::class, 'changeCategoryStatus'])->middleware(['role:admin','auth:api']);
    Route::post('product/change-status', [ProductController::class, 'changeStatus'])->middleware(['role:admin','auth:api']);

    Route::post('add-user', [UserManagementController::class, 'store'])->middleware(['role:admin','auth:api']);
    Route::post('user-list', [UserManagementController::class, 'userList'])->middleware(['role:admin','auth:api']);

    Route::post('change-user-status', [UserManagementController::class, 'changeStatus'])->middleware(['role:admin','auth:api']);

    Route::post('add-branch', [UserManagementController::class, 'storeBranch'])->middleware(['role:admin','auth:api']);
    Route::post('branch-list', [UserManagementController::class, 'branchList'])->middleware(['role:admin','auth:api']);

    Route::post('vendor/add-vendor', [VendorController::class, 'store'])->middleware(['role:admin','auth:api']);
    Route::post('vendor/list', [VendorController::class, 'vendorList'])->middleware(['role:admin','auth:api']);

    Route::post('purchase-order/store', [PurchaseOrderController::class, 'store'])->middleware(['role:admin','auth:api']);

    Route::post('price/store', [PriceController::class, 'store'])->middleware(['role:admin','auth:api']);
    Route::post('price/list', [PriceController::class, 'priceList'])->middleware(['role:admin','auth:api']);

});
