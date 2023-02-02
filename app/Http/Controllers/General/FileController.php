<?php

namespace App\Http\Controllers\General;

use Illuminate\Http\Request;
use App\Http\Traits\HelperTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FileController extends Controller
{
    use HelperTrait;

    public function upload(Request $request)
    {
        $data = $request->all();
        $checkImg = Validator::make($data, [
                        'uploadFile' => 'required'
                    ]);

        if($checkImg->fails()) {
            $response = ['status' => false, "message"=> [$checkImg->errors()->first()]];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        if($request->hasFile('uploadFile')) {
            //banner image
            //get filename with extension
            $fileName = $request->file('uploadFile')->getClientOriginalName();
            //get filename without extension
            $fileName = pathinfo($fileName, PATHINFO_FILENAME);
            //get file extension
            $extension = $request->file('uploadFile')->getClientOriginalExtension();
            //filename to store
            $fileName = $fileName.'_'.time().'.'.$extension;
            $fileName = str_replace(" ", "_", $fileName);
            //Upload File to s3
            Storage::disk('public')->put('document/'.$fileName, fopen($request->file('uploadFile'), 'r+'), 'public');
        }

        $response['status'] = true;
        $response['response']['url'] = $fileName;
        $response['response']['previewUrl'] = Storage::disk('public')->url('document/'.$fileName);
        $response['responseCode'] = 200;
        $response["message"] = ['Uploaded successfully.'];

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }
}