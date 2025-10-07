<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;

class Authenticate extends Middleware
{
    /**
     * Nunca redirijas en API.
     */
    protected function redirectTo($request): ?string
    {
        return null;
    }

    /**
     * Siempre responde 401 JSON en API aunque el cliente no haya enviado Accept: application/json.
     */
    protected function unauthenticated($request, array $guards)
    {
        throw new HttpResponseException(
            response()->json(['message' => 'No autenticado'], 401)
        );
    }
}