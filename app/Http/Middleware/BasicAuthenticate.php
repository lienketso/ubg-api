<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BasicAuthenticate
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
        $logged = false;
        //check if request has authorization header
        if ($request->header('PHP_AUTH_USER', null) && $request->header('PHP_AUTH_PW', null)) {

            $username = $request->header('PHP_AUTH_USER');
            $password = $request->header('PHP_AUTH_PW');

            if ($username === Config('baseauth.username') && $password === Config('baseauth.password'))
                $logged = true;
        }
        //user not logged, request authentication
        if ($logged === false) {

            $headers = ['WWW-Authenticate' => 'Basic'];
            return response()->make('Invalid credentials.', 401, $headers);
        } else //if succesfull logged, proceed with request
            return $next($request);
    }


}
