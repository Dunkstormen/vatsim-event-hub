<?php

namespace App\Providers;

use App\Socialite\VatsimConnectProvider;
use Illuminate\Support\Facades\URL;
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
        // Behind the Cloudflare tunnel, php artisan serve only ever sees plain HTTP.
        // Force https:// so generated asset/route URLs match the public tunnel URL.
        if (str_starts_with(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Socialite::extend('vatsim', function ($app) {
            $config = $app['config']['services.vatsim'];

            /** @var VatsimConnectProvider $provider */
            $provider = Socialite::buildProvider(VatsimConnectProvider::class, $config);

            return $provider->setBaseUrl($config['base_url'] ?? 'https://auth.vatsim.net');
        });
    }
}
