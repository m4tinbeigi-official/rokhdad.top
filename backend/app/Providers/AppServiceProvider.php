<?php

namespace App\Providers;

use App\Payments\Gateways\ZarinpalGateway;
use App\Payments\Gateways\ZibalGateway;
use App\Payments\PaymentGatewayRegistry;
use App\Models\AuditLog;
use App\Services\HermesService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
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

        // Audit trail: record authentication events. Money-movement and other
        // sensitive actions record themselves via AuditLog::record() at the
        // point of change (see App\Models\Payout).
        Event::listen(Login::class, function (Login $event): void {
            AuditLog::record(
                action: 'auth.login',
                description: 'ورود کاربر',
                properties: ['guard' => $event->guard],
                user: $event->user instanceof \App\Models\User ? $event->user : null,
            );
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if (! $event->user instanceof \App\Models\User) {
                return;
            }

            AuditLog::record(
                action: 'auth.logout',
                description: 'خروج کاربر',
                properties: ['guard' => $event->guard],
                user: $event->user,
            );
        });
    }
}
