<?php

namespace App\Http\Controllers\Admin;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\ProductCategory;
use App\Http\Traits\HelperTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
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
                        'category' => 'required|integer',
                        'productName' => 'required',
                        'description' => 'required',
                        'imageUrl' => 'required',
                        'price' => 'required',
                        'discountPrice' => 'required',
                    ];

        $validatedData = Validator::make((array)$inputData, $rulesArray);

        if($validatedData->fails()) {
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 400];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $isExist = ProductCategory::where('id',$inputData->category)->where('status',1)->first();

        if (!isset($isExist->id)) {
            $response = ['status' => false, "message"=> ['Invalid Category'], "responseCode" => 400];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        if (isset($inputData->productId)) {
            $isExist = Product::find($inputData->productId);
            if (isset($isExist->id)) {
                $product = $isExist;
            }
            else{
                $response = ['status' => false, "message"=> ['Invalid Id'], "responseCode" => 400];
                $encryptedResponse['data'] = $this->encryptData($response);
                return response($encryptedResponse, 400);
            }
        }
        else{
            $product = new product;
            $product->rating = 5;
            $product->reviews = 0;
        }

        $product->status = isset($inputData->status) ? $inputData->status : 1;
        $product->name = $inputData->productName;
        $product->category = $inputData->category;
        $product->description = $inputData->description;
        $product->price = $inputData->price;
        $product->discount_price = $inputData->discountPrice;
        $product->image_url = json_encode($inputData->imageUrl);

        $product->save();

        $response['status'] = true;
        $response['responseCode'] = 200;
        $response["message"] = ['Saved successfully.'];

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }

    public function storeCategory(Request $request)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }

        $rulesArray = [
                        'category' => 'required|unique:product_categories'
                    ];

        if (isset($inputData->categoryId) && $inputData->categoryId != null && $inputData->categoryId != "") {
            $rulesArray['category'] = "required|unique:product_categories,category".$inputData->categoryId;
        }

        $validatedData = Validator::make((array)$inputData, $rulesArray);

        if($validatedData->fails()) {
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 400];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        if (isset($inputData->categoryId)) {
            $isExist = ProductCategory::find($inputData->categoryId);
            if (isset($isExist->id)) {
                $category = $isExist;
            }
            else{
                $response = ['status' => false, "message"=> ['Invalid Id'], "responseCode" => 400];
                $encryptedResponse['data'] = $this->encryptData($response);
                return response($encryptedResponse, 400);
            }
        }
        else{
            $category = new ProductCategory;
        }

        $category->status = isset($inputData->status) ? $inputData->status : 1;
        $category->category = $inputData->category;

        $category->save();

        $response['status'] = true;
        $response['responseCode'] = 200;
        $response["message"] = ['Saved successfully.'];

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }
}
