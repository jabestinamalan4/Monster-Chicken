<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderDetail;
use App\Models\ProductCategory;
use App\Http\Traits\HelperTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    use HelperTrait;

    public function orderList(Request $request)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }

        $query = Order::query();

        if (isset($inputData->status) && $inputData->status != null && $inputData->status != "") {
            $query = $query->where('status',$inputData->status);
        }

        if (isset($inputData->userId) && $inputData->userId != null && $inputData->userId != "") {
            $query = $query->where('user_id',$this->decryptId($inputData->userId));
        }


        $orderCount = $query->count();

        $orders = $query->orderBy('id','desc')->paginate(isset($inputData->countPerPage) ? $inputData->countPerPage : 20);

        $orderArray = [];

        foreach($orders as $order)
        {
            $orderList  = [];
            $userList   = [];

            $user  = User::where('id',$order->user_id)->first();

            if(isset($user->id)){
                $userList['id']    = $this->encryptId($user->id);
                $userList['name']  = $user->name;
                $userList['email'] = $user->email;
            }

            $orderList['id']            = $this->encryptId($order->id);
            $orderList['orderBy']       = $userList;
            $orderList['orderOn']       = $order->created_at;
            $orderList['totalPrice']    = $order->total_price;
            $orderList['note']          = $order->note;

            array_push($orderArray,$orderList);
        }

        $response['status'] = true;
        $response["message"] = ['Retrieved Successfully.'];
        $response['response']["orders"] = $orderArray;
        $response['response']["totalOrder"] = $orderCount;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);

    }

    public function orderDetails(Request $request)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }

        $rulesArray = [
            'orderId' => 'required'
        ];

        $validatedData = Validator::make((array)$inputData, $rulesArray);

        if($validatedData->fails()) {
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $order = Order::where('id',$this->decryptId($inputData->orderId))->first();

        if (!isset($order->id)) {
            $response = ['status' => false, "message"=> ['Invalid Order Id.'], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        else{
            $orderList        = [];
            $userList         = [];
            $orderDetailsList = [];
            $productList = [];

            $user  = User::where('id',$order->user_id)->first();

            if(isset($user->id)){
                $userList['id']    = $this->encryptId($user->id);
                $userList['name']  = $user->name;
                $userList['email'] = $user->email;
            }

            $orderDetails  = OrderDetail::where('order_id',$order->id)->get();

            $orderDetailsArray = [];

            foreach($orderDetails as $orderDetail)
            {
                $product = Product::where('id',$orderDetail->product_id)->first();

                if(isset($product->id))
                {
                    $imageArray          = [];
                    $category = ProductCategory::where('id',$product->category)->first();

                    foreach((array)json_decode($product->image_url) as $imageUrl){
                        $imageList  = [];

                        $imageList['fileName'] = $imageUrl;
                        $imageList['previewUrl'] = Storage::disk('public')->url('document/'.$imageUrl);
                        array_push($imageArray,(object) $imageList);
                    }

                    $productList['id']              = $this->encryptId($product->id);
                    $productList['category']        = $category->category;
                    $productList['name']            = $product->name;
                    $productList['price']           = $product->price;
                    $productList['discount_price']  = $product->discount_price;
                    $productList['imageUrl']        = $imageArray;
                }

                $orderDetailsList['id']       = $this->encryptId($orderDetail->id);
                $orderDetailsList['product']  = $productList;
                $orderDetailsList['quantity'] = $orderDetail->quantity;

                array_push($orderDetailsArray,(object) $orderDetailsList);
            }

            $orderList['id']         = $this->encryptId($order->id);
            $orderList['orderBy']    = $userList;
            $orderList['orderOn']    = $order->created_at;
            $orderList['totalPrice'] = $order->total_price;
            $orderList['note']       = $order->note;
            $orderList['status']     = $order->status;
            $orderList['OrderDetails']= $orderDetailsArray;

        }


        $response['status'] = true;
        $response["message"] = ['Retrieved Successfully.'];
        $response['response']["order"] = (object) $orderList;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }

    public function userList(Request $request)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }

        $query = User::query();

        if (isset($inputData->search) && $inputData->search != null && $inputData->search != "") {
            $search = $inputData->search;
            $query  = $query->where(function ($function) use($search) {
                $function->Where('name', 'like', '%' . $search . '%');
          });
        }

        $query = $query->whereNotNull('email');

        $query = $query->whereNotNull('name');

        $query = $query->where('id', '!=', 1);

        $userCount = $query->count();

        $users = $query->orderBy('id','desc')->paginate(isset($inputData->countPerPage) ? $inputData->countPerPage : 20);

        $userArray  = [];

        foreach($users as $user) {
            $userList   = [];
            $rolesList  = [];
            $rolesArray = [];

            $rolesName = $user->getRoleNames()->toArray();
            $role      = implode(" ",$rolesName);

            if($role=="cuttingCenter" || $role=="retailer")
            {
                $userList['id']     = $this->encryptId($user->id);
                $userList['name']   = $user->name;

                array_push($userArray,(object) $userList);
            }
        }


        $response['status'] = true;
        $response["message"] = ['Retrieved Successfully.'];
        $response['response']["user"] = $userArray;
        $response['response']["totalUser"] = $userCount;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }

    public function orderAssign(Request $request)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }

        $rulesArray = [
            'userId' => 'required',
            'orderId' => 'required'
        ];

        $validatedData = Validator::make((array)$inputData, $rulesArray);

        if($validatedData->fails()) {
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $user = User::where('id',$this->decryptId($inputData->userId))->first();

        if (!isset($user->id)) {
            $response = ['status' => false, "message"=> ['Invalid User Id.'], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $order = Order::where('id',$this->decryptId($inputData->orderId))->where('status',1)->first();

        if (!isset($order->id)) {
            $response = ['status' => false, "message"=> ['Invalid Order Id.'], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }
        else{
            $order->delivered_by  = $this->decryptId($inputData->userId);
            $order->status  = 2;
            $order->save();
        }

        $response['status'] = true;
        $response["message"] = ['Order Assign Successfully.'];
        $response['responseCode'] = 200;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }
}
