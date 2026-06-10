<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Fix for local Laragon/PHP cURL error 77 (invalid cacert.pem path in php.ini)
        if (class_exists(\Composer\CaBundle\CaBundle::class)) {
            $path = \Composer\CaBundle\CaBundle::getBundledCaBundlePath();
            if (file_exists($path)) {
                ini_set('curl.cainfo', $path);
                ini_set('openssl.cafile', $path);
            }
        }
    }
}
