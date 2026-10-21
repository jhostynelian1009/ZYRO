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
        //
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
