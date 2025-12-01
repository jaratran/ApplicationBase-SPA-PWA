<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        // Eliminamos el envío automático de verificación de correo.
        // Porque lo hacemos nosotros mediante notificación personalizada : customVerifyWelcomeEmail
    ];
}
