<?php

namespace App\Http\Controllers\Customer;

use App\Models\Cart;
use App\Models\Product;
use App\Models\Wishlist;
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

        $inputUser = $request->user;

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
            $categoryDetail['status'] = $category->status;

            array_push($categoryArray,(object) $categoryDetail);
        }

        $products = Product::where('status',1)->orderBy('id','Desc')->paginate(12);

        $productArray = [];

        foreach($products as $product){
            $productDetail = [];

            $productDetail['productId'] = $this->encryptId($product->id);
            $productDetail['productName'] = $product->name;
            $productDetail['stock'] = 20;
            $productDetail['maxQuantity'] = 10;

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

            $imageUrls = json_decode($product->image_url);

            foreach(json_decode($product->image_url) as $image){
                $imageUrl = Storage::disk('public')->url('document/'.$image);

                array_push($imageArray,$imageUrl);
            }

            $productDetail['imageUrl'] = $imageArray;
            $productDetail['status'] = $product->status;

            if (isset($inputUser->id)) {
                $cartExist = Cart::where('status',1)->where('product_id',$product->id)->where('user_id',$inputUser->id)->first();

                if (isset($cartExist->id)) {
                    $productDetail['cartQuantity'] = $cartExist->quantity;
                }
                else{
                    $productDetail['cartQuantity'] = 0;
                }
            }

            $wishlistExist = Wishlist::where('product_id',$product->id)->where('user_id',$inputUser->id)->where('status',1)->first();

            if (isset($wishlistExist->id)) {
                $productDetail['wishlist'] = true;
            }
            else{
                $productDetail['wishlist'] = false;
            }

            array_push($productArray,(object) $productDetail);
        }

        if (isset($inputUser->id)) {
            $cartCount = Cart::where('status',1)->where('user_id',$inputUser->id)->count();
            $wishlistCount = Wishlist::where('status',1)->where('user_id',$inputUser->id)->count();
        }
        else{
            $cartCount = 0;
            $wishlistCount = 0;
        }

        $response['status'] = true;
        $response["message"] = ['Retrieved successfully.'];
        $response['responseCode'] = 200;
        $response['response']['categories'] = $categoryArray;
        $response['response']['products'] = $productArray;
        $response['response']['cartCount'] = $cartCount;
        $response['response']['wishlistCount'] = $wishlistCount;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }

    public function profile(Request $request)
    {
        $inputUser = $request->user;

        if (isset($inputUser->id)) {
            $userDetail = [];

            $userDetail['name'] = isset($inputUser->name) ? $inputUser->name : "";
            $userDetail['email'] = isset($inputUser->email) ? $inputUser->email : "";
            $userDetail['number'] = isset($inputUser->number) ? $inputUser->number : "";

            if (isset($inputUser->id)) {
                $cartCount = Cart::where('status',1)->where('user_id',$inputUser->id)->count();
                $wishlistCount = Wishlist::where('status',1)->where('user_id',$inputUser->id)->count();
            }
            else{
                $cartCount = 0;
                $wishlistCount = 0;
            }

            $response['status'] = true;
            $response["message"] = ['Retrieved successfully.'];
            $response['responseCode'] = 200;
            $response['response']['userDetail'] = $userDetail;
            $response['response']['cartCount'] = $cartCount;
            $response['response']['wishlistCount'] = $wishlistCount;

            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 200);

        }
        else{
            $response = ['status' => false, "message"=> ['Invalid User Details.']];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }
    }
}
