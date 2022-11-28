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
        $this->app->singleton(\Loghy\SDK\Loghy::class, function () {
            return (new \Loghy\SDK\Loghy(config('loghy.site_code')))
                ->setSiteAccessToken(config('loghy.access_token'));
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
