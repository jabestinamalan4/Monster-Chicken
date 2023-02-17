<?php

namespace App\Http\Controllers\Admin;

use App\Models\Supplier;
use App\Models\State;
use App\Models\ProductCategory;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\HelperTrait;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
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
                        'type' => 'required|array',
                        'name' => 'required',
                        'address' => 'required',
                        'pinCode' => 'required|numeric|digits_between:6,6',
                        'district' => 'required',
                        'state' => 'required',
                        'number' => 'required|numeric|digits_between:10,15',
                        'email' => 'required|email',
                        'contactName' => 'required',
                        'latitude' =>'required|numeric|between:0,99.99',
                        'longitude' => 'required|numeric|between:0,99.99',
                    ];

        $validatedData = Validator::make((array)$inputData, $rulesArray);

        if($validatedData->fails()) {
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        if (isset($inputData->state)) {
            $state = State::where('id',$inputData->state)->first();

            if(!isset($state->id)){
                $response = ['status' => false, "message"=>"This state is does not exist", "responseCode" => 422];
                $encryptedResponse['data'] = $this->encryptData($response);
                return response($encryptedResponse, 400);
            }
        }

        if (isset($inputData->supplierId)) {
            $supplier = Supplier::where('id',$this->decryptId($inputData->supplierId))->first();

            if(!isset($supplier->id)){
                $response = ['status' => false, "message"=>"This supplier is does not exist", "responseCode" => 422];
                $encryptedResponse['data'] = $this->encryptData($response);
                return response($encryptedResponse, 400);
            }
        }
        else{
            $supplier = new Supplier;
        }

        if(isset($inputData->type)) {
            $typeList = [];

            foreach($inputData->type as $type) {

                $type = $this->decryptId($type);
               array_push($typeList, $type);
            }
        }

        $supplier->type          = json_encode($typeList);
        $supplier->name          = $inputData->name;
        $supplier->address       = $inputData->address;
        $supplier->pin_code      = $inputData->pinCode;
        $supplier->district      = $inputData->district;
        $supplier->state         = $inputData->state;
        $supplier->number        = $inputData->number;
        $supplier->email         = $inputData->email;
        $supplier->contact_name  = $inputData->contactName;
        $supplier->latitude      = $inputData->latitude;
        $supplier->longitude     = $inputData->longitude;
        $supplier->status        = 1;
        $supplier->save();

        $response['status'] = true;
        $response["message"] = ['Registered successfully.'];
        $response['responseCode'] = 200;


        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }

    public function supplierList(Request $request)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }
        $query = Supplier::query();

        if (isset($inputData->status) && $inputData->status != null && $inputData->status != "") {
            $query = $query->where('status',$inputData->status);
        }

        if (isset($inputData->search) && $inputData->search != null && $inputData->search != "") {
            $search = $inputData->search;
            $query  = $query->where(function ($function) use($search) {
                $function->Where('name', 'like', '%' . $search . '%');
                $function->orWhere('email', 'like', '%' . $search . '%');
                $function->orWhere('number', 'like', '%' . $search . '%');
                $function->orWhere('district', 'like', '%' . $search . '%');
                $function->orWhere('state', 'like', '%' . $search . '%');
          });
        }

        $supplierCount = $query->count();

        $suppliers = $query->orderBy('id','desc')->paginate(isset($inputData->countPerPage) ? $inputData->countPerPage : 20);

        $totalArray = [];

        foreach($suppliers as $supplier){
            $stateArray   = [];
            $supplierList = [];
            $categoryList = [];
            $stateList    = [];

            $types =json_decode($supplier->type);

            foreach((array)$types as $type) {
                $productCat = [];

                $category = ProductCategory::where('id',$type)->first();

                $productCat['id']   = isset($category->id) ? $this->encryptId($category->id) :"";
                $productCat['name'] = isset($category->category) ? $category->category : "";

                array_push($categoryList,(object) $productCat);
            }

            if(isset($supplier->state)) {

                $stateName = State::where('id',$supplier->state)->first();

                $stateList['id']   = $stateName->id;
                $stateList['name'] = $stateName->state;

                array_push($stateArray,(object) $stateList);
            }

            $supplierList['id']         = $this->encryptId($supplier->id);
            $supplierList['categories'] = $categoryList;
            $supplierList['name']       = $supplier->name;
            $supplierList['address']    = $supplier->address;
            $supplierList['pinCode']    = $supplier->pin_code;
            $supplierList['district']   = $supplier->district;
            $supplierList['state']      = $stateArray;
            $supplierList['number']     = $supplier->number;
            $supplierList['email']      = $supplier->email;
            $supplierList['contactName']= $supplier->contact_name;
            $supplierList['latitude']   = $supplier->latitude;
            $supplierList['longitude']  = $supplier->longitude;
            $supplierList['status']     = $supplier->status;

            array_push($totalArray,(object) $supplierList);
        }


         $response['status'] = true;
         $response["message"] = ['Retrieved Successfully.'];
         $response['response']["suppliers"] = $totalArray;
         $response['response']["totalSupplier"] = $supplierCount;

         $encryptedResponse['data'] = $this->encryptData($response);
         return response($encryptedResponse, 200);
    }

    public function getSupplier(Request $request)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }
        $query = Supplier::query();


        if (isset($inputData->search) && $inputData->search != null && $inputData->search != "") {
            $search = $inputData->search;
            $query  = $query->where(function ($function) use($search) {
                $function->Where('name', 'like', '%' . $search . '%');
                $function->orWhere('email', 'like', '%' . $search . '%');
          });
        }

        $suppliers = $query->orderBy('id','desc')->paginate(isset($inputData->countPerPage) ? $inputData->countPerPage : 20);

        $suppliersArray  = [];


        foreach($suppliers as $supplier){
            $categoryArray = [];
            $supplierList  = [];
            $categoryList  = [];

            foreach(json_decode($supplier->type) as $category) {

                $categoryName = ProductCategory::where('id',$category)->first();

                $categoryList['id']   = $this->encryptId($categoryName->id);
                $categoryList['name'] = $categoryName->category;

                array_push($categoryArray,$categoryList);
            }
            $supplierList['id']         = $this->encryptId($supplier->id);
            $supplierList['categories'] = $categoryArray;
            $supplierList['name']       = $supplier->name;
            $supplierList['number']     = $supplier->number;
            $supplierList['email']      = $supplier->email;

            array_push($suppliersArray,(object) $supplierList);
        }


         $response['status'] = true;
         $response["message"] = ['Retrieved Successfully.'];
         $response['response']["suppliers"] = $suppliersArray;

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

        $rulesArray = [ 'supplierId' => 'required' ];

        $validatedData = Validator::make((array)$inputData, $rulesArray);

        if($validatedData->fails()) {
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $supplierDetails = Supplier::where('id',$this->decryptId($inputData->supplierId))->first();

        if(isset($supplierDetails->id)){

            if($supplierDetails->status==1){
                $supplierDetails->status = 0;
            }else{
                $supplierDetails->status = 1;
            }

            $supplierDetails->save();
        }
        else{
            $response = ['status' => false, "message"=>"Invalid Supplier Id", "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $response['status'] = true;
        $response["message"] = ['Status Updated Successfully.'];
        $response['responseCode'] = 200;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);

    }

    public function supplierDetails(Type $var = null)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }

        $rulesArray = [ 'supplierId' => 'required' ];

        $validatedData = Validator::make((array)$inputData, $rulesArray);

        if($validatedData->fails()) {
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $supplierDetails = Supplier::where('id',$this->decryptId($inputData->supplierId))->first();

        if(isset($supplierDetails->id)){

            $stateArray   = [];
            $supplierList = [];
            $categoryList = [];
            $stateList    = [];

            $types =json_decode($supplier->type);

            foreach((array)$types as $type) {
                $productCat = [];

                $category = ProductCategory::where('id',$type)->first();

                $productCat['id']   = isset($category->id) ? $this->encryptId($category->id) :"";
                $productCat['name'] = isset($category->category) ? $category->category : "";

                array_push($categoryList,(object) $productCat);
            }

            if(isset($supplier->state)) {

                $stateName = State::where('id',$supplier->state)->first();

                $stateList['id']   = $stateName->id;
                $stateList['name'] = $stateName->state;

                array_push($stateArray,(object) $stateList);
            }

            $supplierList['id']         = $this->encryptId($supplier->id);
            $supplierList['categories'] = $categoryList;
            $supplierList['name']       = $supplier->name;
            $supplierList['address']    = $supplier->address;
            $supplierList['pinCode']    = $supplier->pin_code;
            $supplierList['district']   = $supplier->district;
            $supplierList['state']      = $stateArray;
            $supplierList['number']     = $supplier->number;
            $supplierList['email']      = $supplier->email;
            $supplierList['contactName']= $supplier->contact_name;
            $supplierList['latitude']   = $supplier->latitude;
            $supplierList['longitude']  = $supplier->longitude;
            $supplierList['status']     = $supplier->status;

            $supplierDetails = (object) $supplierList;
        }
        else{
            $response = ['status' => false, "message"=> ['Invalid supplier Id.'], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $response['status'] = true;
        $response["message"] = ['Status Updated Successfully.'];
        $response['responseCode'] = 200;
        $response['response']['supplierDetails'] = $supplierDetails;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }
}
