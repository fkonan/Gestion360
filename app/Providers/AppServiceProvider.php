<?php

namespace App\Providers;

use App\View\Composers\MenuComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void{
        View::composer('menu', MenuComposer::class);

        // Acceso total a los usuarios super admin
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('Super Admin')) {
                return true; 
            }      
            return null; 
        }); 
        
    }
}
