<?php

namespace App\Http\Controllers\Admin;

use App\Models\Product;
use App\Models\Supplier;
use App\MOdels\User;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use App\Models\ProductCategory;
use App\Http\Traits\HelperTrait;
use App\Models\PurchaseOrderItem;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PurchaseOrderController extends Controller
{
    use HelperTrait;

    public function store(Request $request)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }

        $inputUser = auth()->user();

        $rulesArray = [
                        'productData' => 'required|array'
                    ];

        $validatedData = Validator::make((array)$inputData, $rulesArray);

        if($validatedData->fails()) {
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        if(isset($inputData->supplierId)) {

            $supplier = Supplier::where('id',$this->decryptId($inputData->supplierId))->first();

            if(!isset($supplier->id)) {
                $response = ['status' => false, "message"=> ['Invalid Supplier Id.'], "responseCode" => 422];
                $encryptedResponse['data'] = $this->encryptData($response);
                return response($encryptedResponse, 400);
            }
        }

        $productArray = [];
        $orderData = [];

        foreach($inputData->productData as $product){

            if(gettype($product) == 'string'){
                $product = json_decode($product);
            }

            if(isset($product->id) && isset($product->quantity)){
                $isExist = Product::where('id',$this->decryptId($product->id))->where('status',1)->first();

                if(isset($isExist->id)){
                    $productDetail = [];

                    $productDetail['id'] = $isExist->id;
                    $productDetail['quantity'] = $product->quantity;

                    array_push($productArray,(object) $isExist->id);

                    array_push($orderData,(object) $productDetail);
                }
                else{
                    $response = ['status' => false, "message"=> ['Invalid Product Id.'], "responseCode" => 422];
                    $encryptedResponse['data'] = $this->encryptData($response);
                    return response($encryptedResponse, 400);
                }
            }
            else{
                $response = ['status' => false, "message"=> ['Invalid input data.'], "responseCode" => 422];
                $encryptedResponse['data'] = $this->encryptData($response);
                return response($encryptedResponse, 400);
            }
        }

        if(isset($inputData->purchaseId) && $inputData->purchaseId != null && $inputData->purchaseId != ""){
            $isExistPurchase = PurchaseOrder::where('id',$inputData->purchaseId)->where('user_id',auth()->user()->id)->where('status',0)->first();

            if(isset($isExistPurchase->id)){
                $purchaseOrder = $isExistPurchase;
            }
            else{
                $response = ['status' => false, "message"=> ['Invalid Purchase Order Id.'], "responseCode" => 422];
                $encryptedResponse['data'] = $this->encryptData($response);
                return response($encryptedResponse, 400);
            }
        }
        else{
            $purchaseOrder = new PurchaseOrder;

            $purchaseOrder->user_id   = auth()->user()->id;
            $purchaseOrder->status    = 0;
            $purchaseOrder->supplier_id = isset($inputData->supplierId) ? $this->decryptId($inputData->supplierId) : "";
        }

        $purchaseOrder->note      = isset($inputData->note) ? $inputData->note : $purchaseOrder->note;
        $purchaseOrder->save();

        if(isset($inputData->purchaseId) && $inputData->purchaseId != null && $inputData->purchaseId != ""){
            $missedItems = PurchaseOrderItem::where('purchase_order_id',$purchaseOrder->id)->whereNotIn('product_id',$productArray)->get();
        }

        foreach($orderData as $item){
            $orderItem = PurchaseOrderItem::where('purchase_order_id',$purchaseOrder->id)->where('product_id',$item->id)->first();

            if(isset($orderItem->id)){
                $itemData = $orderItem;
            }
            else{
                $itemData = new PurchaseOrderItem;

                $itemData->purchase_order_id = $purchaseOrder->id;
                $itemData->product_id = $item->id;
                $itemData->supplier_id = isset($inputData->supplierId) ? $this->decryptId($inputData->supplierId) : "";
                $itemData->status = 1;
            }

            $itemData->quantity = $item->quantity;

            $itemData->save();
        }

        if(isset($missedItems) && count($missedItems) != 0){

            foreach($missedItems as $delete){
                $delete->delete();
            }
        }

        $response['status'] = true;
        $response["message"] = ['Saved successfully.'];
        $response['responseCode'] = 200;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }

    public function purchaseOrderList(Request $request)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }

        $query = PurchaseOrder::query();

        if (isset($inputData->status) && $inputData->status != null && $inputData->status != "") {
            $query = $query->where('status',$inputData->status);
        }

        if (isset($inputData->userId) && $inputData->userId != null && $inputData->userId != "") {
            $query = $query->where('user_id',$this->decryptId($inputData->userId));
        }

        if (isset($inputData->supplierId) && $inputData->supplierId != null && $inputData->supplierId != "") {
            $query = $query->where('supplier_id',$this->decryptId($inputData->supplierId));
        }

        $purchaseOrderCount = $query->count();

        $purchaseOrders = $query->orderBy('id','desc')->paginate(isset($inputData->countPerPage) ? $inputData->countPerPage : 20);

        $purchaseOrderArray = [];

        foreach($purchaseOrders as $purchaseOrder){
            $purchaseOrderList   = [];
            $userList            = [];
            $userArray           = [];

            $user = User::where('id',$purchaseOrder->user_id)->first();

            if(isset($user->id)) {
                $userList['userId']      = $this->encryptId($user->id);
                $userList['userName']    = $user->name;
                $userList['userEmail']   = $user->email;
                $userList['userNumber']  = $user->number;

                array_push($userArray,(object) $userList);
            }

            $purchaseOrderList['id']        = $this->encryptId($purchaseOrder->id);
            $purchaseOrderList['user']      = $userArray;
            $purchaseOrderList['note']      = $purchaseOrder->note;
            $purchaseOrderList['supplier']  = $purchaseOrder->supplier_id;
            $purchaseOrderList['status']    = $purchaseOrder->status;

            if($purchaseOrder->status == 0) {
                $purchaseOrderList['statusName']  = 'Requested';
            }else {
                $purchaseOrderList['statusName']  = 'Pending';
            }

            array_push($purchaseOrderArray,(object) $purchaseOrderList);
        }

        $response['status'] = true;
        $response["message"] = ['Retrieved Successfully.'];
        $response['response']["purchaseOrders"] = $purchaseOrderArray;
        $response['response']["totalPurchaseOrder"] = $purchaseOrderCount;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);

    }

    public function purchaseOrderDetails(Request $request)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }

        $rulesArray = [
            'purchaseOrderId' => 'required'
        ];

        $validatedData = Validator::make((array)$inputData, $rulesArray);

        if($validatedData->fails()) {
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $purchaseOrder = PurchaseOrder::where('id',$this->decryptId($inputData->purchaseOrderId))->first();

        if (!isset($purchaseOrder->id)) {
            $response = ['status' => false, "message"=> ['Invalid Purchase Order Id.'], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $purchaseOrderArray  = [];

        if(isset($purchaseOrder->id)) {
            $purchaseOrderList   = [];

            $user = User::where('id',$purchaseOrder->user_id)->first();

            if(isset($user->id)) {
                $userList            = [];
                $userArray           = [];

                $userList['userId']      = $this->encryptId($user->id);
                $userList['userName']    = $user->name;
                $userList['userEmail']   = $user->email;
                $userList['userNumber']  = $user->number;

                array_push($userArray,(object) $userList);
            }


            $purchaseOrderItems = PurchaseOrderItem::where('purchase_order_id',$purchaseOrder->id)->get();

            $purchaseOrderItemArray  = [];

            foreach($purchaseOrderItems as $purchaseOrderItem) {
                $purchaseOrderItemList   = [];

                $product = Product::where('id',$purchaseOrderItem->product_id)->first();

                $productArray        = [];

                if(isset($product->id)) {
                    $productList         = [];
                    $imageArray          = [];

                    foreach((array)json_decode($product->image_url) as $imageUrl){
                        $imageList  = [];

                        $imageList['fileName'] = $imageUrl;
                        $imageList['previewUrl'] = Storage::disk('public')->url('document/'.$imageUrl);
                        array_push($imageArray,(object) $imageList);
                    }

                    $category = ProductCategory::where('id',$product->category)->first();

                    $productList['id']            = $this->encryptId($product->id);
                    $productList['categoryId']    = $this->encryptId($product->category);
                    $productList['categoryName']  = $category->category;
                    $productList['name']          = $product->name;
                    $productList['price']         = $product->price;
                    $productList['discountPrice'] = $product->discount_price	;
                    $productList['image']         = $imageArray;

                    array_push($productArray,(object) $productList);
                }
                $purchaseOrderItemList['id']       = $this->encryptId($purchaseOrderItem->id);
                $purchaseOrderItemList['product']  = $productArray;
                $purchaseOrderItemList['quantity'] = $purchaseOrderItem->quantity;
                $purchaseOrderItemList['status']   = $purchaseOrderItem->status;

                array_push($purchaseOrderItemArray,(object) $purchaseOrderItemList);
            }

            $purchaseOrderList['id']      = $purchaseOrder->id;
            $purchaseOrderList['user']    = $userArray;
            $purchaseOrderList['note']    = $purchaseOrder->note;
            $purchaseOrderList['supplier']= $purchaseOrder->supplier_id;
            $purchaseOrderList['status']  = $purchaseOrder->status;

            if($purchaseOrder->status == 0) {
                $purchaseOrderList['statusName']  = 'Requested';
            }else {
                $purchaseOrderList['statusName']  = 'Pending';
            }

            $purchaseOrderList['items']   = $purchaseOrderItemArray;

            array_push($purchaseOrderArray,(object) $purchaseOrderList);
        }

         $response['status'] = true;
         $response["message"] = ['Retrieved Successfully.'];
         $response['response']["purchaseOrder"] = $purchaseOrderArray;

         $encryptedResponse['data'] = $this->encryptData($response);
         return response($encryptedResponse, 200);
    }
}
