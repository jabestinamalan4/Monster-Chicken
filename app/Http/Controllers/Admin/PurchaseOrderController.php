<?php

namespace App\Http\Controllers\Admin;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use App\Http\Traits\HelperTrait;
use App\Models\PurchaseOrderItem;
use App\Http\Controllers\Controller;
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

        $productArray = [];
        $orderData = [];

        foreach($productData as $product){

            if(isset($product->id) && isset($product->quantity)){
                $isExist = Product::where('id',$this->decryptId($product->id))->where('status',1)->first();

                if(isset($isExist->id)){
                    $productDetail = [];

                    $productDetail['id'] = $isExist->id;
                    $productDetail['quantity'] = $product->quantity;

                    array_push($productArray,$isExist->id);

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

            $purchaseOrder->user_id = auth()->user()->id;
            $purchaseOrder->status = 0;
        }

        $purchaseOrder->note = isset($inputData->note) ? $inputData->note : $purchaseOrder->note;
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
                $itemData->status = 1;
            }

            $itemData->quantity = $item->quantity;

            $itemData->save();
        }

        foreach($missedItems as $delete){
            $delete->delete();
        }

        $response['status'] = true;
        $response["message"] = ['Saved successfully.'];
        $response['responseCode'] = 200;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }
}
