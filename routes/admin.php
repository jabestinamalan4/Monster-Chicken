<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\VendorController;

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

    Route::post('product/store', [ProductController::class, 'store']);
    Route::post('product/list', [ProductController::class, 'productList']);

    Route::post('product/category-store', [ProductController::class, 'storeCategory']);
    Route::post('product/category-list', [ProductController::class, 'categoryList']);

    Route::post('product/category-change-status', [ProductController::class, 'changeCategoryStatus']);
    Route::post('product/change-status', [ProductController::class, 'changeStatus']);

    Route::post('add-user', [UserManagementController::class, 'store']);

    Route::post('change-user-status', [UserManagementController::class, 'changeStatus']);

    Route::post('add-branch', [UserManagementController::class, 'storeBranch']);
    Route::post('branch-list', [UserManagementController::class, 'branchList']);

    Route::post('vendor/add-vendor', [VendorController::class, 'store']);
    Route::post('vendor/list', [VendorController::class, 'vendorList']);
});
