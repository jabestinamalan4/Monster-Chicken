<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\PriceController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\General\FileController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\StockController;

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
    Route::post('product/get-products', [ProductController::class, 'getProducts'])->middleware(['role:admin','auth:api']);
    Route::post('product/view', [ProductController::class, 'productDetails'])->middleware(['role:admin','auth:api']);


    Route::post('user/store', [UserManagementController::class, 'store'])->middleware(['role:admin','auth:api']);
    Route::post('user/list', [UserManagementController::class, 'userList'])->middleware(['role:admin','auth:api']);
    Route::post('user/get-users', [UserManagementController::class, 'getUsers'])->middleware(['role:admin','auth:api']);
    Route::post('user/profile', [UserManagementController::class,'profile'])->middleware(['role:admin','auth:api']);
    Route::post('user/branch-list', [UserManagementController::class,'branchList'])->middleware(['role:admin','auth:api']);

    Route::post('user/change-status', [UserManagementController::class, 'changeStatus'])->middleware(['role:admin','auth:api']);

    Route::post('supplier/store', [SupplierController::class, 'store'])->middleware(['role:admin','auth:api']);
    Route::post('supplier/list', [SupplierController::class, 'supplierList'])->middleware(['role:admin','auth:api']);
    Route::post('supplier/get-suppliers', [SupplierController::class, 'getSupplier'])->middleware(['role:admin','auth:api']);
    Route::post('supplier/change-status', [SupplierController::class, 'changeStatus'])->middleware(['role:admin','auth:api']);

    Route::post('purchase-order/store', [PurchaseOrderController::class, 'store'])->middleware(['role:admin','auth:api']);
    Route::post('purchase-order/list', [PurchaseOrderController::class, 'purchaseOrderList'])->middleware(['role:admin','auth:api']);
    Route::post('purchase-order/view', [PurchaseOrderController::class, 'purchaseOrderDetails'])->middleware(['role:admin','auth:api']);

    Route::post('price/store', [PriceController::class, 'store'])->middleware(['role:admin','auth:api']);

    Route::post('file/upload', [FileController::class,'upload']);

    Route::get('role/list', [UserManagementController::class, 'rolesList'])->middleware(['role:admin|franchise','auth:api']);

    Route::post('stock/store', [StockController::class, 'store'])->middleware(['role:admin','auth:api']);
    Route::post('stock/list', [StockController::class, 'stockList'])->middleware(['role:admin','auth:api']);


});
