<?php

namespace App\Providers;

use App\Payments\Gateways\ZarinpalGateway;
use App\Payments\Gateways\ZibalGateway;
use App\Payments\PaymentGatewayRegistry;
use App\Services\HermesService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayRegistry::class, function () {
            $registry = new PaymentGatewayRegistry;
            $registry->register(new ZarinpalGateway);
            $registry->register(new ZibalGateway);

            $this->app->singleton(HermesService::class, function ($app) {
                $config = $app['config']['hermes'];
                return new HermesService($config['endpoint'], $config['api_key'] ?? null);
            });

        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\URL::forceScheme('https');
    }
}
