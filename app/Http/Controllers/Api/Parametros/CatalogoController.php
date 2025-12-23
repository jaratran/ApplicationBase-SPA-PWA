<?php

namespace App\Http\Controllers\Api\Parametros;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CatalogoController extends Controller
{
    /**
     * Devuelve las constantes de catálogo usadas por el frontend SPA.
     * Fuente única de verdad: config/constantes.php
     *
     * Acceso restringido a usuarios autenticados.
     */
    public function index(): JsonResponse
    {
        return response()->json(
            config('constantes')						// config/constantes.php
        );
    }
}
