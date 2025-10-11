<?php

namespace App\Http\Controllers\Api;

use App\Models\DesignParameter;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class DesignParameterController extends Controller
{
    /**
     * Retorna los parámetros de diseño almacenados en la tabla design_parameters
     */
    public function index(): JsonResponse
    {
        try {
            $design = DesignParameter::first();

            if (!$design) {
                \Log::warning('No existen parámetros de diseño en la base de datos.');
                return response()->json([
                    'error' => __('responses.design_parameters.not_found')
                ], 404);
            }

            return response()->json($design);
        } catch (\Throwable $e) {
            \Log::error('Error al obtener parámetros de diseño: ' . $e->getMessage());
            return response()->json([
                'error' => __('responses.design_parameters.load_error')
            ], 500);
        }
    }
}
