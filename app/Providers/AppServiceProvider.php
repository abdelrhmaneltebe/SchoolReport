<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

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
        if ($this->app->runningInConsole()) {
            @ini_set('max_execution_time', '0');
            @set_time_limit(0);
        } else {
            @ini_set('max_execution_time', '300');
        }

        Paginator::useBootstrapFive();
    }
}
