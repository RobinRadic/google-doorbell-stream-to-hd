<?php

namespace App\Providers;

use App\Google\Recorder\PathManager;
use App\Google\Recorder\RecordManager;
use App\Google\Services\DoorbellServiceExtension;
use App\Google\Services\GoogleService;
use Illuminate\Support\ServiceProvider;

class GoogleServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(PathManager::class);
        $this->app->singleton(RecordManager::class);
    }

    public function boot()
    {
        GoogleService::extend('doorbell', DoorbellServiceExtension::class);
    }
}
