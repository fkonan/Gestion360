<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AutoLogout
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) { 
            $lastActivity = Session::get('lastActivityTime'); 
            $timeout = config('session.lifetime');

            if ($lastActivity && (time() - $lastActivity > $timeout)) {
                Auth::logout(); 
                Session::flush(); 
                session()->flash('alert', ['type' => 'warning','title' => 'Sesion cerrada por inactividad']);
                return redirect()->route('login');
            }

            Session::put('lastActivityTime', time()); 
        }

        return $next($request);
    }
}
