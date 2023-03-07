<?php

namespace App\Http\Controllers\Admin;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Branch;
use App\Models\Stock;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use App\Models\ProductCategory;
use App\Models\PurchaseOrderStatus;
use App\Models\PurchaseOrderItemsStatus;
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

        $rulesArray = ['productData' => 'required|array'];

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
            $purchaseOrder->supplier_id = isset($inputData->supplierId) ? $this->decryptId($inputData->supplierId) : null;
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
                $itemData->supplier_id = isset($inputData->supplierId) ? $this->decryptId($inputData->supplierId) : null;
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
            $supplierList        = [];

            $user = User::where('id',$purchaseOrder->user_id)->first();

            if(isset($user->id)) {
                $userList['userId']      = $this->encryptId($user->id);
                $userList['userName']    = $user->name;
                $userList['userEmail']   = $user->email;
                $userList['userNumber']  = $user->number;
            }

            $supplier = Supplier::where('id',$purchaseOrder->supplier_id)->first();

            if(isset($supplier->id))
            {
                $supplierList['id']    = $this->encryptId($supplier->id);
                $supplierList['name']  = $supplier->name;
            }

            if(isset($purchaseOrder->status))
            {
                $purchaseOrderStatus = PurchaseOrderStatus::where('status',$purchaseOrder->status)->first();
            }

            $purchaseOrderList['id']        = $this->encryptId($purchaseOrder->id);
            $purchaseOrderList['user']      = (object) $userList;
            $purchaseOrderList['note']      = isset($purchaseOrder->note) && $purchaseOrder->note!= null? $purchaseOrder->note:'-';
            $purchaseOrderList['supplier']  = (object) $supplierList;
            $purchaseOrderList['status']    = $purchaseOrder->status;
            $purchaseOrderList['statusName']= isset($purchaseOrderStatus->id) ? $purchaseOrderStatus->name:"" ;
            $purchaseOrderList['created_at']= date("Y-m-d", strtotime($purchaseOrder->created_at));

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
        $supplierList        = [];

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
            }

            $supplier = Supplier::where('id',$purchaseOrder->supplier_id)->first();

            if(isset($supplier->id))
            {
                $supplierList['id']    = $this->encryptId($supplier->id);
                $supplierList['name']  = $supplier->name;
            }

            $purchaseOrderItems = PurchaseOrderItem::where('purchase_order_id',$purchaseOrder->id)->get();

            $purchaseOrderItemArray  = [];

            foreach($purchaseOrderItems as $purchaseOrderItem) {
                $purchaseOrderItemList   = [];
                $productArray            = [];

                $product = Product::where('id',$purchaseOrderItem->product_id)->first();

                $purchaseOrderItemsStatus = PurchaseOrderItemsStatus::where('status',$purchaseOrderItem->status)->first();

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
                    $productList['discountPrice'] = $product->discount_price;
                    $productList['image']         = $imageArray;
                }
                $purchaseOrderItemList['id']       = $this->encryptId($purchaseOrderItem->id);
                $purchaseOrderItemList['uniqueId'] = $this->encryptId($purchaseOrderItem->purchase_order_id);
                $purchaseOrderItemList['product']  = (object) $productList;
                $purchaseOrderItemList['quantity'] = $purchaseOrderItem->quantity;
                $purchaseOrderItemList['status']   = $purchaseOrderItem->status;
                $purchaseOrderItemList['statusName']= isset($purchaseOrderItemsStatus->id) ? $purchaseOrderItemsStatus->name : "";
                $purchaseOrderItemList['created_at']= date("Y-m-d", strtotime($purchaseOrderItem->created_at));

                array_push($purchaseOrderItemArray,(object) $purchaseOrderItemList);
            }
            $purchaseOrderItems = PurchaseOrderItem::where('purchase_order_id',$purchaseOrder->id)->where('supplier_id','=',null)->count();

            if($purchaseOrderItems==0)
            {
                $changeOutForDelivery    = true;
            }
            else{
                $changeOutForDelivery    = false;
            }

            if(isset($purchaseOrder->status))
            {
                $purchaseOrderStatus = PurchaseOrderStatus::where('status',$purchaseOrder->status)->first();
            }

            if(isset($purchaseOrder->user_id))
            {
                if($purchaseOrder->user_id==Auth()->user()->id)  {
                    $editLable = true;
                }
                else{
                    $editLable = false;
                }
            }

            $purchaseOrderList['id']                    = $this->encryptId($purchaseOrder->id);
            $purchaseOrderList['user']                  = (object) $userList;
            $purchaseOrderList['note']                  = isset($purchaseOrder->note) && $purchaseOrder->note!= null? $purchaseOrder->note:'-';
            $purchaseOrderList['supplier']              = (object) $supplierList;
            $purchaseOrderList['status']                = $purchaseOrder->status;
            $purchaseOrderList['statusName']            = isset($purchaseOrderStatus->id) ? $purchaseOrderStatus->name : "";
            $purchaseOrderList['created_at']            = date("Y-m-d", strtotime($purchaseOrder->created_at));
            $purchaseOrderList['items']                 = $purchaseOrderItemArray;
            $purchaseOrderList['changeOutForDelivery']  = $changeOutForDelivery;
            $purchaseOrderList['edit_label']            = $editLable;
        }

         $response['status'] = true;
         $response["message"] = ['Retrieved Successfully.'];
         $response['response']["purchaseOrder"] = (object) $purchaseOrderList;

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
            'purchaseOrderId' => 'required'
        ];

        $validatedData = Validator::make((array)$inputData, $rulesArray);

        if($validatedData->fails()) {
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }


        $isExistPurchaseOrderItems = PurchaseOrderItem::where('purchase_order_id',$this->decryptId($inputData->purchaseOrderId))->first();

        if (!isset($isExistPurchaseOrderItems->id)) {
            $response = ['status' => false, "message"=> ['Invalid Purchase Order Id.'], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $supplier = Supplier::where('id',$this->decryptId($inputData->supplierId))->first();

        if(!isset($supplier->id)) {
            $response = ['status' => false, "message"=> ['Invalid Supplier Id.'], "responseCode" => 422];
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

                $isExistPurchaseOrder = PurchaseOrderItem::where('product_id',$this->decryptId($product))->where('purchase_order_id',$this->decryptId($inputData->purchaseOrderId))->first();

                if(!isset($isExistPurchaseOrder->id)) {
                    $response = ['status' => false, "message"=> ['Invalid Product Id.'], "responseCode" => 422];
                    $encryptedResponse['data'] = $this->encryptData($response);
                    return response($encryptedResponse, 400);
                }

                $isExistPurchaseOrderItems = PurchaseOrderItem::where('purchase_order_id',$this->decryptId($inputData->purchaseOrderId))->first();

                if(!isset($isExistPurchaseOrderItems->id)) {
                    $response = ['status' => false, "message"=> ['Invalid Purchase Order Id.'], "responseCode" => 422];
                    $encryptedResponse['data'] = $this->encryptData($response);
                    return response($encryptedResponse, 400);
                }

                if(isset($inputData->purchaseOrderItemsId))
                {
                    $purchaseOrderItems = PurchaseOrderItem::where('id',$this->decryptId($inputData->purchaseOrderItemsId))->where('product_id',$this->decryptId($product))
                                        ->where('purchase_order_id',$this->decryptId($inputData->purchaseOrderId))->whereIn('status',[1,2])->first();

                    if(!isset($purchaseOrderItems->id)) {
                        $response = ['status' => false, "message"=> ['Invalid Purchase Order Items Id.'], "responseCode" => 422];
                        $encryptedResponse['data'] = $this->encryptData($response);
                        return response($encryptedResponse, 400);
                    }
                }

                else {
                    $purchaseOrderItems = PurchaseOrderItem::where('product_id',$this->decryptId($product))->where('purchase_order_id',$this->decryptId($inputData->purchaseOrderId))->first();
                }

                $purchaseOrderItems->supplier_id = $this->decryptId($inputData->supplierId);
                $purchaseOrderItems->status = 2;
                $purchaseOrderItems->save();
            }

        $response['status'] = true;
        $response["message"] = ['Assigned successfully.'];
        $response['responseCode'] = 200;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);

        }
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
            'purchaseOrderId' => 'required'
        ];

        $validatedData = Validator::make((array)$inputData, $rulesArray);

        if($validatedData->fails()) {
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $purchaseOrderItems = PurchaseOrderItem::where('purchase_order_id',$this->decryptId($inputData->purchaseOrderId))
                            ->where('supplier_id', '!=', null)->where('status',2)->get();

        if(isset($purchaseOrderItems))
        {
            foreach($purchaseOrderItems as $purchaseOrderItems)
            {
                $purchaseOrderItems->status = 3;
                $purchaseOrderItems->save();
            }

            $purchaseOrder = PurchaseOrder::where('id',$this->decryptId($inputData->purchaseOrderId))->first();

            $purchaseOrder->status = 2;
            $purchaseOrder->save();
        }

        $response['status'] = true;
        $response["message"] = ['Updated successfully.'];
        $response['responseCode'] = 200;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }

    public function productReceived(Request $request)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }

        $rulesArray = [
            'purchaseOrderId' => 'required',
            'status' => 'required',
            'productId' => 'required|array'
        ];

        if(isset($inputData->status) && $inputData->status !='Received')
        {
            $rulesArray['quantity'] = 'required';
        }

        $validatedData = Validator::make((array)$inputData, $rulesArray);

        if($validatedData->fails()) {
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $purchaseOrder = PurchaseOrder::where('id',$this->decryptId($inputData->purchaseOrderId))->first();

        if(!isset($purchaseOrder->id)) {
            $response = ['status' => false, "message"=> ['Invalid PurchaseOrder Id.'], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        if($purchaseOrder->status==2)
        {
            foreach($inputData->productId as $product)
            {
                $purchaseOrderItem = PurchaseOrderItem::where('product_id',$this->decryptId($product))
                                    ->where('purchase_order_id',$this->decryptId($inputData->purchaseOrderId))->first();

                if(!isset($purchaseOrderItem->id))
                {
                    $response = ['status' => false, "message"=> ['Invalid Product Id'], "responseCode" => 422];
                    $encryptedResponse['data'] = $this->encryptData($response);
                    return response($encryptedResponse, 400);
                }

                if($purchaseOrderItem->status!=3)
                {
                    $response = ['status' => false, "message"=> ['Cannot change to purchase order Items status'], "responseCode" => 422];
                    $encryptedResponse['data'] = $this->encryptData($response);
                    return response($encryptedResponse, 400);
                }
            }

            foreach($inputData->productId as $product)
            {
                $purchaseOrderItem = PurchaseOrderItem::where('product_id',$this->decryptId($product))
                                    ->where('purchase_order_id',$this->decryptId($inputData->purchaseOrderId))->first();

                if($purchaseOrderItem->status==3)
                {
                    if($inputData->status =='Received')
                    {
                        $purchaseOrderItem->status = 4;
                        $purchaseOrderItem->save();
                    }
                    else{
                        $purchaseOrderItem->change_quantity = $inputData->quantity;
                        $purchaseOrderItem->status = 5;
                        $purchaseOrderItem->save();
                    }

                    if(isset($purchaseOrderToDelivered->id)){
                        $branch = Branch::where('user_id',$purchaseOrder->user_id)->first();

                        if(isset($inputData->status) && $inputData->status !='Received')
                        {
                            $quantity = $inputData->quantity;
                        }
                        else{
                            $quantity = $purchaseOrderToDelivered->quantity;
                        }

                        $stock = new Stock;
                        $stock->product_id  = $purchaseOrderItem->product_id;
                        $stock->quantity    = $quantity;
                        $stock->branch_id   = $branch->id;
                        $stock->save();
                    }
                }
                else{
                    $response = ['status' => false, "message"=> ['Cannot change to purchase order Items status'], "responseCode" => 422];
                    $encryptedResponse['data'] = $this->encryptData($response);
                    return response($encryptedResponse, 400);
                }
            }
        }

        else
        {
            $response = ['status' => false, "message"=> ['Cannot change to purchase order status'], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $response['status'] = true;
        $response["message"] = ['Purchase order delivered successfully.'];
        $response['responseCode'] = 200;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }

    public function orderReceived(Request $request)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }

        $rulesArray = [
            'purchaseOrderId' => 'required',
        ];

        $validatedData = Validator::make((array)$inputData, $rulesArray);

        if($validatedData->fails()) {
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $purchaseOrder = PurchaseOrder::where('id',$this->decryptId($inputData->purchaseOrderId))->first();

        if(!isset($purchaseOrder->id)) {
            $response = ['status' => false, "message"=> ['Invalid PurchaseOrder Id.'], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $purchaseOrderItems = PurchaseOrderItem::where('purchase_order_id',$this->decryptId($inputData->purchaseOrderId))->get();

            foreach($purchaseOrderItems as $purchaseOrderItem)
            {
                if($purchaseOrderItem->status >= 4)
                {
                    if($purchaseOrderItem->status==5)
                    {
                        $status = 4;

                        $purchaseOrder->status = $status;
                        $purchaseOrder->save();

                        $response['status'] = true;
                        $response["message"] = ['Status updated successfully.'];
                        $response['responseCode'] = 200;

                        $encryptedResponse['data'] = $this->encryptData($response);
                        return response($encryptedResponse, 200);
                    }
                    else{
                        $status = 3;
                    }
                }

                else {
                    $response = ['status' => false, "message"=> ['Cannot change to purchase order status'], "responseCode" => 422];
                    $encryptedResponse['data'] = $this->encryptData($response);
                    return response($encryptedResponse, 400);
                }
            }

        $purchaseOrder->status = $status;
        $purchaseOrder->save();

        $response['status'] = true;
        $response["message"] = ['Status updated successfully.'];
        $response['responseCode'] = 200;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);

    }

}
