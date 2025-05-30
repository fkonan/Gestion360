<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;

class ViewServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Para TODAS las vistas
        View::composer('layouts.dashboard', function ($view) {
            if (Auth::check()) {
                $user = Auth::user()->loadMissing('persona', 'ultimaSesion');
                view()->share('user', $user);
            }
        });
    }
}
