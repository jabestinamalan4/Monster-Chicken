<?php

namespace App\Http\Controllers\Admin;

use App\Models\Stock;
use App\Models\Product;
USE App\Models\Branch;
use App\Models\User;
use App\Models\State;
use App\Models\ProductCategory;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Traits\HelperTrait;

class StockController extends Controller
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
            'branchId' => 'required',
            'quantity' => 'required|numeric',
        ];

        $validatedData = Validator::make((array)$inputData, $rulesArray);

        if($validatedData->fails()) {
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        if (isset($inputData->productId)) {
            $product = Product::where('id',$this->decryptId($inputData->productId))->first();

            if(!isset($product->id)){
                $response = ['status' => false, "message"=>"This product is does not exist", "responseCode" => 423];
                $encryptedResponse['data'] = $this->encryptData($response);
                return response($encryptedResponse, 400);
            }
        }

        if (isset($inputData->branchId)) {
            $branch = Branch::where('id',$this->decryptId($inputData->branchId))->first();

            if(!isset($branch->id)){
                $response = ['status' => false, "message"=>"This branch is does not exist", "responseCode" => 423];
                $encryptedResponse['data'] = $this->encryptData($response);
                return response($encryptedResponse, 400);
            }
        }

        if (isset($inputData->stockId)) {
            $stock = Stock::where('id',$this->decryptId($inputData->stockId))->first();

                if(!isset($Stock->id)){
                    $response = ['status' => false, "message"=>"This stock is does not exist", "responseCode" => 423];
                    $encryptedResponse['data'] = $this->encryptData($response);
                    return response($encryptedResponse, 400);
                }
        }
        else{
            $stock = new Stock;
        }

        $stock->product_id = $this->decryptId($inputData->productId);
        $stock->branch_id  = $this->decryptId($inputData->branchId);
        $stock->quantity   = $inputData->quantity;

        $stock->save();

        $response['status'] = true;
        $response["message"] = ['Registered Successfully.'];
        $response['responseCode'] = 200;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }

    public function stockList(Request $request)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }

        $query = Stock::query();

        if(isset($inputData->productId) && ($inputData->productId!="" || $inputData->productId!=null)) {
            $query = $query->where('product_id',$this->decryptId($inputData->productId));
        }

        if(isset($inputData->branchId) && ($inputData->branchId!="" || $inputData->branchId!=null)) {
            $query = $query->where('branch_id',$this->decryptId($inputData->branchId));
        }

        if (isset($inputData->search) && $inputData->search != null && $inputData->search != "") {
            $search = $inputData->search;
            $query  = $query->where(function ($function) use($search) {
                $function->Where('quantity', 'like', '%' . $search . '%');
          });
        }

        $stockCount = $query->count();

        $stocks = $query->orderBy('id','desc')->paginate(isset($inputData->countPerPage) ? $inputData->countPerPage : 20);

        $stockArray = [];

        foreach($stocks as $stock){
            $stockList    = [];
            $productArray = [];
            $branchArray  = [];
            $stateArray   = [];

            if(isset($stock->product_id)) {
                $productList = [];

                $product = Product::where('id',$stock->product_id)->first();

                $category = ProductCategory::where('id',$product->category)->first();

                $productList['id']        = $this->encryptId($product->id);
                $productList['name']      = $product->name;
                $productList['categoryId']= $this->encryptId($category->id);
                $productList['category']  = $category->category;
            }

            if(isset($stock->branch_id)) {
                $branchList = [];
                $stateList  = [];

                $branch = Branch::where('id',$stock->branch_id)->first();

                if(isset($branch->state)) {

                    $stateName = State::where('id',$branch->state)->first();

                    $stateList['id']   = $stateName->id;
                    $stateList['name'] = $stateName->state;
                }

                if(isset($branch->user_id))
                {
                    $user = User::where('id',$branch->user_id)->first();
                }

                $branchList['id']        = $this->encryptId($branch->id);
                $branchList['user']      = isset($user->name) ? $user->name: "";
                $branchList['district']  = $branch->district;
                $branchList['state']     = (object) $stateList;
            }

            $stockList['id']      = $this->encryptId($stock->id);
            $stockList['product'] = (object) $productList;
            $stockList['branch']  = (object) $branchList;
            $stockList['quantity']= $stock->quantity;

            array_push($stockArray,(object) $stockList);
        }

        $response['status'] = true;
        $response["message"] = ['Retrieved Successfully.'];
        $response['response']["stocks"] = $stockArray;
        $response['response']["totalStock"] = $stockCount;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }
}
