<?php

namespace App\Http\Controllers\Customer;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Traits\HelperTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    use HelperTrait;

    public function cartStore(Request $request)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }

        $inputUser = $request->user;

        $rulesArray = [
                        'productId' => 'required'
                    ];

        $validatedData = Validator::make((array)$inputData, $rulesArray);

        if($validatedData->fails()) {
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $productExist = Product::where('id',$inputData->productId)->where('status',1)->first();

        if(!isset($productExist->id)){
            $response = ['status' => false, "message"=> ["Invalid Product Id."], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $cartExist = Cart::where('product_id',$inputData->productId)->where('user_id',$inputUser->id)->first();

        if (isset($inputData->cartId)) {
            $isExist = Cart::find($inputData->cartId);
            if (isset($isExist->id)) {
                $cart = $isExist;
            }
            else{
                $response = ['status' => false, "message"=> ['Invalid Id'], "responseCode" => 400];
                $encryptedResponse['data'] = $this->encryptData($response);
                return response($encryptedResponse, 400);
            }
        }
        elseif(isset($cartExist->id)){
            $cart = $cartExist;
            $cart->status = 1;
        }
        else{
            $cart = new Cart;
            $cart->status = 1;
            $cart->user_id = $inputUser->id;
        }

        $cart->product_id = $inputData->productId;
        $cart->quantity = isset($inputData->quantity) ? $inputData->quantity : 1;

        $cart->save();

        $response['status'] = true;
        $response['responseCode'] = 200;
        $response["message"] = ['Saved successfully.'];

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }
}