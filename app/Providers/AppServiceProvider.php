<?php

namespace App\Providers;

use App\Socialite\VatsimConnectProvider;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;

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
        Socialite::extend('vatsim', function ($app) {
            $config = $app['config']['services.vatsim'];

            /** @var VatsimConnectProvider $provider */
            $provider = Socialite::buildProvider(VatsimConnectProvider::class, $config);

            return $provider->setBaseUrl($config['base_url'] ?? 'https://auth.vatsim.net');
        });
    }
}
