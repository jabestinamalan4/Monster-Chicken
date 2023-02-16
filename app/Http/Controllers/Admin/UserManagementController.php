<?php

namespace App\Http\Controllers\Admin;

use Crypt;
use App\Models\User;
use App\Models\State;
use App\Models\Branch;
use App\Jobs\SendEmailJob;
use Illuminate\Http\Request;
use App\Http\Traits\HelperTrait;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

        if(isset($inputData->userId) && ($inputData->userId!=null || $inputData->userId!="")) {
            $rulesArray['email'] = 'required|unique:users,email,' . $this->decryptId($inputData->userId);
            $rulesArray['number'] = 'required|unique:users,number,' . $this->decryptId($inputData->userId);
        }

        if(isset($inputData->role) && ($inputData->role=='cuttingCenter' || $inputData->role=='retailer')){
            $rulesArray['address1']= 'required';
            $rulesArray['address2']= 'required';
            $rulesArray['pinCode'] = 'required|min:6|max:6';
            $rulesArray['district']= 'required';
            $rulesArray['state']   = 'required|numeric';
            $rulesArray['number']  = 'required|unique:branches|min:10|max:15';
            $rulesArray['latitude']= 'required|numeric|between:0,99.99';
            $rulesArray['longitude']= 'required|numeric|between:0,99.99';
            $rulesArray['staffs']   = 'required';
        }

        $validatedData = Validator::make((array)$inputData, $rulesArray);

        if($validatedData->fails()) {
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        if ($this->isRoleExist($inputData->role) == false) {
            $response = ['status' => false, "message"=> ['The given role does not exist.'], "responseCode" => 423];
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

        if (isset($inputData->userId) && ($inputData->userId!="" || $inputData->userId!=null)) {
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

        if($inputData->role =='cuttingCenter' && $inputData->role=='retailer' && isset($inputData->adminId)){

            $isExistUser = User::where('id',$inputData->adminId)->where('status',1)->first();

            if(isset($isExistUser->id)){
                $user->admin_id = $this->decryptId($inputData->adminId);
            }
            else{
                $response = ['status' => false, "message"=>"Invalid admin Id.", "responseCode" => 423];
                $encryptedResponse['data'] = $this->encryptData($response);
                return response($encryptedResponse, 400);
            }
        }

        else{
            $user->admin_id  = auth()->user()->id;
        }

        $user->save();


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
        $branch->user_id          = $userId;
        $branch->save();

        $response['status'] = true;
        $response["message"] = ['Registered successfully.'];
        $response['responseCode'] = 200;

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

        $query = $query->whereNotNull('email');

        $query = $query->whereNotNull('name');

        $query = $query->where('id', '!=', 1);

        $userCount = $query->count();

        $users = $query->orderBy('id','desc')->paginate(isset($inputData->countPerPage) ? $inputData->countPerPage : 20);

        $userArray  = [];


        foreach($users as $user) {
            $branchArray= [];
            $userList   = [];
            $rolesList  = [];
            $rolesArray = [];
            $branchList = [];

            $admin = User::where('id',$user->admin_id)->first();

            $rolesName = $user->getRoleNames()->toArray();

            if(isset($admin->id)) {
                $adminArray = [];

                $adminArray['adminName'] = $admin->name;
                $adminArray['adminEmail'] = $admin->email;
            }

            $userList['id']     = $this->encryptId($user->id);
            $userList['name']   = $user->name;
            $userList['email']  = $user->email;
            $userList['number'] = $user->number;
            $userList['status'] = $user->status;
            $rolesList['key']   = ucfirst(ucwords(implode(' ',preg_split('/(?=[A-Z])/',implode(" ",$rolesName)))));
            $rolesList['value'] = implode(" ",$rolesName);

            array_push($rolesArray,$rolesList);

            $userList['role']   = $rolesArray;

            foreach($rolesName as $role)
            {
                $stateArray = [];
                $stateList  = [];

                if($role=="cuttingCenter" || $role=="retailer") {

                    $branch = Branch::where('user_id',$user->id)->first();

                    if(isset($branch->state)) {

                        $stateName = State::where('id',$branch->state)->first();

                        $stateList['id']   = $stateName->id;
                        $stateList['name'] = $stateName->state;

                        array_push($stateArray,(object) $stateList);
                    }

                    $branchList['address1'] = isset($branch->address_line_1) ? $branch->address_line_1 : "";
                    $branchList['address2'] = isset($branch->address_line_2) ? $branch->address_line_2 : "";
                    $branchList['pinCode']  = isset($branch->pin_code) ? $branch->pin_code : "";
                    $branchList['district'] = isset($branch->district) ? $branch->district : "";
                    $branchList['state']    = isset($branch->state) ? $stateArray : "";
                    $branchList['number']   = isset($branch->number) ? $branch->number : "";
                    $branchList['latitude'] = isset($branch->latitude) ? $branch->latitude : "";
                    $branchList['longitude']= isset($branch->longitude) ? $branch->longitude : "";
                    $branchList['staffs']   = isset($branch->staffs) ? $branch->staffs : "";

                    array_push($branchArray,$branchList);
                }
            }

            $userList['branch'] = $branchArray;

            array_push($userArray,(object) $userList);
        }

        $response['status'] = true;
        $response["message"] = ['Retrieved Successfully.'];
        $response['response']["user"] = $userArray;
        $response['response']["totalUser"] = $userCount;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }

    public function rolesList(Request $request)
    {
        $roles = Role::where('name','!=','customer');

        $userRoles = auth()->user()->getRoleNames()->toArray();

        if (auth()->user()->hasRole('admin') == true) {
            $roles = $roles->where('name','!=','admin');
        }

        if (auth()->user()->hasRole('franchise') == true) {
            $roles = $roles->where('name','!=','franchise');
        }

        $roles = $roles->get(['name','name AS value']);

        $roleArray = [];

        $rolesList = [];

        foreach($roles as $role) {

            $rolesList['key'] = ucfirst(ucwords(implode(' ',preg_split('/(?=[A-Z])/',$role['name']))));
            $rolesList['value'] = $role->value;

            array_push($roleArray,$rolesList);
        }

        $response['response']['roles'] = $roleArray;

        $response['status'] = true;
        $response["message"] = ['Retrieved Successfully.'];
        $response['responseCode'] = 200;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }

    public function getUsers(Request $request)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }

        $query = User::query();

        if (isset($inputData->search) && $inputData->search != null && $inputData->search != "") {
            $search = $inputData->search;
            $query  = $query->where(function ($function) use($search) {
                $function->Where('name', 'like', '%' . $search . '%');
          });
        }

        $query = $query->whereNotNull('email');

        $query = $query->where('status',1);

        $query = $query->whereNotNull('name');

        $users = $query->orderBy('id','desc')->paginate(isset($inputData->countPerPage) ? $inputData->countPerPage : 20);

        $usersArray = [];

        foreach($users as $user){
            $userList   = [];
            $rolesList  = [];
            $rolesArray = [];

            $admin = User::where('id',$user->admin_id)->first();

            $rolesName = $user->getRoleNames()->toArray();

            $userList['id']     = $this->encryptId($user->id);
            $userList['name']   = $user->name;
            $userList['email']  = $user->email;
            $userList['number'] = $user->number;
            $userList['status'] = $user->status;

            $rolesList['key']   = ucfirst(ucwords(implode(' ',preg_split('/(?=[A-Z])/',implode(" ",$rolesName)))));
            $rolesList['value'] = implode(" ",$rolesName);

            array_push($rolesArray,$rolesList);

            $userList['role']   = $rolesArray;

            array_push($usersArray,(object) $userList);
        }

        $response['status'] = true;
        $response["message"] = ['Retrieved Successfully.'];
        $response['response']["users"] = $usersArray;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }

    public function profile(Request $request)
    {
        $userDetails = Auth()->user();

        if (isset($userDetails->id)) {
            $userDetail = [];

            $userDetail['name'] = isset(Auth()->user()->name) ? Auth()->user()->name : "";
            $userDetail['email'] = isset(Auth()->user()->email) ? Auth()->user()->email: "";
            $userDetail['number'] = isset(Auth()->user()->number) ? Auth()->user()->number : "";

            $response['status'] = true;
            $response["message"] = ['Retrieved successfully.'];
            $response['responseCode'] = 200;
            $response['response']['userDetail'] = $userDetail;

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
