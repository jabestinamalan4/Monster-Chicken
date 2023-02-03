<?php

namespace App\Http\Controllers\Admin;

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
            $product = new Product;
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
            $rulesArray['category'] = "required|unique:product_categories,category,".$inputData->categoryId;
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
        $category->image_url = $inputData->imageUrl;

        $category->save();

        $response['status'] = true;
        $response['responseCode'] = 200;
        $response["message"] = ['Saved successfully.'];

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }

    public function productList(Request $request){
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }
        $query = Product::query();

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
        if (isset($inputData->productId) && $inputData->productId != null && $inputData->productId != "") {
            $query = $query->where('id',$inputData->productId);
        }
        if (isset($inputData->category) && $inputData->category != null && $inputData->category != "") {
            $query = $query->where('category',$inputData->category);
        }

        if (isset($inputData->maxPrice) && $inputData->maxPrice != null && $inputData->maxPrice != "") {
            $query = $query->where('price',">=",$inputData->maxPrice);
        }

        if (isset($inputData->minPrice) && $inputData->minPrice != null && $inputData->minPrice != "") {
            $query = $query->where('price',"<=",$inputData->minPrice);
        }

        if (isset($inputData->search) && $inputData->search != null && $inputData->search != "") {
            $search = $inputData->search;
           $query = $query->where(function ($function) use($search) {
                $function->where('name', 'like', '%' . $search . '%')
                ->orWhere('description', 'like', '%' . $search . '%')
                ->orWhere('price', 'like', '%' . $search . '%')
                ->orWhere('discount_price', 'like', '%' . $search . '%')
               ->orWhere('rating', 'like', '%' . $search . '%')
               ->orWhere('reviews', 'like', '%' . $search . '%');
          });
        }
        $productCount = $query->count();
        $produts = $query->orderBy('id','desc')->paginate(isset($inputData->countPerPage) ? $inputData->countPerPage : 12);
        $totalArray = [];
        foreach($produts as $produt){
            $productsList = [];
            $category = ProductCategory::where('id',$produt->category)->first();
            $productsList['id']             = $produt->id;
            $productsList['category']       = isset($category) && ($category!=null || $category!="" ) ? $category->category:"";
            $productsList['name']           = $produt->name;
            $productsList['imageUrl']       = Storage::disk('public')->url('document/'.implode(json_decode($produt->image_url)));
            $productsList['description']    = $produt->description;
            $productsList['price']          = $produt->price;
            $productsList['discountPrice']  = $produt->discount_price;
            $productsList['rating']         = $produt->rating;
            $productsList['reviews']        = $produt->reviews;
            $productsList['status']         = $produt->status;
            array_push($totalArray,$productsList);
        }


         $response['status'] = true;
         $response["message"] = ['Retrieved Successfully.'];
         $response['response']["categories"] = $categoryArray;
         $response['response']["products"] = $totalArray;
         $response['response']["totalProduct"] = $productCount;

         $encryptedResponse['data'] = $this->encryptData($response);
         return response($encryptedResponse, 200);

    }
    public function categoryList(Request $request){
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }

        $query = ProductCategory::query();

        if (isset($inputData->categoryId) && $inputData->categoryId != null && $inputData->categoryId != "") {
            $query = $query->where('id',$inputData->categoryId);
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

            $productCount = Product::where('status',1)->where('category',$category->id)->count();
            $categoryDetail['productCount'] = $productCount;

            array_push($categoryList,$categoryDetail);
        }

        $response['status'] = true;
        $response["message"] = ['Retrieved Successfully.'];
        $response['response']["categories"] = $categoryList;
        $response['response']["totalProduct"] = $categoryCount;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);

    }
}