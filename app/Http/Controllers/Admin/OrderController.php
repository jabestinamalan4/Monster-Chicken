<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\OrderDetail;
use App\Models\OrderStatus;
use App\Models\ProductCategory;
use App\Models\OrderDetailsStatus;
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


        $totalOrder = $query->count();

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

            $orderCount  = OrderDetail::where('order_id',$order->id)->count();

            if(isset($order->status))
            {
                $statusName = OrderStatus::where('status',$order->status)->first();
            }

            $orderList['id']            = $this->encryptId($order->id);
            $orderList['orderBy']       = $userList;
            $orderList['orderOn']       = $order->created_at;
            $orderList['totalPrice']    = $order->total_price;
            $orderList['note']          = $order->note;
            $orderList['status']        = $order->status;
            $orderList['statusName']    = $statusName->name;
            $orderList['orderCount']    = $orderCount;

            array_push($orderArray,$orderList);
        }

        $response['status'] = true;
        $response["message"] = ['Retrieved Successfully.'];
        $response['response']["orders"] = $orderArray;
        $response['response']["totalOrder"] = $totalOrder;

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

        $orderCount  = OrderDetail::where('order_id',$this->decryptId($inputData->orderId))->count();

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
                if(isset($orderDetail->status))
                {
                    $orderStatusName = OrderDetailsStatus::where('status',$orderDetail->status)->first();
                }

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

                $orderDetailsList['id']         = $this->encryptId($orderDetail->id);
                $orderDetailsList['product']    = $productList;
                $orderDetailsList['quantity']   = $orderDetail->quantity;
                $orderDetailsList['status']     = $orderDetail->status;
                $orderDetailsList['statusName'] = $orderStatusName->name;


                array_push($orderDetailsArray,(object) $orderDetailsList);
            }

            if(isset($order->status))
            {
                $statusName = OrderStatus::where('status',$order->status)->first();
            }

            $orderList['id']          = $this->encryptId($order->id);
            $orderList['orderBy']     = $userList;
            $orderList['orderOn']     = $order->created_at;
            $orderList['totalPrice']  = $order->total_price;
            $orderList['note']        = $order->note;
            $orderList['status']      = $order->status;
            $orderList['statusName']  = $statusName->name;
            $orderList['orderCount']  = $orderCount;
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
            'productId' => 'required|array',
            'supplierId' => 'required',
            'orderId' => 'required'
        ];

        $validatedData = Validator::make((array)$inputData, $rulesArray);

        if($validatedData->fails()) {
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $supplier = Supplier::where('id',$this->decryptId($inputData->supplierId))->where('status',1)->first();

        if (!isset($supplier->id)) {
            $response = ['status' => false, "message"=> ['Invalid Supplier Id.'], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $order = Order::where('id',$this->decryptId($inputData->orderId))->whereIn('status',[1,2])->first();

        if (!isset($order->id)) {
            $response = ['status' => false, "message"=> ['Invalid Order Id.'], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $orderDetails = OrderDetail::where('order_id',$this->decryptId($inputData->orderId))->first();

        if (!isset($orderDetails->id)) {
            $response = ['status' => false, "message"=> ['Invalid Order Id.'], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        if(isset($inputData->productId))
        {
            foreach($inputData->productId as $product) {
                $isExistProduct = Product::where('id',$this->decryptId($product))->where('status',1)->first();

                if(!isset($isExistProduct->id)) {
                    $response = ['status' => false, "message"=> ['Invalid Product Id.'], "responseCode" => 422];
                    $encryptedResponse['data'] = $this->encryptData($response);
                    return response($encryptedResponse, 400);
                }

                $isExistProduct = OrderDetail::where('product_id',$this->decryptId($product))
                                 ->where('order_id',$this->decryptId($inputData->orderId))->first();

                if(!isset($isExistProduct->id)) {
                    $response = ['status' => false, "message"=> ['Invalid Product Id.'], "responseCode" => 422];
                    $encryptedResponse['data'] = $this->encryptData($response);
                    return response($encryptedResponse, 400);
                }

                if(isset($inputData->OrderDetailsId))
                {
                    $orderDetails = OrderDetail::where('id',$this->decryptId($inputData->OrderDetailsId))->where('product_id',$this->decryptId($product))
                                        ->where('purchase_order_id',$this->decryptId($inputData->purchaseOrderId))->whereIn('status',[1,2])->first();

                    if(!isset($orderDetails->id)) {
                        $response = ['status' => false, "message"=> ['Invalid Order Details Id.'], "responseCode" => 422];
                        $encryptedResponse['data'] = $this->encryptData($response);
                        return response($encryptedResponse, 400);
                    }
                }

                else {
                    $orderDetails = OrderDetail::where('product_id',$this->decryptId($product))
                                        ->where('order_id',$this->decryptId($inputData->orderId))->whereIn('status',[1,2])->first();
                }

                $orderDetails->supplier_id  = $this->decryptId($inputData->supplierId);
                $orderDetails->status = 2;
                $orderDetails->save();

                $validCheckAllOrderDetails = OrderDetail::where('order_id',$this->decryptId($inputData->orderId))->where('status',1)->first();

                if(!isset($validCheckAllOrderDetails->id))
                {
                    $order->status  = 2;
                    $order->save();
                }
                else{
                    $order->status  = 1;
                    $order->save();
                }
            }
        }

        $response['status'] = true;
        $response["message"] = ['Order Assign Successfully.'];
        $response['responseCode'] = 200;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }

    public function orderDelivery(Request $request)
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

        $order = Order::where('id',$this->decryptId($inputData->orderId))->where('status',2)->first();

        if (!isset($order->id)) {
            $response = ['status' => false, "message"=> ['Invalid Order Id.'], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $orderDetails = OrderDetail::where('id',$this->decryptId($inputData->orderId))->where('status',2)->get();

        if(isset($orderDetails))
        {
            foreach($orderDetails as $orderDetail)
            {
                $OrderDetailList  = OrderDetail::where('id',$orderDetail->id)->where('status',2)->first();

                if(isset($OrderDetailList->id))
                {
                    $OrderDetailList->status = 3;
                    $OrderDetailList->save();
                }
            }

            if(isset($order->id))
            {
                $order->status = 3;
                $order->save();
            }
        }

        $response['status'] = true;
        $response["message"] = ['Updated successfully.'];
        $response['responseCode'] = 200;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }
}
