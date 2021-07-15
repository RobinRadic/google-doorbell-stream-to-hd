<?php

namespace App\Providers;

use App\Google\Services\DoorbellServiceExtension;
use App\Google\Services\GoogleService;
use Illuminate\Support\ServiceProvider;

class GoogleServiceProvider extends ServiceProvider
{
    public function register()
    {
    }

    public function boot()
    {
        GoogleService::extend('doorbell', DoorbellServiceExtension::class);
    }
}
