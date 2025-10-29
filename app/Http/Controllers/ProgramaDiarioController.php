<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

use Carbon\Carbon;

use App\Models\Planificacion;
use App\Models\ProgramaDiario;
use App\Models\ProgramaDiarioDetalle;
use App\Models\TelegramLink;
use App\Models\Retiro;
use App\Models\User;
use App\Models\Empresa;
use App\Models\Conductor;

use App\Helpers\CorreoHelper;

class ProgramaDiarioController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Paso 1: Obtener todas las fechas únicas que tienen al menos una versión emitida
        $fechasPrograma = ProgramaDiario::distinct()
                            ->orderByDesc('fecha_programa')
                            ->pluck('fecha_programa');

        // Paso 2: Recorrer cada fecha y obtener versión 0 y última versión (con relaciones)
        $datosProgramas = $fechasPrograma->map(function ($fecha) {
            
            // Obtener versión 0 (base de comparación)
            $version0 = ProgramaDiario::where('fecha_programa', $fecha)
                        ->where('version', 1)
                        ->with(['detalles.retiro', 'usuario'])
                        ->first();

            // Obtener última versión (la más reciente)
            $ultimaVersion = ProgramaDiario::where('fecha_programa', $fecha)
                                ->orderByDesc('version')
                                ->with(['detalles.retiro', 'usuario'])
                                ->first();

            // Paso 3: Contar versiones y registrar primera y última emisión
                // Contar cantidad total de versiones para esta fecha
                $cantidadVersiones = ProgramaDiario::where('fecha_programa', $fecha)->count();

                // Obtener fecha y hora de la primera versión (mínimo created_at)
                $primeraEmision = ProgramaDiario::where('fecha_programa', $fecha)->min('created_at');

                // Obtener fecha y hora de la última versión (máximo created_at)
                $ultimaEmision = ProgramaDiario::where('fecha_programa', $fecha)->max('created_at');

            // Paso 4: Calcular la calidad de los retiros (nuevos, actualizados, sin cambio)
                // Mapear detalles por retiro_id para comparación más rápida
                $detallesV0 = $version0?->detalles->keyBy('retiro_id') ?? collect();
                $detallesVN = $ultimaVersion?->detalles ?? collect();

                // Inicializar contadores
                $nuevos = 0;
                $actualizados = 0;
                $sinCambios = 0;

                foreach ($detallesVN as $detalleN) {
                    $id = $detalleN->retiro_id;
                    $detalle0 = $detallesV0->get($id);


                    if (!$detalle0) {
                        // No existía en versión 0 → es nuevo
                        $nuevos++;
                    } else {

                        // Existía → comparar atributos clave para ver si cambió
                        if (
                            $detalle0->camion_id              !== $detalleN->camion_id ||
                            $detalle0->fecha_hora_retiro?->format('Y-m-d H:i') !== $detalleN->fecha_hora_retiro?->format('Y-m-d H:i') ||
                            $detalle0->eta?->format('Y-m-d H:i')                !== $detalleN->eta?->format('Y-m-d H:i') ||
                            $detalle0->especie_id             !== $detalleN->especie_id ||
                            $detalle0->bins                   !== $detalleN->bins ||
                            $detalle0->kilogramos_estimados   !== $detalleN->kilogramos_estimados
                        ) {
                            $actualizados++;
                        } else {
                            $sinCambios++;
                        }
                    }
                }

            // Paso 5: Contar estados de los retiros en la última versión (en proceso, efectuado, cancelado)
                // Inicializar contadores por estado
                $enProceso  = 0;
                $efectuado  = 0;
                $cancelado  = 0;

                foreach ($detallesVN as $detalleN) {
                    switch ($detalleN->estado) {
                        case config('constantes.ESTADO_RETIRO_PROGRAMADO'):
                            $enProceso++;
                            break;
                        case config('constantes.ESTADO_RETIRO_TERMINADO'):
                            $efectuado++;
                            break;
                        case config('constantes.ESTADO_RETIRO_CANCELADO'):
                            $cancelado++;
                            break;
                    }
                }

            // Paso 6: Calcular ETA mínima y máxima en los retiros de la última versión
                // ETA mínima y máxima en la última versión
                $etaMinima = $detallesVN->min(function ($detalle) {
                    return optional($detalle->eta)?->format('Y-m-d H:i');
                });

                $etaMaxima = $detallesVN->max(function ($detalle) {
                    return optional($detalle->eta)?->format('Y-m-d H:i');
                });

            return [
                        'fecha_programa'       => $fecha,
                        'version0'             => $version0,
                        'ultimaVersion'        => $ultimaVersion,
                        'cantidad_versiones'   => $cantidadVersiones,
                        'primera_emision'      => $primeraEmision,
                        'ultima_emision'       => $ultimaEmision,
                        'retiros_nuevos'       => $nuevos,
                        'retiros_actualizados' => $actualizados,
                        'retiros_sin_cambios'  => $sinCambios,
                        'retiros_en_proceso'   => $enProceso,
                        'retiros_efectuados'   => $efectuado,
                        'retiros_cancelados'   => $cancelado,
                        'eta_minima'           => $etaMinima,
                        'eta_maxima'           => $etaMaxima,
                    ];
        });

        // Paso 7: Retornar la colección procesada a la vista
        return view('programa-diario.index', [
                                                'programas' => $datosProgramas,
                                            ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        return view('programa-diario.emitir')
                ->with('error', $request->get('error')); // Para mostrar un error recibido en AJAX en esa misma vista
    }

    /**
     * Retorna los datos del programa diario a emitir así como sus novedades y estadística.
     *
     * @return \Illuminate\Http\Response
     */
    public function previsualizarEmision(Request $request)
    {
        $fecha = $request->get('fecha');

        if (!$fecha || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return response()->json(['error' => __('responses.programa.previsualizacion_fecha_invalida')], 400);
        }

        try {
            $resumen = $this->construirResumenProgramaDiario($fecha);
            return response()->json($resumen);

        } catch (\Throwable $e) {
            Log::error("Error en previsualizarEmision(): {$e->getMessage()}");
            return response()->json(['error' => __('responses.programa.previsualizacion_error')], 500);
        }
    }

    /**
     * Construye el resumen de previsualización para la fecha dada.
     * NO maneja excepciones: las propaga para que el caller (previsualizarEmision) las capture.
     *
     * @param  string  $fecha  Formato YYYY-MM-DD
     * @return array
     */
    private function construirResumenProgramaDiario(string $fecha): array
    {
        $planificaciones = Planificacion::with([
                                                'retiro.solicitud.maquila.sucursal.comuna',
                                                'retiro.solicitud.maquila.empresa',
                                                'retiro.tipoRetiro',
                                                'retiro.estado',
                                                'camion',
                                                'tipoMateriaPrima',
                                                'especie'
                                            ])
                                            ->whereDate('fecha_hora_planificada', $fecha)               // 🔁 Pivote real por Planificación - Pidieron cambiarla desde ETA
                                            // ->where('activo', 1)                                     // Comentamos este filtro para que aparezcan los CANCELADOS (que siempre poseen estado en false)
                                            ->whereIn('estado_id', [                                    // Los estados validos para considerarlos en el Informe Diario
                                                config('constantes.ESTADO_RETIRO_PLANIFICADO'),         // Tiene que PROPONER PLANIFICADOS pero después GUARDARLOS COMO PROGRAMADOS (en el STORE)
                                                config('constantes.ESTADO_RETIRO_PROGRAMADO'),
                                                config('constantes.ESTADO_RETIRO_TERMINADO'),
                                                config('constantes.ESTADO_RETIRO_CANCELADO'),
                                            ])
                                            ->orderBy('fecha_hora_planificada') // 🔁 También ordenado por Planificación - Pidieron cambiarla desde ETA
                                            ->get();

        $programaV1 = ProgramaDiario::where('fecha_programa', $fecha)->where('version', 1)->first(); // Se busca la versión n°1 - La original.
        $detalleV1 = collect();
        if ($programaV1) {
            $detalleV1 = ProgramaDiarioDetalle::where('programa_id', $programaV1->id)->get()->keyBy('retiro_id');
        }

        $ultimaVersion = ProgramaDiario::where('fecha_programa', $fecha)->max('version');
        $isInitialPreview = ($ultimaVersion ?? 0) === 0;        // Primera previsualización si no hay versión guardada aún

        $resumen = [
            'version' => ($ultimaVersion ?? 0) + 1,
            'total' => 0,
            'nuevos' => 0,
            'actualizados' => 0,
            'sin_cambios' => 0,
            'detalles' => []
        ];

        foreach ($planificaciones as $plan) {
            $retiro = $plan->retiro;
            $item = [
                'retiro_id'            => $retiro->id,
                'estado_retiro_id'     => $retiro->estado_id,
                'sucursal_id'          => optional($retiro->solicitud->maquila)->sucursal_id,
                'comuna_id'            => optional($retiro->solicitud->maquila->sucursal)->comuna_id,
                'proveedor_id'         => optional($retiro->solicitud->maquila)->empresa_id,
                'fecha_hora_retiro'    => optional($plan->fecha_hora_planificada)?->toDateTimeString(),
                'camion_id'            => $plan->camion_id,
                'tipo_retiro_id'       => $retiro->tipo_retiro_id,
                'duracion_viaje'       => $plan->duracion_viaje,
                'eta'                  => optional($plan->hora_llegada_estimada)?->toDateTimeString(),
                'kilogramos_estimados' => $retiro->kilogramos_estimados,
                'producto_id'          => $plan->tipo_materia_prima_id,
                'especie_id'           => $plan->especie_id,
                'bins'                 => $retiro->cantidad_bins,

                // Visuales para DataTable (puedes separarlos luego si quieres)
                'estado_retiro'        => optional($retiro->estado)->nombre,
                'sucursal'             => optional($retiro->solicitud->maquila->sucursal)->nombre_sucursal,
                'comuna'               => optional($retiro->solicitud->maquila->sucursal->comuna)->nombre ?? '',
                'proveedor'            => optional($retiro->solicitud->maquila->empresa)->razon_social,
                'camion'               => optional($plan->camion)->patente,
                'tipo_retiro'          => optional($retiro->tipoRetiro)->nombre,
                'producto'             => optional($plan->tipoMateriaPrima)->nombre,
                'especie'              => optional($plan->especie)->nombre,
            ];

            $novedad = config('constantes.CALIDAD_RETIRO_ORIGINAL');                        // Se supone original hasta que se demuestre lo contrario

            $itemV1 = $detalleV1->get($retiro->id);
            if (!$itemV1) {
                if ($isInitialPreview) {
                    $novedad = config('constantes.CALIDAD_RETIRO_ORIGINAL');                // Versión 1: nada existía antes, lo tratamos como “Original”
                    $resumen['sin_cambios']++;
                } else {
                    $novedad = config('constantes.CALIDAD_RETIRO_NUEVO');                   // Versión ≥2: los que no estaban en v1 son realmente “Nuevos”
                    $resumen['nuevos']++;
                }
            } else {
                $cambios = collect([
                                        $itemV1->sucursal_id                                         !=  $item['sucursal_id'],
                                        $itemV1->comuna_id                                           !=  $item['comuna_id'],
                                        $itemV1->proveedor_id                                        !=  $item['proveedor_id'],
                                        optional($itemV1->fecha_hora_retiro)?->toDateTimeString()    !== $item['fecha_hora_retiro'],
                                        $itemV1->camion_id                                           !=  $item['camion_id'],
                                        $itemV1->tipo_retiro_id                                      !=  $item['tipo_retiro_id'],
                                        $itemV1->duracion_viaje                                      !== $item['duracion_viaje'],
                                        optional($itemV1->eta)?->toDateTimeString()                  !== $item['eta'],
                                        (int) $itemV1->kilogramos_estimados                          !== (int) $item['kilogramos_estimados'],
                                        $itemV1->producto_id                                         !=  $item['producto_id'],
                                        $itemV1->especie_id                                          !=  $item['especie_id'],
                                        (int) $itemV1->bins                                          !== (int) $item['bins'],
                                    ])
                                    ->filter()
                                    ->count();

                // Log::debug('🟡 Verificación de diferencias retiro_id:' . $retiro->id  . ' con versión V1 — ' . now()->format('Y-m-d H:i:s'));
                // if ($itemV1->sucursal_id != $item['sucursal_id']) {
                //     Log::debug('🔁 sucursal_id diferente — V1=' . $itemV1->sucursal_id . ', Nuevo=' . $item['sucursal_id']);
                // }
                // if ($itemV1->comuna_id != $item['comuna_id']) {
                //     Log::debug('🔁 comuna_id diferente — V1=' . $itemV1->comuna_id . ', Nuevo=' . $item['comuna_id']);
                // }
                // if ($itemV1->proveedor_id != $item['proveedor_id']) {
                //     Log::debug('🔁 proveedor_id diferente — V1=' . $itemV1->proveedor_id . ', Nuevo=' . $item['proveedor_id']);
                // }
                // if (optional($itemV1->fecha_hora_retiro)?->toDateTimeString() !== $item['fecha_hora_retiro']) {
                //     Log::debug('🔁 fecha_hora_retiro diferente — V1->toDateTimeString()=' . ($itemV1->fecha_hora_retiro ?? 'NULL') . ', Nuevo=' . $item['fecha_hora_retiro']);
                // }
                // if ($itemV1->camion_id != $item['camion_id']) {
                //     Log::debug('🔁 camion_id diferente — V1=' . $itemV1->camion_id . ', Nuevo=' . $item['camion_id']);
                // }
                // if ($itemV1->tipo_retiro_id != $item['tipo_retiro_id']) {
                //     Log::debug('🔁 tipo_retiro_id diferente — V1=' . $itemV1->tipo_retiro_id . ', Nuevo=' . $item['tipo_retiro_id']);
                // }
                // if ($itemV1->duracion_viaje !== $item['duracion_viaje']) {
                //     Log::debug('🔁 duracion_viaje diferente — V1=' . $itemV1->duracion_viaje . ', Nuevo=' . $item['duracion_viaje']);
                // }
                // if (optional($itemV1->eta)?->toDateTimeString() !== $item['eta']) {
                //     Log::debug('🔁 eta diferente — V1->toDateTimeString()=' . ($itemV1->eta ?? 'NULL') . ', Nuevo=' . $item['eta']);
                // }
                // if ((int) $itemV1->kilogramos_estimados !== (int) $item['kilogramos_estimados']) {
                //     Log::debug('🔁 kilogramos_estimados diferente — V1=' . $itemV1->kilogramos_estimados . ', Nuevo=' . $item['kilogramos_estimados']);
                // }
                // if ($itemV1->producto_id != $item['producto_id']) {
                //     Log::debug('🔁 producto_id diferente — V1=' . $itemV1->producto_id . ', Nuevo=' . $item['producto_id']);
                // }
                // if ($itemV1->especie_id != $item['especie_id']) {
                //     Log::debug('🔁 especie_id diferente — V1=' . $itemV1->especie_id . ', Nuevo=' . $item['especie_id']);
                // }
                // if ((int) $itemV1->bins !== (int) $item['bins']) {
                //     Log::debug('🔁 bins diferente — V1=' . $itemV1->bins . ', Nuevo=' . $item['bins']);
                // }

                $novedad = $cambios > 0                                                     // Calcula novedad según si hubo: "Actualizado" o se mantiene "Original"
                                ? config('constantes.CALIDAD_RETIRO_ACTUALIZADO')
                                : config('constantes.CALIDAD_RETIRO_ORIGINAL');

                $novedad === config('constantes.CALIDAD_RETIRO_ACTUALIZADO')                // Contabilizar según la novedad
                                ? $resumen['actualizados']++
                                : $resumen['sin_cambios']++;
            }

            $item['novedad']       = $novedad;
            $resumen['detalles'][] = $item;
            $resumen['total']++;
        }
        return $resumen;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $fecha = $request->input('fecha_programa');
        $jsonDetalle = $request->input('programa_detalle');

        if (!$fecha || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return back()->with('error', __('responses.programa.fecha_invalida'));
        }

        if (!$jsonDetalle) {
            return back()->with('error', __('responses.programa.sin_detalle'));
        }

        $detalles = json_decode($jsonDetalle, true);

        if (!is_array($detalles)) {
            return back()->with('error', __('responses.programa.detalle_invalido'));
        }

        try {
            ['programa' => $programa, 'nuevaVersion' => $nuevaVersion] = $this->emitirProgramaDiario($fecha, $detalles);

            return redirect()->route('programa-diario.preparar-emision')
                                ->with('status', __('responses.programa.emitido_exito', [
                                                                                            'fecha'   => $fecha,
                                                                                            'version' => $nuevaVersion,
                                                                                        ]));

        } catch (\Throwable $e) {
            if (DB::connection()->transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::error("Error al emitir Programa Diario ({$fecha}): " . $e->getMessage());
            return back()->with('error', __('responses.programa.error_emision'));
        }
    }

    /**
     * Emite (persiste) el Programa Diario para una fecha y un set de detalles.
     * Maneja la transacción internamente. Retorna el Programa creado y la nueva versión.
     *
     * @param  string $fecha
     * @param  array  $detalles
     * @return array{programa:\App\Models\ProgramaDiario,nuevaVersion:int}
     *
     * @throws \Throwable  Propaga cualquier excepción para que el caller decida.
     */
    private function emitirProgramaDiario(string $fecha, array $detalles): array
    {
        DB::beginTransaction();

        try {
            // Calcular nueva versión
            $ultimaVersion = ProgramaDiario::where('fecha_programa', $fecha)->max('version');
            $nuevaVersion = ($ultimaVersion ?? 0) + 1;

            // Crear cabecera del programa diario
            $programa = ProgramaDiario::create([
                'fecha_programa' => $fecha,
                'fecha_emision'  => now(),
                'version'        => $nuevaVersion,
                'usuario_id'     => Auth::id(),
                'estado'         => config('constantes.ESTADO_PROGRAMA_EMITIDO'), // Estado emitido (puedes ajustar según lógica interna)
            ]);

            // Recorrer detalles y guardarlos
            foreach ($detalles as $item) {
                // 1. Resolver estado definitivo
                $estadoFinal = $item['estado'] == config('constantes.ESTADO_RETIRO_PLANIFICADO')    ? config('constantes.ESTADO_RETIRO_PROGRAMADO')
                                                                                                    : $item['estado'];

                // 2. Crear el detalle del programa
                ProgramaDiarioDetalle::create([
                    'programa_id'          => $programa->id,
                    'retiro_id'            => $item['retiro_id'] ?? null,
                    'estado'               => $estadoFinal,
                    'novedad'              => $item['novedad'] ?? 0,
                    'sucursal_id'          => $item['sucursal_id'] ?? null,
                    'comuna_id'            => $item['comuna_id'] ?? null,
                    'proveedor_id'         => $item['proveedor_id'] ?? null,
                    'fecha_hora_retiro'    => $item['fecha_hora_retiro'] ?? null,
                    'camion_id'            => $item['camion_id'] ?? null,
                    'tipo_retiro_id'       => $item['tipo_retiro_id'] ?? null,
                    'duracion_viaje'       => $item['duracion_viaje'] ?? null,
                    'eta'                  => $item['eta'] ?? null,
                    'kilogramos_estimados' => $item['kilogramos_estimados'] ?? null,
                    'producto_id'          => $item['producto_id'] ?? null,
                    'especie_id'           => $item['especie_id'] ?? null,
                    'bins'                 => $item['bins'] ?? null,
                ]);

                // 3. Sincronizar estado en el retiro y su planificación
                if (!empty($item['retiro_id'])) {
                    $retiro = Retiro::find($item['retiro_id']);
                    if ($retiro) {
                        $retiro->estado_id = $estadoFinal;
                        $retiro->save();

                        if ($retiro->planificacion) {
                            $retiro->planificacion->estado_id = $estadoFinal;
                            $retiro->planificacion->save();
                        }
                    }
                }                
            }

            DB::commit();

            // Calculamos los DETALLES y DETALLES PREVIA para usarlos en las notificaciones
            $detallesPorVersion = ProgramaDiario::detallesPorFechaYVersion($programa->fecha_programa->format('Y-m-d'), $programa->version);
            $detalles           = $detallesPorVersion[$programa->version] ?? collect();

            $detallesPorVersionPrevia = ProgramaDiario::detallesPorFechaYVersion($programa->fecha_programa->format('Y-m-d'), $programa->version - 1);
            $detallesPrevia           = $programa->version > 1
                                            ? ($detallesPorVersionPrevia[$programa->version - 1] ?? collect())
                                            : collect();
            
            try {
                $this->notificarRolesInternosEmisionProgramaDiario($programa, $detalles);
            } catch (\Throwable $e) {
                Log::warning("Error al notificar usuarios internos: " . $e->getMessage());
            }

            try {
                $this->notificarTransportistasCorreo($programa, $detalles, $detallesPrevia);
            } catch (\Throwable $e) {
                Log::warning("Error al notificar transportistas: " . $e->getMessage());
            }

            try {
                $this->notificarConductoresTelegram($programa, $detalles, $detallesPrevia);
            } catch (\Throwable $e) {
                Log::warning("Error al notificar conductores: " . $e->getMessage());
            }

            return ['programa' => $programa, 'nuevaVersion' => $nuevaVersion];

        } catch (\Throwable $e) {
            if (DB::connection()->transactionLevel() > 0) {
                DB::rollBack();
            }
            throw $e;
        }
    }

    /**
     * Notifica a los roles internos sobre la emisión del Programa Diario (última versión).
     *
     * @param  \App\Models\ProgramaDiario $programaDiario
     * @return void
     */
    public function notificarRolesInternosEmisionProgramaDiario(ProgramaDiario $programaDiario, Collection $detalles): void
    {
        try {
            $RolesInternos = [
                config('constantes.ROL_PERSONAL_GERENCIA'),
                config('constantes.ROL_PERSONAL_PRODUCCION'),
                config('constantes.ROL_PERSONAL_CALIDAD'),
                config('constantes.ROL_PERSONAL_MANTENCION'),
                config('constantes.ROL_PERSONAL_ROMANA'),
            ];

            $destinatariosRolesInternos = User::whereIn('rol_id', $RolesInternos)
                                                ->pluck('email')
                                                ->filter()
                                                ->unique()
                                                ->toArray();

            $destinatariosAdminIT = User::where('rol_id', config('constantes.ROL_ADMINISTRADOR_IT'))
                                            ->pluck('email')
                                            ->filter()
                                            ->unique()
                                            ->toArray();

            if (empty($destinatariosRolesInternos) && empty($destinatariosAdminIT)) {
                Log::warning("No se encontraron destinatarios internos para notificar programa diario ID {$programaDiario->id}");
                return;
            }

            $destinatarios = [
                                'destinatariosRolesInternos' => $destinatariosRolesInternos,
                                'destinatariosAdminIT'       => $destinatariosAdminIT,
                            ];

            // 🔍 Aplicar filtrado SOLO si no es la versión inicial
            if ($programaDiario->version > 1) {
                $detalles = $detalles->filter(function ($detalle) {
                                                                    return ( $detalle['estado'] === config('constantes.ESTADO_RETIRO_CANCELADO')) || 
                                                                                in_array($detalle['novedad'], [config('constantes.CALIDAD_RETIRO_NUEVO'),
                                                                                                            config('constantes.CALIDAD_RETIRO_ACTUALIZADO')] );
                                                                })
                                            ->values(); // Resetear índices
            }

            if ($detalles->isNotEmpty()) {
                CorreoHelper::enviarCorreoProgramaDiarioEmitido($destinatarios, $programaDiario, $detalles);
            } else {
                Log::warning("No se encontró versión emitida del programa diario ID {$programaDiario->id}");
            }

        } catch (\Throwable $e) {
            Log::error("Error al notificar roles internos por programa diario ID {$programaDiario->id}: " . $e->getMessage());
        }
    }

    /**
     * Notifica por correo del Programa Diario a Transportistas.
     *
     */
    private function notificarTransportistasCorreo(ProgramaDiario $programaDiario, Collection $detalles, Collection $detallesPrevia): void
    {
        try {
            // 🕒 Marcar inicio de notificación en cabecera
            $programaDiario->update([
                'estado_notif_correo'     => config('constantes.NOTIF_EN_PROCESO'),
                'inicio_notif_correo'     => now(),
                'fin_notif_correo'        => null,
            ]);

            // ⚠️ Validar existencia de versión emitida
            if (empty($detalles)) {
                Log::warning("No se encontró versión emitida del programa diario ID {$programaDiario->id}");
                throw new \RuntimeException("Programa diario sin detalles emitidos.");
            }

            // 🔍 Aplicar filtrado SOLO si no es la versión inicial
            if ($programaDiario->version > 1) {
                $detalles = $detalles->filter(function ($detalle) {
                                                                    return ( $detalle['estado'] === config('constantes.ESTADO_RETIRO_CANCELADO')) || 
                                                                             in_array($detalle['novedad'], [config('constantes.CALIDAD_RETIRO_NUEVO'),
                                                                                                            config('constantes.CALIDAD_RETIRO_ACTUALIZADO')] );
                                                                })
                                            ->values(); // Resetear índices
            }

            // 🧭 Agrupar detalles por empresa transportista (id)
            $grupos = $detalles->groupBy('transportista_id');

            $huboErrores = false;
            foreach ($grupos as $empresaId => $renglones) {
                $empresa = Empresa::find($empresaId);

                if (!$empresa || !$empresa->email_contacto) {
                    Log::warning("Empresa ID $empresaId no tiene correo configurado. Se omite notificación.");
                    $huboErrores = true;
                    continue;
                }

                $datos = $renglones->filter(function ($detalle) use ($detallesPrevia, $programaDiario) {
                                                // Si es versión 1, no filtramos nada
                                                if ($programaDiario->version == 1) {
                                                    return true;
                                                }

                                                // Buscar el detalle correspondiente en la versión anterior
                                                $detalleAnterior = $detallesPrevia->firstWhere('numero_retiro', $detalle['numero_retiro']);

                                                // Si no existía → es nuevo
                                                if (!$detalleAnterior) return true;

                                                // Comparar campos relevantes
                                                return (
                                                    $detalle['fecha_hora_retiro']  !== $detalleAnterior['fecha_hora_retiro'] ||
                                                    $detalle['camion']             !== $detalleAnterior['camion'] ||
                                                    $detalle['patente_rampla']     !== $detalleAnterior['patente_rampla'] ||
                                                    $detalle['tipo_camion']        !== $detalleAnterior['tipo_camion'] ||
                                                    $detalle['nombre_chofer']      !== $detalleAnterior['nombre_chofer'] ||
                                                    $detalle['rut_chofer']         !== $detalleAnterior['rut_chofer']
                                                );
                                        })->map(function ($detalle) {
                                            return [
                                                'estado'               => $detalle['estado'] ?? '—',
                                                'novedad'              => $detalle['novedad'] ?? '—',
                                                'numero_retiro'        => $detalle['numero_retiro'] ?? '—',
                                                'fecha_hora_agendada'  => $detalle['fecha_hora_retiro'] ?? '—',
                                                'patente_camion'       => $detalle['camion'] ?? '—',
                                                'patente_rampla'       => $detalle['patente_rampla'] ?? '—',
                                                'tipo_camion'          => $detalle['tipo_camion'] ?? '—',
                                                'nombre_chofer'        => $detalle['nombre_chofer'] ?? '—',
                                                'rut_chofer'           => $detalle['rut_chofer'] ?? '—',
                                            ];
                                        });
                
                // Validamos que hayan quedado retiros que notificar una vez que filtramos NOVEDADES                                        
                if ($datos instanceof Collection && $datos->isNotEmpty()) {
                    // ✉️ Enviar correo usando método del helper - CorreoHelper
                    try {
                        // AQUI HELPER DE ENVIO DE CORREO
                        CorreoHelper::enviarCorreoProgramaTransportista($empresa, $programaDiario, $datos);

                        // 🟢 Marcar como enviado en detalles
                        foreach ($renglones as $detalle) {
                            if ( isset($detalle['detalle_programa_id']) ) {
                                ProgramaDiarioDetalle::where('id', $detalle['detalle_programa_id'])
                                                    ->update([
                                                        'estado_notif_correo' => config('constantes.NOTIF_ENVIADO'),
                                                        'fecha_envio_correo'  => now(),
                                                    ]);
                            }
                        }

                    } catch (\Throwable $e) {
                        Log::error("Fallo envío de correo a empresa ID $empresaId: " . $e->getMessage());
                        $huboErrores = true;

                        // 🔴 Marcar como fallido
                        foreach ($renglones as $detalle) {
                            if (isset($detalle['detalle_programa_id'])) {
                                ProgramaDiarioDetalle::where('id', $detalle['detalle_programa_id'])
                                                    ->update([
                                                        'estado_notif_correo' => config('constantes.NOTIF_FALLIDO'),
                                                        'fecha_envio_correo'  => now(),
                                                    ]);
                            }
                        }
                    }

                    // ⏱️ Control de tasa
                    sleep(2);

                } else {
                    Log::warning("En el programa diario ID {$programaDiario->id} Version {$programaDiario->version} NO se encontraron retiros con novedades que notificar a empresa ID {$empresaId}");
                }
            }

            // 🕒 Marcar fin del proceso
            $programaDiario->update([
                                        'estado_notif_correo' => $huboErrores   ? config('constantes.NOTIF_FALLIDO')
                                                                                : config('constantes.NOTIF_ENVIADO'),
                                        'fin_notif_correo'    => now(),
                                    ]);
        } catch (\Throwable $e) {
            Log::error("Error general al notificar transportistas en programa ID {$programaDiario->id}: " . $e->getMessage());

            // Fallback si la excepción ocurre fuera del ciclo
            $programaDiario->update([
                                        'estado_notif_correo' => config('constantes.NOTIF_FALLIDO'),
                                        'fin_notif_correo'    => now(),
            ]);
        }
    }

    /**
     * Notifica por Telegram del Programa Diario a Conductor.
     *
     */
    private function notificarConductoresTelegram(ProgramaDiario $programaDiario, Collection $detalles, Collection $detallesPrevia): void
    {
        // 📌 1. Inicio del método y trazabilidad de cabecera:
        $programaDiario->inicio_notif_telegram = now();
        $programaDiario->estado_notif_telegram = config('constantes.NOTIF_EN_PROCESO');
        $programaDiario->save();

        // ⚠️ Validar existencia de versión emitida
        if (empty($detalles)) {
            Log::warning("No se encontró versión emitida del programa diario ID {$programaDiario->id}");
            throw new \RuntimeException("Programa diario sin detalles emitidos.");
        }

        // 🔍 Aplicar filtrado SOLO si no es la versión inicial
        if ($programaDiario->version > 1) {
            $detalles = $detalles->filter(function ($detalle) {
                                                                return ( $detalle['estado'] === config('constantes.ESTADO_RETIRO_CANCELADO')) || 
                                                                            in_array($detalle['novedad'], [config('constantes.CALIDAD_RETIRO_NUEVO'),
                                                                                                        config('constantes.CALIDAD_RETIRO_ACTUALIZADO')] );

                                                            })
                                        ->values(); // Resetear índices
        }

        // 📌 3. Iterar sobre los retiros y notificar conductor por cada uno:
        $fallos = false;
        foreach ($detalles as $detalle) {
            try {
                // 🧪 Evitar envío si no hubo cambios (versión > 1)
                if ($programaDiario->version > 1) {
                    $detalleAnterior = $detallesPrevia->firstWhere('numero_retiro', $detalle['numero_retiro']);

                    // Si no existía en versión anterior, es nuevo => se notifica
                    if ($detalleAnterior) {
                        // Comparar campos considerados en el Mensaje Telegram
                        $sinCambios = (
                                        $detalle['nombre_chofer']      === $detalleAnterior['nombre_chofer'] &&
                                        $detalle['sucursal']           === $detalleAnterior['sucursal'] &&
                                        $detalle['fecha_hora_retiro']  === $detalleAnterior['fecha_hora_retiro'] &&
                                        $detalle['camion']             === $detalleAnterior['camion'] &&
                                        $detalle['tipo_camion']        === $detalleAnterior['tipo_camion'] &&
                                        $detalle['bins']               === $detalleAnterior['bins']
                                    );

                        if ($sinCambios) {
                            Log::info("[Telegram] Retiro sin cambios respecto a versión anterior, no se envía", [
                                'programa_diario_id' => $programaDiario->id,
                                'retiro_id'          => $detalle['numero_retiro'],
                                'conductor_id'       => $detalle['conductor_id'],
                            ]);

                            ProgramaDiarioDetalle::where('id', $detalle['detalle_programa_id'])
                                                    ->update([
                                                        'estado_notif_telegram' => config('constantes.NOTIF_SIN_CAMBIOS'),
                                                    ]);
                            continue;
                        }
                    }
                }

                $conductor = Conductor::find( $detalle['conductor_id'] );
                if (!$conductor) {
                    Log::warning("Detalle de Programa {$detalle['detalle_programa_id']} - Retiro {$detalle['numero_retiro']} sin conductor asignado.");
                    continue;
                }

                if (!$conductor->telegram_chat_id) {
                    if ( isset($detalle['detalle_programa_id']) ) {
                        ProgramaDiarioDetalle::where('id', $detalle['detalle_programa_id'])
                                                ->update([
                                                    'estado_notif_telegram' => config('constantes.NOTIF_SIN_TELEGRAM'),
                                                ]);
                    }

                    Log::info('[Telegram] Conductor sin chat_id, se omite envío', [
                                                                                    'programa_diario_id' => $programaDiario->id,
                                                                                    'retiro_id'          => $detalle['numero_retiro'],
                                                                                    'conductor_id'       => $detalle['conductor_id'],
                                                                                ]);
                    continue;
                }

                $chatId = $conductor->telegram_chat_id;
                $fechaCarbon = Carbon::createFromFormat('d-m-Y H:i', $detalle['fecha_hora_retiro']);

                if ($detalle['estado'] === config('constantes.ESTADO_RETIRO_CANCELADO')){
                    $propositoMensaje = "Le informamos que ha sido cancelada la planificación del siguiente retiro:";       // Retiro ANULADO
                    $instruccionesMensaje = "Por favor, no proceda con el retiro. Si ya había iniciado el trayecto, contacte inmediatamente al Coordinador.";
                }
                else{
                    if ($detalle['novedad'] === config('constantes.CALIDAD_RETIRO_ACTUALIZADO')){
                        $propositoMensaje = "Un retiro asignado ha recibido una modificación en el detalle:";               // Retiro MODIFICADO
                    }
                    else{
                        $propositoMensaje = "Se le ha asignado un retiro con el siguiente detalle:";                        // Retiro NUEVO
                    }
                    $instruccionesMensaje = "Ante cualquier duda, puede contactar al Coordinador de Retiros de Materia Prima de La Portada.";
                }

                $mensaje = "📣 Estimado {$detalle['nombre_chofer']},\n\n"
                        . "{$propositoMensaje}\n\n"

                        . "🏭 Planta: {$detalle['sucursal']}\n"
                        . "📅 Fecha: " . $fechaCarbon->format('d/m/Y') . "\n"
                        . "⏰ Hora: " . $fechaCarbon->format('H:i') . "\n"
                        . "🚛 Patente Camión: {$detalle['camion']}\n"
                        . "🏷️ Tipo Camión: {$detalle['tipo_camion']}\n"
                        . "🔁 Repone Bins: " . ($detalle['bins'] > 0 ? $detalle['bins'] : 'NO') . "\n\n"

                        . "{$instruccionesMensaje}\n\n"

                        . "Atentamente,\n"
                        . "Sistema de Planificación de Retiro de Materia Prima";

                $enviado =  $conductor->notificarPorTelegram($mensaje, $chatId);

                if ($enviado) {
                    ProgramaDiarioDetalle::where('id', $detalle['detalle_programa_id'])
                                            ->update([
                                                'estado_notif_telegram' => config('constantes.NOTIF_ENVIADO'),
                                                'fecha_envio_telegram'  => now(),
                                            ]);

                } else {
                    ProgramaDiarioDetalle::where('id', $detalle['detalle_programa_id'])
                                            ->update([
                                                        'estado_notif_telegram' => config('constantes.NOTIF_FALLIDO'),
                                                    ]);

                    $fallos = true;

                    Log::error('[Telegram] Falló envío Telegram', [
                        'programa_diario_id' => $programaDiario->id,
                        'retiro_id'          => $detalle['numero_retiro'],
                        'conductor_id'       => $detalle['conductor_id'],
                    ]);
                }

                sleep(1); // Controlar tasa de envíos

            } catch (\Throwable $e) {
                ProgramaDiarioDetalle::where('id', $detalle['detalle_programa_id'])
                                        ->update([
                                                    'estado_notif_telegram' => config('constantes.NOTIF_FALLIDO'),
                                                ]);

                $fallos = true;

                Log::error('[Telegram] Excepción al enviar Telegram', [
                    'programa_diario_id' => $programaDiario->id,
                    'retiro_id'          => $detalle['numero_retiro'],
                    'conductor_id'       => $detalle['conductor_id'],
                    'exception'          => $e->getMessage(),
                ]);
            }
        }

        // 📌 4. Finalizar trazabilidad de cabecera:
        $programaDiario->fin_notif_telegram = now();
        $programaDiario->estado_notif_telegram = $fallos    ? config('constantes.NOTIF_FALLIDO')
                                                            : config('constantes.NOTIF_ENVIADO');

        $programaDiario->save();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($fecha)
    {
        // Validación de formato de fecha
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return back()->with('error', __('responses.programa.fecha_invalida'));
        }

        // Usamos nuestro método reutilizable
        $detallesPorVersion = ProgramaDiario::detallesPorFechaYVersion($fecha, config('constantes.VERSION_TODAS'));

        return view('programa-diario.ver', [
                                                'fecha_programa'     => \Carbon\Carbon::parse($fecha)->format('d-m-Y'),
                                                'detallesPorVersion' => $detallesPorVersion,
        ]);
    }

    /**
     * Muestra los gráficos y KPIs consolidados del programa diario para una fecha específica.
     *
     * Este método reutiliza los mismos 5 métodos estadísticos definidos en el modelo ProgramaDiario,
     * pero recibe como parámetro una fecha cualquiera (emitida previamente), en lugar de usar "hoy".
     *
     * @param string $fecha Fecha en formato Y-m-d
     * @return \Illuminate\View\View
     */
    public function consolidados($fecha)
    {
        // 🧱 BLOQUE 1 — Validar formato de fecha
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return back()->with('error', __('responses.programa.fecha_invalida'));
        }

        // 🧱 BLOQUE 4 — Obtener datos estadísticos para esa fecha
        $desde = \Carbon\Carbon::parse($fecha)->subDays(6)->toDateString();           // Últimos 7 días incluyendo "fecha"
        $hasta = $fecha;                                                              // "fecha"

        $tonsPorSucursal = ProgramaDiario::obtenerTonsPorSucursalHoy($fecha);
        $planVsReal      = ProgramaDiario::obtenerTonsPlanVsReal7Dias($desde, $hasta);
        $kpiHoy          = ProgramaDiario::obtenerKpiTonsHoy($fecha);
        $kpiPlan7dias    = ProgramaDiario::obtenerKpiAcumPlan7Dias($desde, $hasta);
        $kpiReal7dias    = ProgramaDiario::obtenerKpiAcumReal7Dias($desde, $hasta);

        // 🧱 BLOQUE 5 — Renderizar vista con los datos
        return view('programa-diario.consolidados', [
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

    public function verDesdeToken($token)
    {
        try {
            $pivoteCorreo = Crypt::decrypt($token); // es un array: ['campo' => 'programa_diario_id', 'valor' => 42]

            if (!is_array($pivoteCorreo) || !isset($pivoteCorreo['campo']) || !isset($pivoteCorreo['valor'])) {
                throw new \Exception('Token incompleto o malformado');
            }

            if ($pivoteCorreo['campo'] !== 'programa_diario_id') {
                throw new \Exception('Campo no reconocido en token: ' . $pivoteCorreo['campo']);
            }

            $programaDiario = ProgramaDiario::findOrFail($pivoteCorreo['valor']);

            return redirect()->route('programa-diario.ver', [ 'fecha' => $programaDiario->fecha_programa->format('Y-m-d') ]);
        }
        catch (\Exception $e) {
            Log::error('❌ Error al ver desde correo programa diario', [
                'token' => $token,
                'error' => $e->getMessage(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('programa-diario.index')->with('error', __('emails.token_invalido_expirado'));
        }
    }

    /**
     * Emite hoy el Programa Diario:
     * - Obtiene fecha de hoy,
     * - Construye materiales (resumen y detalles),
     * - Emite el programa usando los encapsulamientos existentes.
     *
     * @return \Illuminate\Http\Response
     */
    public function emitirProgramaDiarioAuto(string $fecha)
    {
        // 1) Fecha de hoy (formato YYYY-MM-DD)
        try {
            // Acepta 'YYYY-MM-DD' y Normaliza/valida fecha
            $fecha = \Carbon\Carbon::parse($fecha)->toDateString();
        } catch (\Throwable $e) {
            return response()->json([
                'ok'      => false,
                'error'   => 'fecha_invalida',
                'message' => __('responses.programa.previsualizacion_fecha_invalida'),
            ], 422);
        }

        try {
            // 2) Construir materiales (previsualización/resumen)
            $resumen = $this->construirResumenProgramaDiario($fecha);

            // Validación liviana: que existan detalles a emitir
            $detallesResumen = $resumen['detalles'] ?? [];
            if (empty($detallesResumen)) {
                return response()->json([
                                            'ok'       => false,
                                            'emitted'  => false,
                                            'reason'   => 'sin_detalle',
                                            'message'  => __('responses.programa.sin_detalle'),
                                            'fecha'    => $fecha,
                                            'version'  => null,
                                            'programa_id' => null,
                                        ], 200);                
            }

            // 3) Adaptar el payload del resumen al esperado por emitirProgramaDiario()
            //    (store espera 'estado', aquí viene como 'estado_retiro_id')
            $detalles = array_map(function ($r) {
                return [
                    'retiro_id'            => $r['retiro_id']            ?? null,
                    'estado'               => $r['estado_retiro_id']     ?? null,  // 👈 mapeo clave
                    'novedad'              => $r['novedad']              ?? 0,
                    'sucursal_id'          => $r['sucursal_id']          ?? null,
                    'comuna_id'            => $r['comuna_id']            ?? null,
                    'proveedor_id'         => $r['proveedor_id']         ?? null,
                    'fecha_hora_retiro'    => $r['fecha_hora_retiro']    ?? null,
                    'camion_id'            => $r['camion_id']            ?? null,
                    'tipo_retiro_id'       => $r['tipo_retiro_id']       ?? null,
                    'duracion_viaje'       => $r['duracion_viaje']       ?? null,
                    'eta'                  => $r['eta']                  ?? null,
                    'kilogramos_estimados' => $r['kilogramos_estimados'] ?? null,
                    'producto_id'          => $r['producto_id']          ?? null,
                    'especie_id'           => $r['especie_id']           ?? null,
                    'bins'                 => $r['bins']                 ?? null,
                ];
            }, $detallesResumen);

            // 4) Emitir (transacción adentro del método)
            ['programa' => $programa, 'nuevaVersion' => $nuevaVersion] = $this->emitirProgramaDiario($fecha, $detalles);

            // 5) Respuesta apta para CRON (sin redirects)
            return response()->json([
                'ok'          => true,
                'message'     => __('responses.programa.emitido_exito', [
                    'fecha'   => $fecha,
                    'version' => $nuevaVersion,
                ]),
                'programa_id' => $programa->id,
                'fecha'       => $fecha,
                'version'     => $nuevaVersion,
            ], 200);

        } catch (\Throwable $e) {
            // Blindaje por si en el futuro se agrega algo transaccional aquí
            if (\Illuminate\Support\Facades\DB::connection()->transactionLevel() > 0) {
                \Illuminate\Support\Facades\DB::rollBack();
            }

            \Illuminate\Support\Facades\Log::error("Error al emitir Programa Diario automático ({$fecha}): " . $e->getMessage());

            // Respuesta para CRON (sin back/redirect)
            return response()->json([
                'ok'      => false,
                'error'   => __('responses.programa.error_emision'),
                'message' => $e->getMessage(), // si prefieres no exponerla, elimina esta línea
            ], 500);
        }
    }
}
