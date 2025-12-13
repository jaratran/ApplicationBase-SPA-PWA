<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Middleware\TrustProxies as Middleware;

/**
 * TRUST PROXIES CONFIGURATION
 * ---------------------------------------------------------------------------------
 * Este middleware se incorpora para que Laravel interprete correctamente
 * el esquema HTTPS cuando la aplicación se encuentra detrás de un proxy inverso.
 *
 * Contexto arquitectónico:
 * - En desarrollo y pre-producción, la aplicación se sirve mediante dos Apaches:
 *      * Apache FRONT (80/443) maneja SSL y actúa como reverse proxy
 *      * Apache BACK (8080) sirve Laravel vía HTTP puro
 * - En este escenario, PHP recibe las peticiones como HTTP,
 *   aunque el navegador accede vía HTTPS.
 *
 * Problema que resuelve:
 * - Sin esta configuración, Laravel genera URLs con esquema HTTP
 *   (assets, @vite, redirects, etc.), provocando errores de Mixed Content
 *   en navegadores modernos (especialmente críticos en SPA/PWA).
 *
 * Solución:
 * - Se confía explícitamente en los headers X-Forwarded-* enviados por el proxy
 *   (X-Forwarded-Proto, Host, Port, For), permitiendo que Laravel detecte HTTPS
 *   correctamente mediante Request::isSecure().
 *
 * Notas:
 * - En ambientes con un solo Apache (DESA / PROD), esta configuración es inocua.
 * - El backend solo confía en proxies locales (127.0.0.1 / ::1),
 *   coherente con el esquema donde el proxy y Laravel residen en la misma máquina.
 *
 * Implementado para el proyecto "Calidad" como evolución del esquema usado en EcoRuta.
 * ---------------------------------------------------------------------------------
 */

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array|string|null
     */
    protected $proxies = ['127.0.0.1', '::1'];

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO;
}
