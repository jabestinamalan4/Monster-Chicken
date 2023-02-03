<?php

namespace App\Http\Controllers\Customer;

use App\Models\Cart;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use App\Http\Traits\HelperTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use HelperTrait;

    public function register(Request $request)
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
                        'password' => 'required'
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

        $user->name = $inputData->name;
        $user->email = $inputData->email;
        $user->number = $inputData->number;
        $user->password = Hash::make($inputData->password);
        $user->status = 1;

        $user->save();

        $user->assignRole('customer');

        $response['status'] = true;
        $response['responseCode'] = 200;
        $response["message"] = ['Registered successfully.'];
        $response['response']['accessToken'] = $this->getAccessToken($user);

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }

    public function login(Request $request)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }

        $inputUser = $request->user;

        $rulesArray = [
                        'userName' => 'required',
                        'googleUser' => 'required',
                        'password' => 'required'
                    ];

        if (isset($inputData->googleUser) && $inputData->googleUser == true) {
            $rulesArray['idToken'] = 'required';
        }

        $validatedData = Validator::make((array)$inputData, $rulesArray);

        if($validatedData->fails()) {
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 400];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $user = User::where('email',$inputData->userName)->where('status',1)->first();

        if ($user->hasRole('customer') != true) {
            $response['responseCode'] = 400;
            $response['status'] = false;
            $response["message"] = ['Customer login is invalid.'];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        if (isset($inputData->googleUser) && $inputData->googleUser == true) {

            $clientId = env('MOBILE_WEB_GOOGLE_CLIENT_ID');
            $idToken = $inputData->idToken;

            $client = new \Google_Client(['client_id' => $clientId]);  // Specify the CLIENT_ID of the app that accesses the backend
            $payload = $client->verifyIdToken($idToken);
            if ($payload) {
                $emailId = $payload['email'];

                if (isset($user->id) && $emailId == $user->email) {
                    if (isset($inputData->deviceKey)) {
                        $user->device_key = $inputData->deviceKey;
                        $user->save();
                    }
                    $response = ['status' => true, "message"=> ['Logged in successfully.'], "responseCode" => 200];
                    $response['response']['accessToken'] = $this->getAccessToken($user);
                    $response['response']['userId'] = $this->encryptId($user->id);
                    $encryptedResponse['data'] = $this->encryptData($response);
                    return response($encryptedResponse, 200);
                }
                else{
                    $response['responseCode'] = 400;
                    $response['status'] = false;
                    $response["message"] = ['Invalid Credentials.'];
                    $encryptedResponse['data'] = $this->encryptData($response);
                    return response($encryptedResponse, 400);
                }
            } else {
                $response['responseCode'] = 400;
                $response['status'] = false;
                $response["message"] = ['Invalid Credentials.'];
                $encryptedResponse['data'] = $this->encryptData($response);
                return response($encryptedResponse, 400);
            }
        }
        else{
            if (isset($user->id)) {

                if(Hash::check($inputData->password, $user->password)){

                    if (isset($inputData->deviceKey)) {
                        $user->device_key = $inputData->deviceKey;
                        $user->save();
                    }

                    if ($inputUser->id != $user->id) {
                        $isCartAvailable = Cart::where('user_id',$inputUser->id)->get();

                        foreach($isCartAvailable as $cart){
                            $cart->user_id = $user->id;
                            $cart->save();
                        }

                        $isListAvailable = Wishlist::where('user_id',$inputUser->id)->get();

                        foreach($isListAvailable as $list){
                            $list->user_id = $user->id;
                            $list->save();
                        }

                        $existingUser = User::where('id',$inputUser->id)->first();

                        if (isset($existingUser->id)) {
                            $existingUser->delete();
                        }
                    }

                    $response['status'] = true;
                    $response['response']['userId'] = $this->encryptId($user->id);
                    $response['responseCode'] = 200;
                    $response["message"] = ['Logged in successfully.'];
                    $response['response']['accessToken'] = $this->getAccessToken($user);
                    $encryptedResponse['data'] = $this->encryptData($response);
                    return response($encryptedResponse, 200);
                }

            }

            $response['responseCode'] = 400;
            $response['status'] = false;
            $response["message"] = ['Invalid Credentials.'];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }
    }
}