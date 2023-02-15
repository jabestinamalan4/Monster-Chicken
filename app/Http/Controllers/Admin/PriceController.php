<?php

namespace App\Http\Controllers\Admin;

use App\Models\Price;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\HelperTrait;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PriceController extends Controller
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

        $rulesArray = [
                        'productId' => 'required',
                        'price' => 'required|numeric',
                        'discountPrice' => 'required|numeric',
                    ];

        $validatedData = Validator::make((array)$inputData, $rulesArray);

        if($validatedData->fails()) {
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        if (isset($inputData->productId)) {
                $product = Product::where('id',$this->decryptId($inputData->productId))->where('status',1)->first();

                if(!isset($product->id)){
                    $response = ['status' => false, "message"=>"This Product is does not exist", "responseCode" => 423];
                    $encryptedResponse['data'] = $this->encryptData($response);
                    return response($encryptedResponse, 400);
                }
        }

        $product->price = $inputData->price;
        $product->discount_price = $inputData->discountPrice;
        $product->save();

        $response['status'] = true;
        $response["message"] = ['Registered Successfully.'];
        $response['responseCode'] = 200;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }

}
