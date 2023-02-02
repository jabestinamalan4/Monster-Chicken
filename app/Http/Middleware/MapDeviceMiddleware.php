<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MapDeviceMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $inputData = $request->input;

        if (Auth::guard('api')->check() == true) {
            $user = auth('api')->user();
        }
        else{
            if (isset($inputData->deviceId)) {
                $isExist = User::where('device_id',$inputData->deviceId)->first();

                if (isset($isExist->id)) {
                    $user = $isExist;
                }
                else{
                    $user = new User;

                    $user->device_id = $inputData->deviceId;
                }
                $user->device_key = $inputData->deviceKey;

                $user->save();
            }
            else{
                $user = null;
            }
        }

        $request->merge(['user' => $user]);

        return $next($request);
    }
}
