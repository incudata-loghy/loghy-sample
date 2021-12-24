<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class LoghyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(\Loghy\SDK\Loghy::class, function() {
            return new \Loghy\SDK\Loghy(
                config('loghy.api_key'),
                config('loghy.site_code'),
            );
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
