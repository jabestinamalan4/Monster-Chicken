<?php

namespace App\Http\Controllers\Admin;

use App\Models\Feed;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\HelperTrait;
use Illuminate\Support\Facades\Validator;

class FeedController extends Controller
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
                        'name' => 'required',
                        'description' => 'required',
                    ];

        $validatedData = Validator::make((array)$inputData, $rulesArray);

        if($validatedData->fails()) {
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        if(isset($inputData->feedId))
        {
            $feed = Feed::where('id',$this->decryptId($inputData->feedId))->first();

            if(!isset($feed->id)){
                $response = ['status' => false, "message"=>"Invalid Feed Id", "responseCode" => 423];
                $encryptedResponse['data'] = $this->encryptData($response);
                return response($encryptedResponse, 400);
            }
        }
        else
        {
            $feed = new Feed;
        }

        $feed->name        = $inputData->name;
        $feed->description = $inputData->description;
        $feed->status      = 1;
        $feed->save();

        $response['status'] = true;
        $response["message"] = ['Registered Successfully.'];
        $response['responseCode'] = 200;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }

    public function feedList(Request $request)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }

        $query = Feed::query();

        if (isset($inputData->status) && $inputData->status != null && $inputData->status != "") {
            $query = $query->where('status',$inputData->status);
        }

        if (isset($inputData->search) && $inputData->search != null && $inputData->search != "") {
            $search = $inputData->search;
            $query = $query->where(function ($function) use($search) {
                $function->where('name', 'like', '%' . $search . '%');
          });
        }

        $feedCount = $query->count();

        $feeds = $query->orderBy('id','desc')->paginate(isset($inputData->countPerPage) ? $inputData->countPerPage : 20);

        $feedArray = [];

        foreach($feeds as $feed){
            $feedList  =[];

            $feedList['id']         = $this->encryptId($feed->id);
            $feedList['name']       = $feed->name;
            $feedList['description']= $feed->description;
            $feedList['status']     = $feed->status;

            array_push($feedArray,(object) $feedList);
        }

        $response['status'] = true;
        $response["message"] = ['Retrieved Successfully.'];
        $response['response']["feeds"] = $feedArray;
        $response['response']["feedCount"] = $feedCount;

         $encryptedResponse['data'] = $this->encryptData($response);
         return response($encryptedResponse, 200);
    }

    public function feedDetails(Request $request)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }

        $rulesArray = [
                        'feedId' => 'required'
                ];

        $validatedData = Validator::make((array)$inputData, $rulesArray);

        if($validatedData->fails()) {
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $feed = Feed::where('id',$this->decryptId($inputData->feedId))->first();

        if (!isset($feed->id)) {
            $response = ['status' => false, "message"=> ['Invalid Feed Id.'], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $feedList = [];

        $feedList['id']         = $this->encryptId($feed->id);
        $feedList['name']       = $feed->name;
        $feedList['description']= $feed->description;
        $feedList['status']     = $feed->status;

        $response['status'] = true;
        $response["message"] = ['Retrieved Successfully.'];
        $response['response']["feed"] = $feedList;

         $encryptedResponse['data'] = $this->encryptData($response);
         return response($encryptedResponse, 200);
    }

    public function getFeeds(Request $request)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }

        $query = Feed::query();

        if (isset($inputData->search) && $inputData->search != null && $inputData->search != "") {
            $search = $inputData->search;
            $query = $query->where(function ($function) use($search) {
                $function->where('name', 'like', '%' . $search . '%');
          });
        }

        $feedCount = $query->count();

        $feeds = $query->orderBy('id','desc')->paginate(isset($inputData->countPerPage) ? $inputData->countPerPage : 20);

        $feedArray = [];

        foreach($feeds as $feed){
            $feedList  =[];

            $feedList['id']         = $this->encryptId($feed->id);
            $feedList['name']       = $feed->name;

            array_push($feedArray,(object) $feedList);
        }

        $response['status'] = true;
        $response["message"] = ['Retrieved Successfully.'];
        $response['response']["feeds"] = $feedArray;
        $response['response']["feedCount"] = $feedCount;

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

        $rulesArray = ['feedId' => 'required'];

        $validatedData = Validator::make((array)$inputData, $rulesArray);

         if($validatedData->fails()) {
            $response = ['status' => false, "message"=> [$validatedData->errors()->first()], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        $feed = Feed::where('id',$this->decryptId($inputData->feedId))->first();

        if(!isset($feed->id)){
            $response = ['status' => false, "message"=> ["Invalid Feed Id."], "responseCode" => 422];
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 400);
        }

        if(isset($feed->status) && $feed->status==1){
            $feed->status = 0;
        }else{
            $feed->status = 1;
        }

        $feed->save();

        $response['status'] = true;
        $response["message"] = ['Status Updated Successfully.'];
        $response['responseCode'] = 200;

        $encryptedResponse['data'] = $this->encryptData($response);
        return response($encryptedResponse, 200);
    }
}
