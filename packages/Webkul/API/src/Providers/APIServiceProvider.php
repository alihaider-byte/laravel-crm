<?php

namespace Webkul\API\Providers;

use Illuminate\Support\ServiceProvider;

class APIServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }
}
