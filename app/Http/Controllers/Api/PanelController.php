<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PanelController extends Controller
{
    /**
     * Devuelve los datos del panel de control (Dashboard)
     * para el usuario autenticado (vía API Sanctum).
     */
    public function datos(Request $request)
    {
        try {
            $hasta = now();
            $desde = $hasta->copy()->subDays(6);

            $data = [
                'periodo' => [
                    'desde' => $desde->format('d-m-Y'),
                    'hasta' => $hasta->format('d-m-Y'),
                ],
                'kpiPrincipal'  => 0,
                'kpiSecundario' => 0,
                'kpiTerciario'  => 0,
                'detalles'      => [],
            ];

            return response()->json([
                'status'  => 'success',
                'message' => __('responses.panel.load_success'),
                'data'    => $data,
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Error al obtener datos del Panel API', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => __('responses.panel.load_error'),
            ], 500);
        }
    }
}
