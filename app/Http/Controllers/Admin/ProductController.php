<?php

namespace App\Http\Controllers\Admin;

use App\Models\Product;
use App\Models\Cart;
use Illuminate\Http\Request;
use App\Models\ProductCategory;
use App\Http\Traits\HelperTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
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
                        'category' => 'required',
                        'productName' => 'required',
                        'description' => 'required',
                        'imageUrl' => 'required|array',
                        'price' => 'required',
                        'discountPrice' => 'required',
                    ];

        $validatedData = Validator::make((array)$inputData, $rulesArray);

        if($validatedData->fails()) {
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $isExist = ProductCategory::where('id',$this->decryptId($inputData->category))->where('status',1)->first();

        if (!isset($isExist->id)) {
            $response = ['status' => false, "message"=> ['Invalid Category'], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        if (isset($inputData->productId)) {
            $isExist = Product::find($this->decryptId($inputData->productId));
            if (isset($isExist->id)) {
                $product = $isExist;
            }
            else{
                $response = ['status' => false, "message"=> ['Invalid Id'], "responseCode" => 422];
                $encryptedResponse['data'] = $this->encryptData($response);
                return response($encryptedResponse, 400);
            }
        }
        else{
            $product = new Product;
            $product->rating = 5;
            $product->reviews = 0;
        }

        $product->status = isset($inputData->status) ? $inputData->status : 1;
        $product->name = $inputData->productName;
        $product->category = $this->decryptId($inputData->category);
        $product->description = $inputData->description;
        $product->price = $inputData->price;
        $product->discount_price = $inputData->discountPrice;
        $product->image_url = json_encode($inputData->imageUrl);

        $product->save();

        $response['status'] = true;
        $response["message"] = ['Saved successfully.'];
        $response['responseCode'] = 200;

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
                        'category' => 'required|unique:product_categories',
                        'imageUrl' => 'required'
                    ];

        if (isset($inputData->categoryId) && $inputData->categoryId != null && $inputData->categoryId != "") {
            $rulesArray['category'] = "required|unique:product_categories,category,".$inputData->categoryId;
        }

        $validatedData = Validator::make((array)$inputData, $rulesArray);

        if($validatedData->fails()) {
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        if (isset($inputData->categoryId)) {
            $isExist = ProductCategory::find($this->decryptId($inputData->categoryId));
            if (isset($isExist->id)) {
                $category = $isExist;
            }
            else{
                $response = ['status' => false, "message"=> ['Invalid Id'], "responseCode" => 422];
                $encryptedResponse['data'] = $this->encryptData($response);
                return response($encryptedResponse, 400);
            }
        }
        else{
            $category = new ProductCategory;
        }

        $category->status = isset($inputData->status) ? $inputData->status : 1;
        $category->category = $inputData->category;
        $category->image_url = $inputData->imageUrl;

        $category->save();

        $response['status'] = true;
        $response["message"] = ['Saved successfully.'];
        $response['responseCode'] = 200;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }

    public function productList(Request $request)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }
        $query = Product::query();

        $categoryArray = [];

        $categories = ProductCategory::where('status',1)->get();

        foreach($categories as $category){

            $categoryDetail = [];

            $categoryDetail['categoryId'] = $this->encryptId($category->id);
            $categoryDetail['categoryName'] = $category->category;
            $categoryDetail['imageUrl'] = Storage::disk('public')->url('document/'.$category->image_url);
            $categoryDetail['status'] = $category->status;

            $productCount = Product::where('status',1)->where('category',$category->id)->count();
            $categoryDetail['productCount'] = $productCount;

            if (isset($inputData->category) && $inputData->category == $category->id) {
                $categoryDetail['selected'] = true;
            }
            else{
                $categoryDetail['selected'] = false;
            }

            array_push($categoryArray,(object) $categoryDetail);
        }
        if (isset($inputData->status) && $inputData->status != null && $inputData->status != "") {
            $query = $query->where('status',$inputData->status);
        }
        if (isset($inputData->category) && $inputData->category != null && $inputData->category != "") {
            $query = $query->where('category',$inputData->category);
        }

        if (isset($inputData->maxPrice) && $inputData->maxPrice != null && $inputData->maxPrice != "") {
            $query = $query->where('price',"<=",$inputData->maxPrice);
        }

        if (isset($inputData->minPrice) && $inputData->minPrice != null && $inputData->minPrice != "") {
            $query = $query->where('price',">=",$inputData->minPrice);
        }

        if (isset($inputData->search) && $inputData->search != null && $inputData->search != "") {
            $search = $inputData->search;
            $query = $query->where(function ($function) use($search) {
                $function->where('name', 'like', '%' . $search . '%');
          });
        }
        $productCount = $query->count();

        $products = $query->orderBy('id','desc')->paginate(isset($inputData->countPerPage) ? $inputData->countPerPage : 20);

        $totalArray   = [];
        $productsList = [];

        foreach($products as $product){

            $imageList    = [];

            $category = ProductCategory::where('id',$product->category)->first();

            $productsList['id']             = $this->encryptId($product->id);
            $productsList['categoryId']     = $product->category;
            $productsList['category']       = isset($category) && ($category!=null || $category!="" ) ? $category->category:"";
            $productsList['name']           = $product->name;
            $productsList['description']    = $product->description;
            $productsList['price']          = $product->price;
            $productsList['discountPrice']  = $product->discount_price;
            $productsList['rating']         = $product->rating;
            $productsList['reviews']        = $product->reviews;
            $productsList['updatedAt']      = $product->updated_at;
            $productsList['status']         = $product->status;

            foreach((array)json_decode($product->image_url) as $imageUrl){
                $imageData = [];

                $imageData['fileName'] = $imageUrl;
                $imageData['previewUrl'] = Storage::disk('public')->url('document/'.$imageUrl);
                array_push($imageList,(object) $imageData);
            }

            $productsList['imageUrl']       = $imageList;

            array_push($totalArray,(object) $productsList);
        }


         $response['status'] = true;
         $response["message"] = ['Retrieved Successfully.'];
         $response['response']["categories"] = $categoryArray;
         $response['response']["products"] = $totalArray;
         $response['response']["totalProduct"] = $productCount;

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

        $query = ProductCategory::query();

        if (isset($inputData->status) && $inputData->status != null && $inputData->status != "") {
            $query = $query->where('status',$inputData->status);
        }

        if (isset($inputData->search) && $inputData->search != null && $inputData->search != "") {
            $search = $inputData->search;
           $query = $query->where(function ($function) use($search) {
                $function->where('category', 'like', '%' . $search . '%');
          });
        }

        $categoryList = [];

        $categoryCount = $query->count();

        $categories = $query->orderBy('id','desc')->paginate(isset($inputData->countPerPage) ? $inputData->countPerPage : 12);

        foreach($categories as $category){

            $categoryDetail = [];

            $categoryDetail['categoryId'] = $this->encryptId($category->id);
            $categoryDetail['categoryName'] = $category->category;
            $categoryDetail['imageUrl'] = Storage::disk('public')->url('document/'.$category->image_url);
            $categoryDetail['status'] = $category->status;

            $productCount = Product::where('status',1)->where('category',$category->id)->count();
            $categoryDetail['productCount'] = $productCount;

            array_push($categoryList,(object) $categoryDetail);
        }

        $response['status'] = true;
        $response["message"] = ['Retrieved Successfully.'];
        $response['response']["categories"] = $categoryList;
        $response['response']["totalProduct"] = $categoryCount;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);

    }

    public function changeCategoryStatus(Request $request)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }

        $rulesArray = ['categoryId' => 'required'];

        $validatedData = Validator::make((array)$inputData, $rulesArray);

         if($validatedData->fails()) {
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $productCategory = ProductCategory::where('id',$this->decryptId($inputData->categoryId))->first();

        if(!isset($productCategory->id)){
            $response = ['status' => false, "message"=> ["Invalid Category Id."], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        if(isset($productCategory->status) && $productCategory->status==1){
            $productCategory->status = 0;
        }else{
            $productCategory->status = 1;
        }

        $productCategory->save();

        if(isset($productCategory->id)){

            $product = Product::where('category',$productCategory->id)->get();

            foreach($product as $product){
                $product->status = $productCategory->status;
                $product->save();
            }
        }

        $response['status'] = true;
        $response["message"] = ['Status Updated Successfully.'];
        $response['responseCode'] = 200;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);

    }

    public function changeStatus(Request $request)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }

        $rulesArray = ['productId' => 'required'];

        $validatedData = Validator::make((array)$inputData, $rulesArray);

         if($validatedData->fails()) {
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $product = Product::where('id',$this->decryptId($inputData->productId))->first();

        if(!isset($product->id)){
            $response = ['status' => false, "message"=> ["Invalid Product Id."], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        if(isset($product->status) && $product->status==1){
            $product->status = 0;
        }else{
            $product->status = 1;
        }

        $product->save();

        if(isset($product->id) && ($product->id!="" || $product->id!=null)){

            $cart = Cart::where('product_id',$product->id)->get();

            foreach($cart as $carts){

                $carts = Cart::where('id',$carts->id)->first();

                $carts->status = $product->status;
                $carts->save();
            }
        }

        $response['status'] = true;
        $response["message"] = ['Status Updated Successfully.'];
        $response['responseCode'] = 200;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }
}
