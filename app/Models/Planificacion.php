<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Planificacion extends Model
{
    protected $table = 'planificaciones';

    protected $fillable = [
        'retiro_id',
        'fecha_hora_planificada',
        'duracion_viaje',
        'hora_llegada_estimada',
        'especie_id',
        'tiene_restriccion',
        'tipo_materia_prima_id',
        'camion_id',
        'patente_rampla',
        'conductor_id',
        'motivo_modificacion_id',
        'estado_id',
        'ticket_cierre',
        'activo',
    ];

    protected $casts = [
        'fecha_hora_planificada'  => 'datetime',
        'hora_llegada_estimada'   => 'datetime',
        'tiene_restriccion'       => 'boolean',
        'activo'                  => 'boolean',
    ];

    // Modelo Planificacion sincroniza el estado del retiro con el estado actual de la planificación.
    public function sincronizarEstadoRetiro(string $comentario_anulacion = ''): void
    {
        // $this->loadMissing('retiro'); // ✅ Asegura que la relación esté disponible

        if (isset($this->retiro) && isset($this->estado_id)) {

            if ( in_array( $this->estado_id, [  config('constantes.CATALOGO_NO_ESPECIFICADO'),              // Si el estado de la planificacón es CERO (CRUDA, VACIA) la ABORTARON
                                                config('constantes.ESTADO_RETIRO_CANCELADO')    ]) ) {      // Si es CANCELADO la CANCELARON
                $datos = [
                    'estado_id'             => config('constantes.ESTADO_RETIRO_CANCELADO'),                    // Marcamos el estado del retiro como CANCELADO
                    'activo'                => false,                                                           // Marcamos el estado del registro del retiro como inactivo
                    'comentario_anulacion'  => $comentario_anulacion,                                           // Y le ponemos el comentario ingresado por el usuario
                ];
            }
            else{                                                                                           // El estado de la PLANIFICACION es significativo
                $datos = [                                                                                      // (PLANIFICADA, PROGRAMADA, TERMINADA)
                    'estado_id'             => $this->estado_id,                                                // Se lo copiamos al RETIRO
                ];
            }

            $this->retiro->update($datos);
        }
    }

    // RELACIONES

    // Relación con el retiro al cual corresponde esta planificación
    public function retiro() {
        return $this->belongsTo(Retiro::class, 'retiro_id');
    }

    // Relación con el catálogo de especies (ej. Manzana, Pera, etc.)
    public function especie()
    {
        return $this->belongsTo(Catalogo::class, 'especie_id');
    }

    // Relación con el catálogo de tipos de materia prima (ej. Fruta fresca, Bins sueltos, etc.)
    public function tipoMateriaPrima()
    {
        return $this->belongsTo(Catalogo::class, 'tipo_materia_prima_id');
    }

    // Relación con el catálogo de estados de planificación (ej. Pendiente, Confirmada, Cancelada, etc.)
    public function estado()
    {
        return $this->belongsTo(Catalogo::class, 'estado_id');
    }

    // Relación con el camión asignado a la planificación
    public function camion()
    {
        return $this->belongsTo(Camion::class, 'camion_id');
    }

    // Relación con el conductor asignado al retiro
    public function conductor()
    {
        return $this->belongsTo(Conductor::class, 'conductor_id');
    }

    // Relación con el catálogo de motivos de modificación del registro
    public function motivoModificacion()
    {
        return $this->belongsTo(Catalogo::class, 'motivo_modificacion_id');
    }
}
