<?php

namespace App\Http\Middleware;

use Closure;
use JWTAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

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
        try {
            $user = JWTAuth::toUser($request->header('token'));

        } catch (JWTException $e) {
            if ($e instanceof TokenExpiredException) {
                return response()->json([
                    'error' => 'token_expired',
                    'code' => $e->getStatusCode()
                ], $e->getStatusCode());
            }
            else if($e instanceof TokenInvalidException){
                return response()->json([
                    'error' => "token_invalid",
                    'code' => $e->getStatusCode()
                ], $e->getStatusCode());
            }
            else {
                return response()->json([
                    'error' => 'Token is required',
                    'code' => $e->getStatusCode(),

                ], $e->getStatusCode());
            }
        }

        return $next($user);
    }
}
