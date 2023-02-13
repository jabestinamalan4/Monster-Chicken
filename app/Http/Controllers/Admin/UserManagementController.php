<?php

namespace App\Http\Controllers\Admin;

use Crypt;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Jobs\SendEmailJob;
use App\Http\Traits\HelperTrait;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
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

        $inputUser = auth()->user();

        $rulesArray = [
                        'name' => 'required',
                        'email' => 'required|unique:users',
                        'number' => 'required|unique:users',
                        'role' => 'required',
                    ];

        if(isset($inputData->role) && ($inputData->role=='cuttingCenter' || $inputData->role=='retailer')){
            $rulesArray['admin_id']= 'required';
            $rulesArray['address1']= 'required';
            $rulesArray['address2']= 'required';
            $rulesArray['pinCode'] = 'required|min:6|max:6';
            $rulesArray['district']= 'required';
            $rulesArray['state']   = 'required';
            $rulesArray['number']  = 'required|unique:branches|min:10|max:10';
            $rulesArray['latitude']= 'required';
            $rulesArray['longitude']= 'required|numeric|between:0,99.99';
            $rulesArray['staffs']   = 'required|numeric|between:0,99.99';
            $rulesArray['type']   = 'required|numeric';
        }

        $validatedData = Validator::make((array)$inputData, $rulesArray);

        if($validatedData->fails()) {
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }
        if ($this->isRoleExist($inputData->role)) {

        }
        else{
            $response = ['status' => false, "message"=> ['The given role does not exist.'], "responseCode" => 423];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }
        if (isset($inputData->userId)) {
            $user = User::where('id',$this->decryptId($inputData->userId))->first();

            if(!isset($user->id)){
                $response = ['status' => false, "message"=>"This user is does not exist", "responseCode" => 423];
                $encryptedResponse['data'] = $this->encryptData($response);
                return response($encryptedResponse, 400);
            }
        }
        else{
            $user = new User;
        }

        $password       = $this->generatePassword();
        $user->name     = $inputData->name;
        $user->email    = $inputData->email;
        $user->number   = $inputData->number;
        $user->password = Hash::make($password);
        $user->password_raw = $password;
        $user->status   = 1;
        if($inputData->role =='cuttingCenter' && $inputData->role=='retailer'){
            $user->admin_id = $this->decryptId($inputData->admin_id);
        }
        $user->save();

        if($inputData->role =='franchise'){
            $user->admin_id  = $user->id;
            $user->save();
        }


        $user->assignRole($inputData->role);

        if(isset($user->id) && ($user->id!=null || $user->id!="")){
            dispatch(new SendEmailJob($user,'add_user'));
        }

        if(isset($inputData->role) && $inputData->role=='cuttingCenter' || $inputData->role=='retailer'){
             $this->storeBranch($inputData,$user->id);
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
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 422];
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
            $response = ['status' => false, "message"=>"Invalid User Id", "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $response['status'] = true;
        $response["message"] = ['Status Updated Successfully.'];
        $response['responseCode'] = 200;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);

    }

    public function storeBranch($inputData,$userId)
    {

        if (isset($inputData->branchId)) {
            $branch = Branch::where('id',$this->decryptId($inputData->branchId))->first();

            if(!isset($branch->id)) {
                $response = ['status' => false, "message"=>"This branch is does not exist", "responseCode" => 422];
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
        $branch->type             = $inputData->type;
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
            $branchList['type']       = $branch->type;
            $branchList['address1']   = $branch->address_line_1;
            $branchList['address2']   = $branch->address_line_2;
            $branchList['pinCode']    = $branch->pin_code;
            $branchList['district']   = $branch->district;
            $branchList['state']      = $branch->state;
            $branchList['number']     = $branch->number;
            $branchList['latitude']   = $branch->latitude;
            $branchList['longitude']  = $branch->longitude;
            $branchList['staffs']     = $branch->staffs;

            array_push($totalArray,(object) $branchList);
        }


         $response['status'] = true;
         $response["message"] = ['Retrieved Successfully.'];
         $response['response']["Branch"] = $totalArray;
         $response['response']["totalBranch"] = $branchCount;

         $encryptedResponse['data'] = $this->encryptData($response);
         return response($encryptedResponse, 200);
    }

    public function userList(Request $request)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }

        $query = User::query();

        if (isset($inputData->adminId) && $inputData->adminId != null && $inputData->adminId != "") {
            $query = $query->where('admin_id',$this->decryptId($inputData->adminId));
        }
        if (isset($inputData->search) && $inputData->search != null && $inputData->search != "") {
            $search = $inputData->search;
            $query  = $query->where(function ($function) use($search) {
                $function->Where('name', 'like', '%' . $search . '%');
          });
        }

        $userCount = $query->count();

        $users = $query->orderBy('id','desc')->paginate(isset($inputData->countPerPage) ? $inputData->countPerPage : 20);

        $totalArray = [];

        foreach($users as $user){
            $userList   = [];
            $adminList  = [];

            $admin = User::where('id',$user->admin_id)->first();

            if(isset($admin)) {
                $adminarr = [];

                $adminarr['name'] = $admin->name;
                $adminarr['email'] = $admin->email;

                array_push($adminList,$adminarr);
            }

            $userList['id']     = $this->encryptId($user->id);
            $userList['name']   = $user->name;
            $userList['email']  = $user->email;
            $userList['number'] = $user->number;
            $userList['admin']  = $adminList;

            array_push($totalArray,(object) $userList);
        }

        $response['status'] = true;
         $response["message"] = ['Retrieved Successfully.'];
         $response['response']["user"] = $totalArray;
         $response['response']["totaLUser"] = $userCount;

         $encryptedResponse['data'] = $this->encryptData($response);
         return response($encryptedResponse, 200);
    }

    public function rolesList(Request $request)
    {
        $existRoles = Auth::user();

        if($existRoles-hasRole('writer')) {

        }
    }

}
