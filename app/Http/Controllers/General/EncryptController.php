<?php

namespace App\Http\Controllers\General;

use Illuminate\Http\Request;
use App\Http\Traits\HelperTrait;
use App\Http\Controllers\Controller;

class EncryptController extends Controller
{
    use HelperTrait;

    public function decrypt(Request $request)
    {
        $input = $request->input;

        if (gettype($input) == 'object') {
            $response = json_encode($input);
        }
        else{
            $response = $input;
        }

        return response($response, 200);
    }

    public function encrypt(Request $request)
    {
        $data = $request->all();

        if (count($data) > 0) {
            $input = $this->encryptData($data);

            $response['success'] = true;
            $response['message'] = ['Success'];
            $response['response'] = $input;
        } else {
            $response['success'] = false;
            $response['message'] = ['Empty input field'];
        }


        return response($response, 200);
    }

    public function unauthorized(Request $request) {

        $response['status'] = false;
        $response["message"] = ["You are not authorized."];
        $encryptedResponse['data'] = $this->encryptData($response);

        return response($encryptedResponse, 401);
    }

    public function test(Request $request) {

        $response['status'] = false;
        $response["message"] = ["You are not authorized."];
        $encryptedResponse['data'] = $this->encryptData($response);

        return response($encryptedResponse, 401);
    }
}