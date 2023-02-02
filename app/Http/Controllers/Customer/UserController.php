<?php

namespace App\Http\Controllers\Customer;

use Illuminate\Http\Request;
use App\Models\ProductCategory;
use App\Http\Traits\HelperTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    use HelperTrait;

    public function dashboard(Request $request)
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
            if (isset($inputData->categoryId) && $inputData->categoryId == $category->id) {
                $categoryDetail['selected'] = true;
            }
            else{
                $categoryDetail['selected'] = false;
            }

            array_push($categoryArray,$categoryDetail);
        }

        $products = Product::where('status',1)->orderBy('DESC','id')->paginate(12);

        $productArray = [];

        foreach($products as $product){
            $productDetail = [];

            $productDetail['productId'] = $this->encryptId($product->id);
            $productDetail['productName'] = $product->name;

            $category = ProductCategory::where('id',$product->category)->where('status',1)->first();
            if (isset($category->category)) {
                $productDetail['categoryName'] = $category->category;
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

            $productDetail['reviews'] = $product->reviews;

            array_push($productArray,$productDetail);
        }

        $response['status'] = true;
        $response['responseCode'] = 200;
        $response["message"] = ['Retrieved successfully.'];
        $response['response']['categories'] = $categoryArray;
        $response['response']['products'] = $productArray;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }
}