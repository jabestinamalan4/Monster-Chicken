<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            if ($request->route()->getPrefix() === 'api' || $request->route()->getPrefix() === 'api/v1.0' || $request->route()->getPrefix() === 'api/admin') {
                return route('unauthenticated');
            }
            else{
                return route('login');
            }
        }
    }
}