<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Models\ProgramaDiario;

class PanelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $hoy = now()->format('Y-m-d');
        // $hoy = '2025-06-05';                                                                        // 🔧 Fecha fija temporal para pruebas
        // $hoy = '2025-07-15';                                                                        // 🔧 Fecha fija temporal para pruebas
        // $hoy = '2025-07-23';                                                                        // 🔧 Fecha fija temporal para pruebas

        // 📦 Detalle de última versión de hoy
        $detallesPorVersion = ProgramaDiario::detallesPorFechaYVersion($hoy, config('constantes.VERSION_ULTIMA'));
        $ultimaVersion      = array_key_first($detallesPorVersion);
        $detalles           = $detallesPorVersion[$ultimaVersion] ?? collect();

        // 🧮 Cálculo del total de kilogramos estimados (con exclusión de cancelados)
        $totalKilosEstimados = $detalles
                                    ->filter(fn($detalle) => $detalle['estado'] !== config('constantes.ESTADO_RETIRO_CANCELADO'))
                                    ->sum('kg_estimados');

        // 📈 Datos para KPIs y gráficos
        $desde = \Carbon\Carbon::parse($hoy)->subDays(6)->toDateString();           // Últimos 7 días incluyendo hoy
        $hasta = $hoy;                                                              // Hoy

        $tonsPorSucursal    = ProgramaDiario::obtenerTonsPorSucursalHoy($hoy);
        $planVsReal         = ProgramaDiario::obtenerTonsPlanVsReal7Dias($desde, $hasta);
        $kpiHoy             = ProgramaDiario::obtenerKpiTonsHoy($hoy);
        $kpiPlan7dias       = ProgramaDiario::obtenerKpiAcumPlan7Dias($desde, $hasta);
        $kpiReal7dias       = ProgramaDiario::obtenerKpiAcumReal7Dias($desde, $hasta);

        return view('panel', [
            'fecha_vigente_programa'  => \Carbon\Carbon::parse($hoy)->format('d-m-Y'),
            'version_programa_diario' => $ultimaVersion,
            'detalles'                => $detalles,
            'totalKilosEstimados'     => $totalKilosEstimados,   // ← NUEVA VARIABLE

            // Nuevos datos estadísticos
            'desdeFecha'      => \Carbon\Carbon::parse($desde)->format('d-m-Y'),
            'hastaFecha'      => \Carbon\Carbon::parse($hasta)->format('d-m-Y'),

            'tonsPorSucursal' => $tonsPorSucursal,
            'planVsReal'      => $planVsReal,
            'kpiRcvrHoy'      => $kpiHoy,
            'kpiAcumPlan'     => $kpiPlan7dias,
            'kpiAcumReal'     => $kpiReal7dias,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function data(Request $request) {

    }
}
