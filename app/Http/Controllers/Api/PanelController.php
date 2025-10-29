<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProgramaDiario;
use Carbon\Carbon;
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
            $hoy = now()->format('Y-m-d');
            $hoy = '2025-07-23'; // Fecha donde sabes que hay programas emitidos            

            $detallesPorVersion = ProgramaDiario::detallesPorFechaYVersion($hoy, config('constantes.VERSION_ULTIMA'));
            $ultimaVersion      = array_key_first($detallesPorVersion);
            $detalles           = $detallesPorVersion[$ultimaVersion] ?? collect();

            $totalKilosEstimados = $detalles
                ->filter(fn($detalle) => $detalle['estado'] !== config('constantes.ESTADO_RETIRO_CANCELADO'))
                ->sum('kg_estimados');

            $desde = Carbon::parse($hoy)->subDays(6)->toDateString();
            $hasta = $hoy;

            $data = [
                'fecha_vigente_programa'  => Carbon::parse($hoy)->format('d-m-Y'),
                'version_programa_diario' => $ultimaVersion,
                'totalKilosEstimados'     => $totalKilosEstimados,
                'desdeFecha'              => Carbon::parse($desde)->format('d-m-Y'),
                'hastaFecha'              => Carbon::parse($hasta)->format('d-m-Y'),
                'tonsPorSucursal'         => ProgramaDiario::obtenerTonsPorSucursalHoy($hoy),
                'planVsReal'              => ProgramaDiario::obtenerTonsPlanVsReal7Dias($desde, $hasta),
                'kpiRcvrHoy'              => ProgramaDiario::obtenerKpiTonsHoy($hoy),
                'kpiAcumPlan'             => ProgramaDiario::obtenerKpiAcumPlan7Dias($desde, $hasta),
                'kpiAcumReal'             => ProgramaDiario::obtenerKpiAcumReal7Dias($desde, $hasta),
                'detalles'                => $detalles,
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
