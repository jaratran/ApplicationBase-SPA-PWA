<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any application authentication / authorization services.
     *
     * @return void
     */
    public function boot(): void
    {
        // En Laravel 12 ya no es necesario pasar el $gate como parámetro
        // $this->registerPolicies($gate);
        $this->registerPolicies();

        // Aquí puedes definir gates si las necesitas
    }
}
