<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
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
        // Fuera de producción, cualquier consulta N+1 lanza una excepción
        // para detectarla en desarrollo en lugar de degradar el rendimiento.
        Model::preventLazyLoading(! $this->app->isProduction());
    }
}
