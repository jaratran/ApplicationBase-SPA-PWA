<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Support\Facades\Auth;

class ProgramaDiario extends Model
{
    protected $table = 'programas_diarios';

    protected $fillable = [
        'fecha_programa',
        'fecha_emision',
        'version',
        'usuario_id',
        'estado',
    ];

    protected $casts = [
        'fecha_programa' => 'date',
        'fecha_emision'  => 'datetime',
        'version'        => 'integer',
        'estado'         => 'integer',
    ];

    // Relaciones
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(ProgramaDiarioDetalle::class, 'programa_id', 'id');
    }

    /**
     * Obtiene los detalles emitidos para una fecha dada del programa diario y versión(es) especifica(s).
     *
     * @param   string    $fecha                                Fecha en formato 'Y-m-d'
     * @param   int       $version                              -1 para última versión, 0 para todas, o número de versión específico
     * @return  array   <int, \Illuminate\Support\Collection>   Array asociativo con versión como clave y colección de detalles como valor
     *
     * @throws \InvalidArgumentException Si la fecha no tiene el formato esperado
     */
    public static function detallesPorFechaYVersion(string $fecha, int $version = 0): array
    {
        // BLOQUE 1 – Validar formato y parsear $fecha        
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            throw new \InvalidArgumentException("Formato de fecha inválido. Se esperaba YYYY-MM-DD.");
        }
        $fechaCarbon = \Carbon\Carbon::parse($fecha);

        // BLOQUE 2 – Obtener los detalles como en el controlador
        $rolSesion     = Auth::user()?->rol_id;   // null en consola (ejecucución automática), entero en web (el que corresponde al usuario de la sesión)
        $esSolicitante = in_array($rolSesion, [
                                                config('constantes.ROL_SOLICITANTE_PLANTA'),
                                                config('constantes.ROL_SOLICITANTE_PRODUCTOR')
                                            ]);

        $detalles = ProgramaDiarioDetalle::whereHas('programa', function ($query) use ($fechaCarbon) {
                                                                                                        $query->whereDate('fecha_programa', $fechaCarbon);
                                                                                                    })
                                                ->when($esSolicitante, function ($query) {
                                                                                            $query->whereHas('retiro.solicitud', function ($q) {
                                                                                                                                                    $q->where('usuario_id', Auth::id());
                                                                                                                                                });
                                                                                        })
                                                ->with([
                                                    'programa',
                                                    'retiro.solicitud.maquila.empresa',
                                                    'retiro.solicitud.maquila.sucursal.comuna',
                                                    'retiro.tipoRetiro',
                                                    'retiro.estado',
                                                    'retiro.planificacion.camion',
                                                    'retiro.planificacion.conductor:id,nombre,apellido,rut',    // Agregado para satisfacer correo a Transportista y Telegrama a Conductor
                                                    'retiro.planificacion.tipoMateriaPrima',
                                                    'retiro.planificacion.especie',
                                                    'camion.empresa',
                                                    'tipoRetiro',
                                                    'especie',
                                                    'producto',
                                                ])
                                                ->get()
                                                ->groupBy(fn($detalle) => $detalle->programa->version)
                                                ->sortKeysDesc();

        // BLOQUE 3 – Transformar a formato amigable (Blade-ready)
        $detallesPorVersionProcesados = [];

        foreach ($detalles as $versionKey => $items) {
            $detallesPorVersionProcesados[$versionKey] = $items->map(function ($detalle) {
                    $retiro = $detalle->retiro;
                    $plan   = optional($retiro)->planificacion;

                    return [
                                // Estos campo se sacan del Detalle del Programa Diario
                                'estado'                   => $detalle->estado,
                                'novedad'                  => $detalle->novedad,

                                // Estos campos se pueden sacar desde retiro, solicitud, maquila (NO CAMBIAN ENTRE EMISIONES)
                                'sucursal'                 => optional($retiro->solicitud->maquila->sucursal)->nombre_sucursal,
                                'procedencia'              => optional($retiro->solicitud->maquila->sucursal->comuna)->nombre,
                                'proveedor'                => optional($retiro->solicitud->maquila->empresa)->razon_social,

                                // Estos campo se sacan del Detalle del Programa Diario
                                'fecha_hora_retiro'        => optional($detalle)->fecha_hora_retiro->format('d-m-Y H:i'),
                                'camion'                   => optional($detalle->camion)->patente,
                                'tipo_retiro'              => optional($detalle->tipoRetiro)->nombre,
                                'duracion_viaje'           => optional($detalle)->duracion_viaje,
                                'eta'                      => optional($detalle)->eta->format('H:i d-m-Y'),
                                'kg_estimados'             => optional($detalle)->kilogramos_estimados ?? 0,
                                'producto'                 => optional($detalle->producto)->nombre,
                                'especie'                  => optional($detalle->especie)->nombre,
                                'bins'                     => optional($detalle)->bins,

                                // Estos campos son para armar Correo a Transportistas
                                'detalle_programa_id'      => $detalle->id,
                                'transportista_id'         => optional($detalle->camion?->empresa)?->id,
                                'numero_retiro'            => $detalle->retiro_id,
                                'patente_rampla'           => optional($plan)?->patente_rampla ?? '—',                  // Este campo no está en la tabla Detalle de Programa Diario
                                'tipo_camion'              => optional($detalle->camion?->tipoCamion)?->nombre,
                                'nombre_chofer'            => optional($plan->conductor)?->nombre_completo ?? '—',      // Este campo no está en la tabla Detalle de Programa Diario
                                'rut_chofer'               => optional($plan->conductor)?->rut,                         // Este campo no está en la tabla Detalle de Programa Diario
                                
                                // Estos campos son para armar Telegram a Conductores
                                'conductor_id'             => optional($plan->conductor)?->id,                          // Este campo no está en la tabla Detalle de Programa Diario
                            ];
                });
            }

        // BLOQUE 4 — Decidir qué retornar según $version
        switch ($version) {
            case config('constantes.VERSION_TODAS'):
                return $detallesPorVersionProcesados;                                                               // 🔁 Todas las versiones agrupadas

            case config('constantes.VERSION_ULTIMA'):
                $ultimaClave = collect($detallesPorVersionProcesados)->keys()->first();
                return $ultimaClave ? [ $ultimaClave => $detallesPorVersionProcesados[$ultimaClave] ] : [];         // 📌 Última versión como array [N => Collection]

            default:
                return isset($detallesPorVersionProcesados[$version])                                               // 🔎 Versión específica como array [N => Collection]
                        ? [ $version => $detallesPorVersionProcesados[$version] ]
                        : [];
        }
    }

    /**
     * Obtener desde la última versión del programa diario de una fecha dada ($fecha).
     * La suma de kilos estimados (kilogramos_estimados), agrupados por sucursal.
     * Estructura lista para ser usada por Chart.js
     */
    public static function obtenerTonsPorSucursalHoy(string $fecha): array
    {
        // BLOQUE 1 — Firma del método y validación
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            throw new \InvalidArgumentException("Formato de fecha inválido. Se esperaba YYYY-MM-DD.");
        }

        $fechaCarbon = \Carbon\Carbon::parse($fecha);

        // BLOQUE 2 — Obtener última versión para esa fecha
        $ultimaVersion = self::whereDate('fecha_programa', $fechaCarbon)->max('version');

        if (!$ultimaVersion) {
            return ['labels' => [], 'data' => []]; // No hay programa
        }

        // BLOQUE 3 — Obtener kilos por sucursal
        $rolSesion     = Auth::user()->rol_id;
        $esSolicitante = in_array($rolSesion, [
                                                    config('constantes.ROL_SOLICITANTE_PLANTA'),
                                                    config('constantes.ROL_SOLICITANTE_PRODUCTOR'),
                                                ]);

        $detalles = ProgramaDiarioDetalle::whereHas('programa', function ($q) use ($fechaCarbon, $ultimaVersion) {
                                                                                                                    $q->whereDate('fecha_programa', $fechaCarbon)
                                                                                                                    ->where('version', $ultimaVersion);
                                                                                                                })
                                            ->when($esSolicitante, function ($query) {
                                                                                        $query->whereHas('retiro.solicitud', function ($q) {
                                                                                            $q->where('usuario_id', Auth::id());
                                                                                    });
                                            })
                                            ->with('retiro.solicitud.maquila.sucursal')
                                            ->get();

        // BLOQUE 4 — Agrupar y sumar por sucursal
        $agrupado = $detalles->groupBy(fn($detalle) =>
                                                        optional($detalle->retiro->solicitud->maquila->sucursal)->nombre_sucursal ?? 'SIN SUCURSAL'
                                        )->map(function ($items) {
                                                                    return $items->sum(fn($item) => optional($item->retiro)->kilogramos_estimados ?? 0);
                                                                });

        // BLOQUE 5 — Preparar estructura final
        return [
            'labels' => $agrupado->keys()->toArray(),
            'data'   => $agrupado->values()->toArray(),
        ];
    }

    /**
     * Obtener para los últimos 7 días (incluido $hastaFecha):
     * Para cada día: Toneladas planificadas (versión 1). Toneladas reales (versión última).
     * Devolver datos en estructura lista para Chart.js:
     */
    public static function obtenerTonsPlanVsReal7Dias(string $desdeFecha, string $hastaFecha): array
    {
        // BLOQUE 1 — Firma y validación inicial
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hastaFecha)) {
            throw new \InvalidArgumentException("Formato de fecha inválido. Se esperaba YYYY-MM-DD.");
        }

        $desde = \Carbon\Carbon::parse($desdeFecha)->startOfDay();              // aaaa-mm-dd 00:00:00
        $hasta = \Carbon\Carbon::parse($hastaFecha)->endOfDay();                // aaaa-mm-dd 23:59:59

        // BLOQUE 2 — Obtener todos los programas en ese rango
        $programas = self::whereBetween('fecha_programa', [$desde, $hasta])->get();

        if ($programas->isEmpty()) {
            return ['labels' => [], 'plan' => [], 'real' => []];
        }

        // BLOQUE 3 — Agrupar por fecha (Y-m-d)
        $agrupado = $programas->groupBy(fn($p) => $p->fecha_programa->format('Y-m-d'));

        // BLOQUE 4 — Procesar cada día
        $labels = [];
        $plan   = [];
        $real   = [];

        $rolSesion     = Auth::user()->rol_id;
        $esSolicitante = in_array($rolSesion, [
                                                config('constantes.ROL_SOLICITANTE_PLANTA'),
                                                config('constantes.ROL_SOLICITANTE_PRODUCTOR'),
                                            ]);

        $planKilos = 0;
        $realKilos = 0;

        foreach ($agrupado as $fechaStr => $programasDelDia) {
            $fechaCarbon = \Carbon\Carbon::parse($fechaStr);

            // Versión 1 (planificada)
            $version1 = $programasDelDia->firstWhere('version', 1);
            if ($version1) {
                $query = $version1->detalles()->with('retiro');

                if ($esSolicitante) {
                    $query->whereHas('retiro.solicitud', function ($q) {
                                                                            $q->where('usuario_id', Auth::id());
                                                                        });
                }

                $planKilos = $query->get()
                                    ->sum(fn($d) => optional($d->retiro)->kilogramos_estimados ?? 0);
            }

            // $planKilos = $version1
            //     ? $version1->detalles()
            //             ->with('retiro')
            //             ->get()
            //             ->sum(fn($d) => optional($d->retiro)->kilogramos_estimados ?? 0)
            //     : 0;

            // Última versión (real consolidada)
            $ultimaVersion = $programasDelDia->sortByDesc('version')->first();
            if ($ultimaVersion) {
                $query = $ultimaVersion->detalles()->with('retiro');

                if ($esSolicitante) {
                    $query->whereHas('retiro.solicitud', function ($q) {
                                                                            $q->where('usuario_id', Auth::id());
                                                                        });
                }

                $realKilos = $query->get()
                                    ->sum(fn($d) => optional($d->retiro)->kilogramos_estimados ?? 0);
            }

            // $realKilos = $ultimaVersion
            //     ? $ultimaVersion->detalles()
            //                     ->with('retiro')
            //                     ->get()
            //                     ->sum(fn($d) => optional($d->retiro)->kilogramos_estimados ?? 0)
            //     : 0;

            $labels[] = $fechaCarbon->format('d-m');
            $plan[]   = round($planKilos);
            $real[]   = round($realKilos);
        }
        
        // BLOQUE 5 — Retornar estructura final
        return [
            'labels' => $labels,
            'plan'   => $plan,
            'real'   => $real,
        ];
    }

    /**
     * Obtener la suma total de kilogramos estimados en la última versión del programa diario para la fecha $fecha.
     * Este dato alimenta el KPI: 💡 "Tons a Recibir ETA Hoy"
     */
    public static function obtenerKpiTonsHoy(string $fecha): int
    {
        // BLOQUE 1 — Firma y validación
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            throw new \InvalidArgumentException("Formato de fecha inválido. Se esperaba YYYY-MM-DD.");
        }

        $fechaCarbon = \Carbon\Carbon::parse($fecha);

        // BLOQUE 2 — Obtener la última versión de ese día
        $ultimaVersion = self::whereDate('fecha_programa', $fechaCarbon)->max('version');

        if (!$ultimaVersion) {
            return 0;
        }

        // BLOQUE 3 — Sumar kilos en esa versión
        $programa = self::whereDate('fecha_programa', $fechaCarbon)
                        ->where('version', $ultimaVersion)
                        ->first();

        if (!$programa) {
            return 0;
        }

        $rolSesion     = Auth::user()->rol_id;
        $esSolicitante = in_array($rolSesion, [
                                                config('constantes.ROL_SOLICITANTE_PLANTA'),
                                                config('constantes.ROL_SOLICITANTE_PRODUCTOR'),
                                            ]);

        $query = $programa->detalles()->with('retiro');

        if ($esSolicitante) {
            $query->whereHas('retiro.solicitud', function ($q) {
                                                                    $q->where('usuario_id', Auth::id());
                                                                });
        }

        return $query->get()
                        ->sum(fn($detalle) => optional($detalle->retiro)->kilogramos_estimados ?? 0);

        // return $programa->detalles()
        //                 ->with('retiro')
        //                 ->get()
        //                 ->sum(fn($detalle) => optional($detalle->retiro)->kilogramos_estimados ?? 0);
    }

    /**
     * Obtener la suma acumulada de kilogramos estimados en la versión 1 (planificada) de los últimos 7 días, incluida la fecha $hastaFecha.
     * Este valor alimenta el KPI: 💡 "Acum Plan Ults 7 días"
     */
    public static function obtenerKpiAcumPlan7Dias(string $desdeFecha, string $hastaFecha): int
    {
        // BLOQUE 1 — Firma y validación
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hastaFecha)) {
            throw new \InvalidArgumentException("Formato de fecha inválido. Se esperaba YYYY-MM-DD.");
        }

        $desde = \Carbon\Carbon::parse($desdeFecha)->startOfDay();              // aaaa-mm-dd 00:00:00
        $hasta = \Carbon\Carbon::parse($hastaFecha)->endOfDay();                // aaaa-mm-dd 23:59:59

        // BLOQUE 2 — Obtener todos los programas versión 1 en ese rango
        $programas = self::whereBetween('fecha_programa', [$desde, $hasta])
                        ->where('version', 1)
                        ->get();

        if ($programas->isEmpty()) {
            return 0;
        }

        // BLOQUE 3 — Sumar kilogramos de los detalles
        $rolSesion     = Auth::user()->rol_id;
        $esSolicitante = in_array($rolSesion, [
                                                config('constantes.ROL_SOLICITANTE_PLANTA'),
                                                config('constantes.ROL_SOLICITANTE_PRODUCTOR'),
                                            ]);

        $suma = 0;

        foreach ($programas as $programa) {
            $query = $programa->detalles()->with('retiro');

            if ($esSolicitante) {
                $query->whereHas('retiro.solicitud', function ($q) {
                                                                        $q->where('usuario_id', Auth::id());
                                                                    });
            }

            $suma += $query->get()
                        ->sum(fn($detalle) => optional($detalle->retiro)->kilogramos_estimados ?? 0);
        }

        return $suma;
    }

    /**
     * Obtener la suma acumulada de kilogramos estimados en la última versión de los últimos 7 días, incluida la fecha $hastaFecha.
     * Este dato alimenta el KPI: 💡 "Acum Real Ults 7 días"
     */
    public static function obtenerKpiAcumReal7Dias(string $desdeFecha, string $hastaFecha): int
    {
        // BLOQUE 1 — Firma y validación
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hastaFecha)) {
            throw new \InvalidArgumentException("Formato de fecha inválido. Se esperaba YYYY-MM-DD.");
        }

        $desde = \Carbon\Carbon::parse($desdeFecha)->startOfDay();              // aaaa-mm-dd 00:00:00
        $hasta = \Carbon\Carbon::parse($hastaFecha)->endOfDay();                // aaaa-mm-dd 23:59:59

        // BLOQUE 2 — Obtener todos los programas en ese rango
        $programas = self::whereBetween('fecha_programa', [$desde, $hasta])->get();

        if ($programas->isEmpty()) {
            return 0;
        }

        // BLOQUE 3 — Agrupar por fecha y obtener última versión por día
        $agrupado = $programas->groupBy(fn($p) => $p->fecha_programa->format('Y-m-d'));

        $rolSesion     = Auth::user()->rol_id;
        $esSolicitante = in_array($rolSesion, [
                                                    config('constantes.ROL_SOLICITANTE_PLANTA'),
                                                    config('constantes.ROL_SOLICITANTE_PRODUCTOR'),
                                                ]);

        $suma = 0;

        foreach ($agrupado as $programasDelDia) {
            $programaUltimaVersion = $programasDelDia->sortByDesc('version')->first();

            if ($programaUltimaVersion) {
                $query = $programaUltimaVersion->detalles()->with('retiro');

                if ($esSolicitante) {
                    $query->whereHas('retiro.solicitud', function ($q) {
                                                                            $q->where('usuario_id', Auth::id());
                                                                        });
                }

                $suma += $query->get()
                            ->sum(fn($d) => optional($d->retiro)->kilogramos_estimados ?? 0);
            }
        }

        return $suma;
    }
}
