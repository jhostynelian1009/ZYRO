<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Contracts\OmnichannelOrderServiceInterface::class, \App\Services\ManualOrderService::class);
        $this->app->singleton(\App\Contracts\ReturnRequestServiceInterface::class, \App\Services\ReturnRequestService::class);
        $this->app->singleton(\App\Contracts\WhatsAppLinkGeneratorInterface::class, \App\Services\WhatsAppLinkService::class);

        // F4: Arquitectura de pasarelas de pago.
        $this->app->singleton(\App\Payments\PaymentGatewayManager::class);
        $this->app->singleton(\App\Payments\Clients\PayPhoneClient::class);
        $this->app->bind(\App\Contracts\PaymentGatewayInterface::class, function ($app) {
            return $app->make(\App\Payments\PaymentGatewayManager::class)->gateway();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // En producción, forzar HTTPS como refuerzo adicional
        // al middleware TrustProxies configurado en bootstrap/app.php.
        // Esto es seguro porque no afecta el entorno local (XAMPP).
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        Gate::define('admin', function (User $user): bool {
            return $user->isAdmin();
        });
    }
}
