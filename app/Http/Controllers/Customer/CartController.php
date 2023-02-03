<?php

namespace App\Http\Controllers\Customer;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\ProductCategory;
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

        $productId = $this->decryptId($inputData->productId);

        $productExist = Product::where('id',$productId)->where('status',1)->first();

        if(!isset($productExist->id)){
            $response = ['status' => false, "message"=> ["Invalid Product Id."], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $cartExist = Cart::where('product_id',$productId)->where('user_id',$inputUser->id)->first();

        if (isset($inputData->cartId)) {
            $isExist = Cart::find($this->decryptId($inputData->cartId));
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

        $cart->product_id = $productId;
        $cart->quantity = isset($inputData->quantity) ? $inputData->quantity : 1;

        $cart->save();

        $response['status'] = true;
        $response['responseCode'] = 200;
        $response["message"] = ['Saved successfully.'];

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }

    public function cartList(Request $request)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }

        $inputUser = $request->user;

        $totalCount = Cart::with('product')->whereRelation('product', 'status', 1)->where('status',1)->where('user_id',$inputUser->id)->count();
        $carts = Cart::with('product')->whereRelation('product', 'status', 1)->where('status',1)->where('user_id',$inputUser->id)->orderBy('id','DESC')->paginate(isset($inputData->countPerPage) ? $inputData->countPerPage : 12);

        $cartArray = [];

        foreach($carts as $cart){
            $cartDetail = [];

            $cartDetail['cartId'] = $this->encryptId($cart->id);
            $cartDetail['productId'] = $this->encryptId($cart->product->id);
            $cartDetail['productName'] = $cart->product->name;

            $isCategoryExist = ProductCategory::where('status',1)->where('id',$cart->product->category)->first();
            if (isset($isCategoryExist->id)) {
                $cartDetail['productCategory'] = $isCategoryExist->category;
            }
            else{
                $cartDetail['productCategory'] = "";
            }

            $cartDetail['quantity'] = $cart->quantity;
            $cartDetail['price'] = $cart->product->price;
            $cartDetail['totalPrice'] = (int) $cart->product->price * (int) $cart->quantity;

            array_push($cartArray,$cartDetail);
        }

        $response['status'] = true;
        $response['responseCode'] = 200;
        $response["message"] = ['Saved successfully.'];
        $response["cart"] = $cartArray;
        $response["totalCount"] = $totalCount;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }
}
