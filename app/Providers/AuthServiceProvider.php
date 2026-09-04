<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

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

        Gate::define('access-administration', fn (User $user): bool => $this->isAdministrator($user) || $this->isCoordinator($user));
        Gate::define('manage-parameters', fn (User $user): bool => $this->isAdministrator($user));
        Gate::define('manage-catalogs', fn (User $user): bool => $this->isAdministrator($user));
        Gate::define('manage-organization', fn (User $user): bool => $this->isAdministrator($user) || $this->isCoordinator($user));
        Gate::define('manage-users', fn (User $user): bool => $this->isAdministrator($user) || $this->isCoordinator($user));

        // Aquí puedes definir gates si las necesitas
    }

    private function isAdministrator(User $user): bool
    {
        return $user->rol_id === config('constantes.ROL_ADMINISTRADOR_IT');
    }

    private function isCoordinator(User $user): bool
    {
        return $user->rol_id === config('constantes.ROL_COORDINADOR');
    }
}
