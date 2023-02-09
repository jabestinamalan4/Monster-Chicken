<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Branch;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Jobs\SendEmailJob;
use App\Http\Traits\HelperTrait;
use Illuminate\Support\Facades\Validator;

class UserManagementController extends Controller
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

        $inputUser = $request->user;

        $rulesArray = [
                        'name' => 'required',
                        'email' => 'required|unique:users',
                        'number' => 'required|unique:users',
                    ];

        $validatedData = Validator::make((array)$inputData, $rulesArray);

        if($validatedData->fails()) {
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 400];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        if (isset($inputUser->id)) {
            $user = User::where('id',$this->encryptData($inputUser->id))->first();

            if(!isset($user->id)){
                $response = ['status' => false, "message"=>"This user is does not exist", "responseCode" => 400];
                $encryptedResponse['data'] = $this->encryptData($response);
                return response($encryptedResponse, 400);
            }
        }
        else{
            $user = new User;
        }
        $password = $this->generatePassword();
        $user->name = $inputData->name;
        $user->email = $inputData->email;
        $user->number = $inputData->number;
        $user->password = Hash::make($password);
        $user->status = 1;

        $user->save();

        $user->assignRole('franchise');

        if(isset($user->id) && ($user->id!=null || $user->id!="")){
            dispatch(new SendEmailJob($user,'add_user'));
        }


        $response['status'] = true;
        $response["message"] = ['Registered successfully.'];
        $response['responseCode'] = 200;
        $response['response']['accessToken'] = $this->getAccessToken($user);

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

        $rulesArray = [ 'userId' => 'required' ];

        $validatedData = Validator::make((array)$inputData, $rulesArray);

        if($validatedData->fails()) {
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 400];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $userDetails = User::where('id',$this->decryptId($inputData->userId))->first();

        if(isset($userDetails->id)){

            if($userDetails->status==1){
                $userDetails->status = 0;
            }else{
                $userDetails->status = 1;
            }

            $userDetails->save();
        }
        else{
            $response = ['status' => false, "message"=>"Invalid User Id", "responseCode" => 400];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $response['status'] = true;
        $response["message"] = ['Status Updated Successfully.'];
        $response['responseCode'] = 200;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);

    }

    public function storeBranch(Request $request)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }

        $rulesArray = [
            'address1' => 'required',
            'address2' => 'required',
            'pinCode' => 'required|min:6|max:6',
            'district' => 'required',
            'state' => 'required',
            'number' => 'required|min:10|max:10',
            'latitude' => 'required',
            'longitude' => 'required',
            'staffs' => 'required',
            'userId' => 'required',
        ];

        $validatedData = Validator::make((array)$inputData, $rulesArray);

        if($validatedData->fails()) {
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 400];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        if (isset($inputData->branchId)) {
            $branch = Branch::where('id',$this->decryptId($inputData->branchId))->first();

            if(!isset($branch->id)) {
                $response = ['status' => false, "message"=>"This branch is does not exist", "responseCode" => 400];
                $encryptedResponse['data'] = $this->encryptData($response);
                return response($encryptedResponse, 400);
            }
        }
        else{
            $branch = new Branch;
        }
        $branch->address_line_1   = $inputData->address1;
        $branch->address_line_2   = $inputData->address2;
        $branch->pin_code         = $inputData->pinCode;
        $branch->district         = $inputData->district;
        $branch->latitude         = $inputData->latitude;
        $branch->longitude        = $inputData->longitude;
        $branch->staffs           = $inputData->staffs;
        $branch->state            = $inputData->state;
        $branch->number           = $inputData->number;
        $branch->user_id          = $this->decryptId($inputData->userId);
        $branch->save();

        $response['status'] = true;
        $response["message"] = ['Registered successfully.'];
        $response['responseCode'] = 200;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }

    public function branchList(Request $request)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }
        $query = Branch::query();

        if (isset($inputData->userId) && $inputData->userId != null && $inputData->userId != "") {
            $query = $query->where('user_id',$this->decryptId($inputData->userId));
        }
        if (isset($inputData->search) && $inputData->search != null && $inputData->search != "") {
            $search = $inputData->search;
            $query = $query->where(function ($function) use($search) {
                $function->where('name', 'like', '%' . $search . '%');
                $function->where('district', 'like', '%' . $search . '%');
                $function->where('user_id', 'like', '%' . $search . '%');
          });
        }

        $branchCount = $query->count();

        $branches = $query->orderBy('id','desc')->paginate(isset($inputData->countPerPage) ? $inputData->countPerPage : 20);

        $totalArray = [];

        foreach($branches as $branch){
            $branchList = [];

            $user = User::where('id',$branch->user_id)->first();

            $branchList['id']         = $this->encryptId($branch->id);
            $branchList['userName']   = $user->name;
            $branchList['userEmail']  = $user->email;
            $branchList['userNumber'] = $user->number;
            $branchList['address1']   = $branch->address_line_1;
            $branchList['address2']   = $branch->address_line_2;
            $branchList['pinCode']    = $branch->pin_code;
            $branchList['district']   = $branch->district;
            $branchList['state']      = $branch->state;
            $branchList['number']     = $branch->number;
            $branchList['latitude']   = $branch->latitude;
            $branchList['longitude']  = $branch->longitude;
            $branchList['staffs']     = $branch->staffs;

            array_push($totalArray,$branchList);
        }


         $response['status'] = true;
         $response["message"] = ['Retrieved Successfully.'];
         $response['response']["Branch"] = $totalArray;
         $response['response']["totalBranch"] = $branchCount;

         $encryptedResponse['data'] = $this->encryptData($response);
         return response($encryptedResponse, 200);
    }


}
