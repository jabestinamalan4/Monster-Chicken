<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\HelperTrait;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{

    use HelperTrait;

    public function addUser(Request $request)
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
            $user = User::where('id',$inputUser->id)->first();
        }
        else{
            $user = new User;
        }
        $password = $this->generatePassword();
        $user->name = $inputData->name;
        $user->email = $inputData->email;
        $user->number = $inputData->number;
        $user->password = Hash::make($password);
        $user->password_raw = $password;
        $user->status = 1;

        $user->save();

        $user->assignRole('franchise');

        if(isset($user->id) && ($user->id!=null || $user->id!="")){
            dispatch(new SendEmailJob($user,'add_user'));
        }


        $response['status'] = true;
        $response['responseCode'] = 200;
        $response["message"] = ['Registered successfully.'];
        $response['response']['accessToken'] = $this->getAccessToken($user);

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }
}
