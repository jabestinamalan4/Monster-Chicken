<?php

namespace App\Http\Controllers\Customer;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\ProductCategory;
use App\Http\Traits\HelperTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    use HelperTrait;

    public function productList(Request $request)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }

        $categories = ProductCategory::where('status',1)->get();

        $categoryArray = [];

        foreach($categories as $category){
            $categoryDetail = [];

            $categoryDetail['categoryId'] = $this->encryptId($category->id);
            $categoryDetail['categoryName'] = $category->category;
            $categoryDetail['imageUrl'] = Storage::disk('public')->url('document/'.$category->image_url);

            $productCount = Product::where('status',1)->where('category',$category->id)->count();
            $categoryDetail['productCount'] = $productCount;

            if (isset($inputData->categoryId) && $inputData->categoryId == $category->id) {
                $categoryDetail['selected'] = true;
            }
            else{
                $categoryDetail['selected'] = false;
            }

            array_push($categoryArray,$categoryDetail);
        }

        $products = Product::where('status',1);

        if (isset($inputData->category) && $inputData->category != null && $inputData->category != "") {
            $products = $products->where('category',$inputData->category);
        }

        if (isset($inputData->search) && $inputData->search != null && $inputData->search != "") {
            $products = $products->where('name', 'like', '%'.$inputData->search.'%');
        }

        $totalCount = $products->count();
        $products = $products->orderBy('id','DESC')->paginate(isset($inputData->countPerPage) ? $inputData->countPerPage : 12);

        $productArray = [];

        foreach($products as $product){
            $productDetail = [];

            $productDetail['productId'] = $this->encryptId($product->id);
            $productDetail['productName'] = $product->name;

            $category = ProductCategory::where('id',$product->category)->where('status',1)->first();
            if (isset($category->category)) {
                $productDetail['categoryName'] = $category->category;
                $productDetail['categoryId'] = $category->id;
            }

            $productDetail['price'] = $product->price;
            $productDetail['discountPrice'] = $product->discount_price;
            $productDetail['description'] = $product->description;
            $productDetail['rating'] = $product->rating;
            $productDetail['reviews'] = $product->reviews;

            $imageArray = [];

            foreach(json_decode($product->image_url) as $image){
                $imageUrl = Storage::disk('public')->url('document/'.$image);

                array_push($imageArray,$imageUrl);
            }

            $productDetail['imageUrl'] = $imageArray;

            array_push($productArray,$productDetail);
        }

        $response['status'] = true;
        $response['responseCode'] = 200;
        $response["message"] = ['Retrieved successfully.'];
        $response['response']['categories'] = $categoryArray;
        $response['response']['products'] = $productArray;
        $response['response']['totalCount'] = $totalCount;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }

    public function productDetails(Request $request)
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
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 400];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $productId = $this->decryptId($inputData->productId);

        $product = Product::where('status',1)->where('id',$productId)->first();

        if (!isset($product->id)) {
            $response = ['status' => false, "message"=> ['Invalid Product Id.'], "responseCode" => 400];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $productDetail = [];

        $productDetail['productId'] = $this->encryptId($product->id);
        $productDetail['productName'] = $product->name;

        $category = ProductCategory::where('id',$product->category)->where('status',1)->first();
        if (isset($category->category)) {
            $productDetail['categoryName'] = $category->category;
            $productDetail['categoryId'] = $category->id;
        }

        $productDetail['price'] = $product->price;
        $productDetail['discountPrice'] = $product->discount_price;
        $productDetail['description'] = $product->description;
        $productDetail['rating'] = $product->rating;
        $productDetail['reviews'] = $product->reviews;

        if (isset($inputUser->id)) {
            $cartExist = Cart::where('status',1)->where('product_id',$product->id)->where('user_id',$inputUser->id)->first();

            if (isset($cartExist->id)) {
                $productDetail['cartQuantity'] = $cartExist->quantity;
            }
            else{
                $productDetail['cartQuantity'] = 0;
            }
        }

        $imageArray = [];

        foreach(json_decode($product->image_url) as $image){
            $imageUrl = Storage::disk('public')->url('document/'.$image);

            array_push($imageArray,$imageUrl);
        }

        $productDetail['imageUrl'] = $imageArray;

        $response['status'] = true;
        $response['responseCode'] = 200;
        $response["message"] = ['Retrieved successfully.'];
        $response['response']['productDetail'] = $productDetail;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }
}