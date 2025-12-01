<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;

use App\Models\DesignParameter;
use App\Models\OperationalParameter;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Inyectar parámetros visuales en TODAS las vistas, si hay conexión válida a la base de datos
        View::composer('*', function ($view) {
            try {
                $param = DesignParameter::first();
                $view->with('designParameter', $param);
            } catch (\Throwable $e) {
                Log::warning('No se pudo cargar parámetros de diseño: ' . $e->getMessage());
                $view->with('designParameter', null); // Valor nulo evita error en vistas
            }
        });

        // Privado: Solo para vistas con usuario autenticado
        View::composer('*', function ($view) {
            if (Auth::check()) {
                try {
                    $op = OperationalParameter::first();
                    $view->with('operationalParameter', $op);
                } catch (\Throwable $e) {
                    Log::warning('No se pudo cargar parámetros operacionales: ' . $e->getMessage());
                    $view->with('operationalParameter', null);
                }
            }
        });
	}
}
