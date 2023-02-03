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

            if (isset($inputData->category) && $inputData->category == $category->id) {
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

            $wishlistExist = Wishlist::where('product_id',$product->id)->where('user_id',$inputUser->id)->where('status',1)->first();

            if (isset($wishlistExist->id)) {
                $productDetail['wishlist'] = true;
            }
            else{
                $productDetail['wishlist'] = false;
            }

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

        $wishlistExist = Wishlist::where('product_id',$productId)->where('user_id',$inputUser->id)->where('status',1)->first();

        if (isset($wishlistExist->id)) {
            $productDetail['wishlist'] = true;
        }
        else{
            $productDetail['wishlist'] = false;
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

    public function wishlistStore(Request $request)
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
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $productId = $this->decryptId($inputData->productId);

        $productExist = Product::where('id',$productId)->where('status',1)->first();

        if(!isset($productExist->id)){
            $response = ['status' => false, "message"=> ["Invalid Product Id."], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $wishlistExist = Wishlist::where('product_id',$productId)->where('user_id',$inputUser->id)->first();

        if (isset($inputData->wishlistId)) {
            $isExist = Wishlist::find($this->decryptId($inputData->wishlistId));
            if (isset($isExist->id)) {
                $wishlist = $isExist;
            }
            else{
                $response = ['status' => false, "message"=> ['Invalid Id'], "responseCode" => 400];
                $encryptedResponse['data'] = $this->encryptData($response);
                return response($encryptedResponse, 400);
            }
        }
        elseif(isset($wishlistExist->id)){
            $wishlist = $wishlistExist;
        }
        else{
            $wishlist = new Wishlist;
            $wishlist->user_id = $inputUser->id;
            $wishlist->product_id = $productId;
        }

        if (isset($wishlist->status) && $wishlist->status == 1) {
            $wishlist->status = 0;
        }
        else{
            $wishlist->status = 1;
        }

        $wishlist->save();

        $response['status'] = true;
        $response['responseCode'] = 200;
        $response["message"] = ['Saved successfully.'];

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }

    public function wishlist(Request $request)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }

        $inputUser = $request->user;

        $totalCount = Wishlist::with('product')->whereRelation('product', 'status', 1)->where('status',1)->where('user_id',$inputUser->id)->count();
        $wishlists = Wishlist::with('product')->whereRelation('product', 'status', 1)->where('status',1)->where('user_id',$inputUser->id)->orderBy('id','DESC')->paginate(isset($inputData->countPerPage) ? $inputData->countPerPage : 12);

        $listArray = [];

        foreach($wishlists as $list){
            $listDetail = [];

            $listDetail['listId'] = $this->encryptId($list->id);
            $listDetail['productId'] = $this->encryptId($list->product->id);
            $listDetail['productName'] = $list->product->name;

            $isCategoryExist = ProductCategory::where('status',1)->where('id',$list->product->category)->first();
            if (isset($isCategoryExist->id)) {
                $listDetail['productCategory'] = $isCategoryExist->category;
            }
            else{
                $listDetail['productCategory'] = "";
            }

            $listDetail['price'] = $list->product->price;

            array_push($listArray,$listDetail);
        }

        $response['status'] = true;
        $response['responseCode'] = 200;
        $response["message"] = ['Saved successfully.'];
        $response["list"] = $listArray;
        $response["totalCount"] = $totalCount;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }

    public function categoryList(Request $request)
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

            array_push($categoryArray,$categoryDetail);
        }

        $response['status'] = true;
        $response['responseCode'] = 200;
        $response["message"] = ['Saved successfully.'];
        $response["categories"] = $categoryArray;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }
}