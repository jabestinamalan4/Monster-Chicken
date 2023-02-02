<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\Traits\HelperTrait;

class DecryptMiddleware
{
    use HelperTrait;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (isset(auth()->user()->id) && auth()->user()->status == 0) {
            $response = ['status' => false, "message"=> ['Please verify OTP to login.'], "responseCode" => 401];
            $response['response']['isActive'] = auth()->user()->status;
            $response['response']['userId'] = $this->encryptId(auth()->user()->id);
            $encryptedResponse['data'] = $this->encryptData($response);
            return response($encryptedResponse, 401);
        }

        $key=env('ENCRYPTION_KEY');
        $iv=env('ENCRYPTION_IV');

        if (isset($request->input)) {
            $value=openssl_decrypt(base64_decode($request->input),'AES-256-CBC',$key,OPENSSL_RAW_DATA,$iv);
            if(json_decode($value) != null){
                $input = json_decode($value);
                if (gettype($input) == "object") {
                    $input = (array) $input;
                }
            }
            else{
                $input = $value;
            }

            if($request->has('input')) {
                $request->merge(['input' => $input]);
            }

            if (isset($input) && gettype($input) == "array" && isset($input['page'])) {
                $request->merge(['page' => (int) $input['page']]);
            }
            elseif(isset($input) && gettype($input) == "object" && isset($input->page))
            {
                $request->merge(['page' => $input->page]);
            }
        }

        return $next($request);
    }
}