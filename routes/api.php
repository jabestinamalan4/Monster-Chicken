<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\General\EncryptController;

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

Route::group(['middleware'=>['decrypt']], function(){
    Route::get('test', [EncryptController::class,'test'])->middleware(['auth:api']);

    Route::post('dashboard', [UserController::class,'dashboard']);

});
