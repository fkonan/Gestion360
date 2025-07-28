<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Hashing\SHA1HashDriver;

class HashServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
      $this->app->make('hash')->extend('sha1', function ($app, $config) {
         return new SHA1HashDriver();
      });
   }
}
