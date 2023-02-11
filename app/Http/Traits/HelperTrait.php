<?php

namespace App\Http\Traits;
use Carbon\Carbon;
use App\Models\User;
use App\Models\OfferType;
use App\Models\FormSetting;
use Illuminate\Support\Str;
use App\Models\TableSetting;
use App\Models\OfferCategory;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

trait HelperTrait {

    public function encryptData($content)
    {
        $key=env('ENCRYPTION_KEY');
		$iv=env('ENCRYPTION_IV');
        if (gettype($content) == 'string') {
            $encrypted = base64_encode(openssl_encrypt($content, 'AES-256-CBC', $key, OPENSSL_RAW_DATA,$iv));
        }
        else{
            if (isset(auth()->user()->id)) {
                $userRoles = auth()->user()->getRoleNames()->toArray();
                $content['role'] = $userRoles[0];
            }
            $encrypted = base64_encode(openssl_encrypt(json_encode($content), 'AES-256-CBC', $key, OPENSSL_RAW_DATA,$iv));
        }

        return $encrypted;

    }

    public function encryptId($id)
    {
        $key=env('ENCRYPTION_KEY');
		$iv=env('ENCRYPTION_IV');
        $encrypted = base64_encode(openssl_encrypt($id, 'AES-256-CBC', $key, OPENSSL_RAW_DATA,$iv));

        $rectified = str_replace(array('+', '/'), array('-', '_'), $encrypted);

        return $rectified;
    }

    public function decryptId($input)
    {
        $key=env('ENCRYPTION_KEY');
		$iv=env('ENCRYPTION_IV');

        $rectified = str_replace(array('-', '_'), array('+', '/'), $input);

        $decrypted=openssl_decrypt(base64_decode($rectified),'AES-256-CBC',$key,OPENSSL_RAW_DATA,$iv);

        return $decrypted;
    }

    public function isRoleExist($role_name){
        return Count(Role::where("name",$role_name)->get()) > 0;
    }

    public function storeOtpRecord($result,$user_id,$type = 1)
    {
        $data = json_decode($result);

        $user = User::find($user_id);
        if ($data->status_code == 200) {

            if (isset($user->id)) {
                $user->stytch_id = $data->phone_id;
                $user->otp_type = $type;
                $user->save();
            }
            else{
                $response = ['status' => false, "message"=> ['This User does not have privilege'],"responseCode" => 422];
                $encryptedResponse['data'] = $this->encryptData($response);
                return $encryptedResponse;
            }
        }
        else{
            if (isset($user->id)) {
                $user->stytch_id = $data->request_id;
                $user->otp_type = $type;
                $user->status = 0;
                $user->save();
            }
            else{
                $response = ['status' => false, "message"=> ['This User does not have privilege'],"responseCode" => 422];
                $encryptedResponse['data'] = $this->encryptData($response);
                return $encryptedResponse;
            }
        }
    }

    public function getAccessToken($user)
    {
        $accessToken = $user->createToken('authToken')->accessToken;
        return $accessToken;
    }


    public function generatePassword()
    {
        // $string="0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()-";

        // for ($i=0; $i != -1; $i++) {

        //     $pwd=substr(str_shuffle($string),0,12);
        //     if (preg_match('/^(?=.*\d)(?=.*[A-Za-z])[0-9A-Za-z!@#$%]{8,12}$/',$pwd)) {
        //         return $pwd;
        //     }
        // }

        $digits    = array_flip(range('0', '9'));
        $lowercase = array_flip(range('a', 'z'));
        $uppercase = array_flip(range('A', 'Z'));
        $special   = array_flip(str_split('#?!@$%^&*-'));
        $combined  = array_merge($digits, $lowercase, $uppercase, $special);

        $password  = str_shuffle(array_rand($digits) .
                                array_rand($lowercase) .
                                array_rand($uppercase) .
                                array_rand($special) .
                                implode(array_rand($combined, rand(4, 8))));

        return $password;
    }

