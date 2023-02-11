<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LocationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle($request, Closure $next)
    {
        if (gettype($request->input) == 'array') {
            $inputData = (object) $request->input;
        }
        else{
            $inputData = $request->input;
        }

        if (Auth::guard('api')->check() == true) {
            $user = User::find(auth()->user()->id);

            if (isset($inputData->userLat) && $inputData->userLat != $user->user_lat) {
                $user->user_latitude = $inputData->userLat;
            }

            if (isset($inputData->userLong) && $inputData->userLong != $user->userLong) {
                $user->user_longitude = $inputData->userLong;
            }

            $user->save();
        }

        return $next($request);
    }
}
