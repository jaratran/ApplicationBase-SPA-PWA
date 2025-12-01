<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        //

        // Si la variable de entorno LOG_SQL_QUERIES = true registra las consultas SQL en el log definido en .ENV
        // Pero la leemos desde el config para evitar hacer consultas a env() en tiempo de ejecución
        // Y mantener coherencia con el sistema de cache de configuración de Laravel.
        if (config('app.log_sql_queries')) {
            DB::listen(function ($query) {
                // Formatear la consulta con los bindings reemplazados
                $sqlWithBindings = vsprintf(
                    str_replace(['%', '?'], ['%%', '%s'], $query->sql),
                    array_map(function ($binding) {
                        return is_numeric($binding) ? $binding : "'".addslashes($binding)."'";
                    }, $query->bindings)
                );

                // Enviar al log con retornos de carro antes y después. Busca en 'workflow\storage\logs'
                Log::info("\n\nSQL ejecutada:\n" . $sqlWithBindings . "\n\n\n");
            });
        }

	}
}