    public function getNumberValue($amount)
    {
        $array = explode('.',$amount);
		$roundAmount = $array[0];

		if (isset($array[1])) {
			$decimal = $array[1];
		}
		else{
			$decimal = 00;
		}
        $len = strlen($roundAmount);
        $m = '';
        $roundAmount = strrev($roundAmount);
        for($i=0;$i<$len;$i++){
            if(( $i==3 || ($i>3 && ($i-1)%2==0) )&& $i!=$len){
                $m .=',';
            }
            $m .=$roundAmount[$i];
        }
        return strrev($m).".".$decimal;
    }

    public function shortCode($message, $data = null)
    {
        if (isset($data->event) && $data->event == "add_user") {
            $user = $data;
        }

        if (isset($data->event) && $data->event == "forget_password") {
            $user = $data;

            if (isset($user->otp) && $user->otp != null) {
                $otp = $user->otp;
            }
        }

        if (isset($data->event) && ($data->event == "loanCreated" || $data->event == 'leadAssigned' || $data->event == 'approvalRequest' || $data->event == 'rejectionRequest' || $data->event == 'requestApproved' || $data->event == 'requestRejected' || $data->event == 'leadDisbursed' || $data->event == 'leadRejected')) {

            $applicantName = isset($data->loan->personal->name) ? $data->loan->personal->name : "";
            $applicantNumber = isset($data->loan->permanent_id) ? $data->loan->permanent_id : "";

            if (isset($data->loan->type) && $data->loan->type == 1) {
                $isExist = OfferCategory::where('type_id',$data->loan->type)->where('id',$data->loan->category)->first();

                if (isset($isExist->id)) {
                    $loanType = $isExist->category;
                }
                else{
                    $loanType = "";
                }
            }
            else{
                $loanType = "Credit Card";
            }
            $appliedOn = isset($data->loan->applied_on) ? Carbon::parse($data->loan->applied_on)->diffInMonths() : "";
        }

        if (isset($data->event) && ($data->event == "changePasswordRequest" || $data->event == "dsaRegistration")) {
            $user = $data->user;
        }

        if (isset($data->event) && ($data->event == "ticketAssign" || $data->event == "closingRequest" || $data->event == "rejectingRequest" || $data->event == "ticketClose" || $data->event == "ticketClose")) {

            if (isset($data->ticket->request_from) && $data->ticket->request_from == 1) {
                $isExist = User::where('type_id',$data->ticket->request_from)->first();

                if (isset($isExist->id)) {
                    $customerName = $isExist->name;
                }
                else{
                    $customerName = "";
                }
            }
            else{
                $customerName = "";
            }

            $ticketDate = date('d-m-Y');
        }

        $list=[];
        // SHORTCODE REPLACE MENT
        $list=[
                '[USER_NAME]'            =>   isset($user->name) ? $user->name : '',
                '[LOGIN_NAME]'           =>   isset($user->email) ? $user->email : '',
                '[LOGIN_PASS]'           =>   isset($user->password_raw) ? $user->password_raw : '',
                '[OTP]'                  =>   isset($otp) ? $otp : '',
                '[APPLICANT_NAME]'       =>   isset($applicantName) ? $applicantName : '',
                '[APPLICANT_NUMBER]'     =>   isset($applicantNumber) ? $applicantNumber : '',
                '[LOAN_TYPE]'            =>   isset($loanType) ? $loanType : '',
                '[APPLIED_ON]'           =>   isset($appliedOn) ? $appliedOn : '',
                '[CUSTOMER_NAME]'        =>   isset($customerName) ? $customerName : '',
                '[TICKET_DATE]'          =>   isset($ticketDate) ? $ticketDate : '',
              ];

        $find       = array_keys($list);
        $replace    = array_values($list);

        $new_string = str_ireplace($find, $replace, $message);

        $str_pos_left= strpos( $new_string, '[' );
        $str_pos_right = strpos( $new_string, ']' );

        $sub_str = substr($new_string, $str_pos_left , $str_pos_right);

        $message = str_replace($sub_str, '', $new_string);

        return $message;
    }
}
