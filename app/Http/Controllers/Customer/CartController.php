<?php

namespace App\Http\Controllers\Customer;

use App\Models\Cart;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderDetail;
use Illuminate\Http\Request;
use App\Models\CustomerDetail;
use App\Models\ShippingDetail;
use App\Models\ProductCategory;
use App\Http\Traits\HelperTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
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
                $response = ['status' => false, "message"=> ['Invalid Id'], "responseCode" => 422];
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

        if (isset($inputData->status) && $inputData->status == "remove") {
            $cart->status = 0;
        }

        $cart->save();

        $response['status'] = true;
        $response["message"] = ['Saved successfully.'];
        $response['responseCode'] = 200;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }

    public function cartCheckout(Request $request)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }

        $inputUser = $request->user;

        $rulesArray = [
                        'cartId' => 'required|array',
                        'firstName' => 'required',
                        'lastName' => 'required',
                        'state' => 'required',
                        'city' => 'required',
                        'pin' => 'required',
                        'address' => 'required',
                        'number' => 'required',
                        'email' => 'required',
                    ];

        if(isset($inputData->diffShipAddress) && $inputData->diffShipAddress == true){
            $rulesArray['shipFirstName'] = 'required';
            $rulesArray['shipLastName'] = 'required';
            $rulesArray['shipState'] = 'required';
            $rulesArray['shipCity'] = 'required';
            $rulesArray['shipPin'] = 'required';
            $rulesArray['shipAddress'] = 'required';
        }

        $validatedData = Validator::make((array)$inputData, $rulesArray);

        if($validatedData->fails()) {
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $cartArray = [];
        $totalCartPrice = 0;

        foreach($inputData->cartId as $cartId){
            $id = $this->decryptId($cartId);

            $isExist = Cart::where('id',$id)->where('status',1)->first();

            if(isset($isExist->id)){
                $totalCartPrice = $totalCartPrice + ((int) $isExist->product->price * (int) $isExist->quantity);
                array_push($cartArray,$isExist->id);
            }
            else{
                $response = ['status' => false, "message"=> ['Invalid Cart ID.'], "responseCode" => 422];
                $encryptedResponse['data'] = $this->encryptData($response);
                return response($encryptedResponse, 400);
            }
        }

        $order = new Order;

        $order->user_id = $inputUser->id;
        $order->note = isset($inputData->note) && ($inputData->note) ? $inputData->note:null;
        $order->total_price = $totalCartPrice;
        $order->status = 1;

        $order->save();

        $customerDetail = new CustomerDetail;
        $customerDetail->user_id = $inputUser->id;
        $customerDetail->first_name = $inputData->firstName;
        $customerDetail->last_name = $inputData->lastName;
        $customerDetail->state = $inputData->state;
        $customerDetail->city = $inputData->city;
        $customerDetail->pin = $inputData->pin;
        $customerDetail->address = $inputData->address;
        $customerDetail->number = $inputData->number;
        $customerDetail->email = $inputData->email;
        $customerDetail->status = isset($inputData->saveData) ? $inputData->saveData : 0;
        $customerDetail->order_id = $order->id;

        $customerDetail->save();

        if(isset($inputData->diffShipAddress) && $inputData->diffShipAddress == true){

            $shippingDetail = new ShippingDetail;
            $shippingDetail->first_name = $inputData->shipFirstName;
            $shippingDetail->last_name = $inputData->shipLastName;
            $shippingDetail->state = $inputData->shipState;
            $shippingDetail->city = $inputData->shipCity;
            $shippingDetail->pin = $inputData->shipPin;
            $shippingDetail->address = $inputData->shipAddress;
            $shippingDetail->order_id = $order->id;

            $shippingDetail->save();
        }
        else{
            $shippingDetail = new ShippingDetail;
            $shippingDetail->first_name = $inputData->firstName;
            $shippingDetail->last_name = $inputData->lastName;
            $shippingDetail->state = $inputData->state;
            $shippingDetail->city = $inputData->city;
            $shippingDetail->pin = $inputData->pin;
            $shippingDetail->address = $inputData->address;
            $shippingDetail->order_id = $order->id;

            $shippingDetail->save();
        }

        foreach($cartArray as $cartId){

            $cartData = Cart::where('id',$cartId)->where('status',1)->first();

            if(isset($cartData->id)){
                $orderDetail = new OrderDetail;

                $orderDetail->order_id = $order->id;
                $orderDetail->product_id = $cartData->product_id;
                $orderDetail->quantity = $cartData->quantity;

                $orderDetail->save();

                $cartData->status = 0;

                $cartData->save();
            }
            else{
                $response = ['status' => false, "message"=> ['Invalid Cart ID.'], "responseCode" => 422];
                $encryptedResponse['data'] = $this->encryptData($response);
                return response($encryptedResponse, 400);
            }
        }

        $response['status'] = true;
        $response['responseCode'] = 200;
        $response["message"] = ['Saved successfully.'];
        $response["response"]['orderId'] = $this->encryptId($order->id);

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
        $carts = Cart::with('product')->whereRelation('product', 'status', 1)->where('status',1)->where('user_id',$inputUser->id)->orderBy('id','DESC')->get();

        $cartArray = [];
        $totalCartPrice = 0;

        foreach($carts as $cart){
            $cartDetail = [];

            $cartDetail['cartId'] = $this->encryptId($cart->id);
            $cartDetail['productId'] = $this->encryptId($cart->product->id);
            $cartDetail['productName'] = $cart->product->name;
            $cartDetail['stock'] = 20;
            $cartDetail['maxQuantity'] = 10;

            $isCategoryExist = ProductCategory::where('status',1)->where('id',$cart->product->category)->first();
            if (isset($isCategoryExist->id)) {
                $cartDetail['productCategory'] = $isCategoryExist->category;
            }
            else{
                $cartDetail['productCategory'] = "";
            }

            $imageArray = [];

            foreach(json_decode($cart->product->image_url) as $image){
                $imageUrl = Storage::disk('public')->url('document/'.$image);

                array_push($imageArray,$imageUrl);
            }

            $cartDetail['imageUrl'] = $imageArray;

            $cartDetail['quantity'] = $cart->quantity;
            $cartDetail['price'] = $cart->product->price;
            $cartDetail['rating'] = $cart->product->rating;
            $cartDetail['reviews'] = $cart->product->reviews;
            $cartDetail['totalPrice'] = (int) $cart->product->price * (int) $cart->quantity;
            $totalCartPrice = $totalCartPrice + ((int) $cart->product->price * (int) $cart->quantity);

            array_push($cartArray,(object) $cartDetail);
        }

        $response['status'] = true;
        $response["message"] = ['Saved successfully.'];
        $response['responseCode'] = 200;
        $response['response']["cart"] = $cartArray;
        $response['response']["deliveryCharge"] = 15;
        $response['response']["totalCartPrice"] = $totalCartPrice;
        $response['response']["grandTotal"] = $totalCartPrice + 15;
        $response['response']["totalCount"] = $totalCount;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }

    public function getCheckoutData(Request $request)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }

        $inputUser = $request->user;

        $rulesArray = [
                        'cartId' => 'required|array'
                    ];

        $validatedData = Validator::make((array)$inputData, $rulesArray);

        if($validatedData->fails()) {
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        if(isset($inputUser->id)){
            $user = User::where('id',$inputUser->id)->where('status',1)->first();

            if(isset($user->id)){
                $checkOutData = [];
                $checkOutData['name'] = $user->name;
                $checkOutData['email'] = $user->email;
                $checkOutData['number'] = $user->number;

                $isCheckoutExist = CustomerDetail::where('user_id',$user->id)->where('status',1)->get();

                $checkoutSuggestionArray = [];

                foreach($isCheckoutExist as $suggestion){

                    $suggestionArray = [];

                    $suggestionArray['state'] = $suggestion->state;
                    $suggestionArray['city'] = $suggestion->city;
                    $suggestionArray['pin'] = $suggestion->pin;
                    $suggestionArray['address'] = $suggestion->address;

                    array_push($checkoutSuggestionArray,(object) $suggestionArray);
                }

                $checkOutData['suggestions'] = $checkoutSuggestionArray;

                $cartArray = [];
                $totalCartPrice = 0;

                foreach($inputData->cartId as $cartId){
                    $id = $this->decryptId($cartId);

                    $isExist = Cart::where('id',$id)->where('status',1)->first();

                    if(isset($isExist->id)){

                        $cart = $isExist;

                        $cartDetail['cartId'] = $this->encryptId($cart->id);
                        $cartDetail['productId'] = $this->encryptId($cart->product->id);
                        $cartDetail['productName'] = $cart->product->name;
                        $cartDetail['stock'] = 20;
                        $cartDetail['maxQuantity'] = 10;

                        $isCategoryExist = ProductCategory::where('status',1)->where('id',$cart->product->category)->first();
                        if (isset($isCategoryExist->id)) {
                            $cartDetail['productCategory'] = $isCategoryExist->category;
                        }
                        else{
                            $cartDetail['productCategory'] = "";
                        }

                        $imageArray = [];

                        foreach(json_decode($cart->product->image_url) as $image){
                            $imageUrl = Storage::disk('public')->url('document/'.$image);

                            array_push($imageArray,$imageUrl);
                        }

                        $cartDetail['imageUrl'] = $imageArray;

                        $cartDetail['quantity'] = $cart->quantity;
                        $cartDetail['price'] = $cart->product->price;
                        $cartDetail['rating'] = $cart->product->rating;
                        $cartDetail['reviews'] = $cart->product->reviews;
                        $cartDetail['totalPrice'] = (int) $cart->product->price * (int) $cart->quantity;
                        $totalCartPrice = $totalCartPrice + ((int) $cart->product->price * (int) $cart->quantity);

                        array_push($cartArray,$cartDetail);
                    }
                    else{
                        $response = ['status' => false, "message"=> ['Invalid Cart ID.'], "responseCode" => 422];
                        $encryptedResponse['data'] = $this->encryptData($response);
                        return response($encryptedResponse, 400);
                    }
                }

                $checkOutData['cartData'] = $cartArray;
            }
        }
        else{
            $response = ['status' => false, "message"=> ['Invalid User.'], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $response['status'] = true;
        $response["message"] = ['Saved successfully.'];
        $response['responseCode'] = 200;
        $response['response']["checkOutData"] = $checkOutData;
        $response['response']["deliveryCharge"] = 15;
        $response['response']["offerPrice"] = 0;
        $response['response']["totalCartPrice"] = $totalCartPrice;
        $response['response']["grandTotal"] = $totalCartPrice + 15;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }

    public function stateList(Request $request)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }

        $query = State::where('status',1);

        if(isset($inputData->search) && $inputData->search != null && $inputData->search != ""){
            $search = $inputData->search;
            $query  = $query->where(function ($function) use($search) {
                $function->Where('state', 'like', '%' . $search . '%');
            });
        }

        $states = $query->orderBy('state','ASC')->get();
        $stateArray = [];

        foreach($states as $state){
            $stateDetail = [];

            $stateDetail['name'] = $state->state;
            $stateDetail['value'] = $state->id;

            array_push($stateArray,(object) $stateDetail);
        }

        $response['status'] = true;
        $response["message"] = ['Saved successfully.'];
        $response['responseCode'] = 200;
        $response['response']["states"] = $stateArray;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }

}