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

        if(isset($inputData->priceId)){
            $price = Price::where('id',$this->decryptId($inputData->priceId))->first();
        }
        else{
            $price = new Price;
        }

        $price->product_id = $this->decryptId($inputData->productId);
        $price->price = $inputData->price;
        $price->discount_price = $inputData->discountPrice;
        $price->save();

        $response['status'] = true;
        $response["message"] = ['Registered Successfully.'];
        $response['responseCode'] = 200;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }

    public function priceList(Request $request)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }

        $query = Price::query();

        if (isset($inputData->prouductId) && $inputData->prouductId != null && $inputData->prouductId != "") {
            $query = $query->where('product_id',$this->decryptId($inputData->prouductId));
        }
        if (isset($inputData->search) && $inputData->search != null && $inputData->search != "") {
            $search = $inputData->search;
            $query  = $query->where(function ($function) use($search) {
                $function->Where('price', 'like', '%' . $search . '%');
          });
        }

        $priceCount = $query->count();

        $prices = $query->orderBy('id','desc')->paginate(isset($inputData->countPerPage) ? $inputData->countPerPage : 20);

        $totalArr     = [];
        $priceList    = [];
        $productList  = [];
        $imageList    = [];

        foreach($prices as $price){
            $product = Product::where('id',$price->product_id)->first();
            if(isset($product)) {
                $products     = [];
                $productsList = [];

                $category = ProductCategory::where('id',$product->category)->first();

                foreach((array)json_decode($product->image_url) as $image_url){
                    array_push($imageList,Storage::disk('public')->url('document/'.$image_url));
                }


                $products['name']       = $product->name;
                $products['category']   = $category->category;
                $products['price']      = $product->price;
                $products['discountPrice']= $product->discount_price;
                $products['imageUrl']   = $imageList;

                array_push($productList,(object)$products);
            }

            $priceList['id']         = $this->encryptId($price->id);
            $priceList['product']    = $productList;
            $priceList['price']       = $price->price;
            $priceList['discountPrice'] = $price->discount_price;

            array_push($totalArr,(object)$priceList);
        }

        $response['status'] = true;
         $response["message"] = ['Retrieved Successfully.'];
         $response['response']["price"] = $totalArr;
         $response['response']["totalprice"] = $priceCount;

         $encryptedResponse['data'] = $this->encryptData($response);
         return response($encryptedResponse, 200);

    }
}
