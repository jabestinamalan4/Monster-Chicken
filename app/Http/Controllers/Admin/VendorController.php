<?php

namespace App\Http\Controllers\Admin;

use App\Models\Vendor;
use App\Models\ProductCategory;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\HelperTrait;
use Illuminate\Support\Facades\Validator;

class VendorController extends Controller
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
                        'emailId' => 'required|email',
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

        if (isset($inputData->vendorId)) {
            $vendor = Vendor::where('id',$this->decryptId($inputData->vendorId))->first();

            if(!isset($vendor->id)){
                $response = ['status' => false, "message"=>"This vendor is does not exist", "responseCode" => 422];
                $encryptedResponse['data'] = $this->encryptData($response);
                return response($encryptedResponse, 400);
            }
        }
        else{
            $vendor = new Vendor;
        }

        $vendor->type          = json_encode($inputData->type);
        $vendor->name          = $inputData->name;
        $vendor->address       = $inputData->address;
        $vendor->pin_code      = $inputData->pinCode;
        $vendor->district      = $inputData->district;
        $vendor->state         = $inputData->state;
        $vendor->number        = $inputData->number;
        $vendor->email_id      = $inputData->emailId;
        $vendor->contact_name  = $inputData->contactName;
        $vendor->latitude      = $inputData->latitude;
        $vendor->longitude     = $inputData->longitude;
        $vendor->status        = 1;
        $vendor->save();

        $response['status'] = true;
        $response["message"] = ['Registered successfully.'];
        $response['responseCode'] = 200;


        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }

    public function vendorList(Request $request)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }
        $query = Vendor::query();

        if (isset($inputData->status) && $inputData->status != null && $inputData->status != "") {
            $query = $query->where('status',$inputData->status);
        }
        if (isset($inputData->type) && $inputData->type != null && $inputData->type != "") {
            $query = $query->where('type',$inputData->type);
        }
        if (isset($inputData->search) && $inputData->search != null && $inputData->search != "") {
            $search = $inputData->search;
            $query  = $query->where(function ($function) use($search) {
                $function->Where('name', 'like', '%' . $search . '%');
                $function->orWhere('email_id', 'like', '%' . $search . '%');
                $function->orWhere('number_1', 'like', '%' . $search . '%');
                $function->orWhere('number_2', 'like', '%' . $search . '%');
                $function->orWhere('type', 'like', '%' . $search . '%');
                $function->orWhere('district', 'like', '%' . $search . '%');
                $function->orWhere('state', 'like', '%' . $search . '%');
          });
        }

        $vendorCount = $query->count();

        $vendors = $query->orderBy('id','desc')->paginate(isset($inputData->countPerPage) ? $inputData->countPerPage : 20);

        $totalArray = [];

        foreach($vendors as $vendor){
            $vendorList = [];
            $categoryList = [];
            $types =json_decode($vendor->type);
            foreach((array)$types as $type) {
                $productCat = [];

                $category = ProductCategory::where('id',$type)->first();

                $productCat['id']       = isset($category->id) ? $this->encryptId($category->id) :"";
                $productCat['category'] = isset($category->category) ? $category->category : "";

                array_push($categoryList,(object) $productCat);
            }

            $vendorList['id']         = $this->encryptId($vendor->id);
            $vendorList['type']       = $categoryList;
            $vendorList['name']       = $vendor->name;
            $vendorList['address']    = $vendor->address;
            $vendorList['pinCode']    = $vendor->pin_code;
            $vendorList['district']   = $vendor->district;
            $vendorList['state']      = $vendor->state;
            $vendorList['number']     = $vendor->number;
            $vendorList['emailId']    = $vendor->email_id;
            $vendorList['contactName']= $vendor->contact_name;
            $vendorList['latitude']   = $vendor->latitude;
            $vendorList['longitude']  = $vendor->longitude;
            $vendorList['status']     = $vendor->status;

            array_push($totalArray,(object) $vendorList);
        }


         $response['status'] = true;
         $response["message"] = ['Retrieved Successfully.'];
         $response['response']["Vendor"] = $totalArray;
         $response['response']["totalVendor"] = $vendorCount;

         $encryptedResponse['data'] = $this->encryptData($response);
         return response($encryptedResponse, 200);
    }
}
