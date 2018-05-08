<?php

namespace App\Http\Middleware;

use Closure;
use Route;

class LoginOauth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    { 

        $currentAction = Route::getCurrentRoute()->getActionName();

        if($currentAction=='App\Http\Controllers\Controller@login'){
            if($request->session()->has('client')) return redirect('/');
        }else{
            if(!$request->session()->has('client')) return redirect('/login');
        }
    
        return $next($request);

    }
}
