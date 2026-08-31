<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

use App\Models\RetiroHistorial;
use App\Models\RetiroComentario;

class Retiro extends Model
{
    use HasFactory;

    protected $table = 'retiros';

    protected $casts = [
        'fecha_retiro' => 'datetime',       // Para usar format sin tener que invocar a Carbon explicitamente
    ];

    protected $fillable = [
        'solicitud_id',
        'fecha_retiro',
        'tipo_retiro_id',
        'kilogramos_estimados',
        'requiere_reposicion',
        'cantidad_bins',
        'estado_id',
        'activo',
        'comentario_anulacion',
    ];


    /**
     * Método: Para guardar en el historial una copia del actual retiro.
     */
    public function guardarHistorial(string $motivo = null, int $usuarioId = null): void
    {
        RetiroHistorial::create([
            'retiro_id'           => $this->id,
            'fecha_retiro'        => $this->fecha_retiro,
            'tipo_retiro_id'      => $this->tipo_retiro_id,
            'kilogramos_estimados'=> $this->kilogramos_estimados,
            'requiere_reposicion' => $this->requiere_reposicion,
            'cantidad_bins'       => $this->cantidad_bins,
            'estado_id'           => $this->estado_id,
            'activo'              => $this->activo,
            'usuario_id'          => $usuarioId ?? Auth::id(),
            'motivo_cambio'       => $motivo,
        ]);
    }

    /**
     * Método: Para guardar en los comentarios el actual comentario.
     */
    public function guardarComentario(string $comentario, int $usuarioId = null): void
    {
        RetiroComentario::create([
            'retiro_id'   => $this->id,
            'usuario_id'  => $usuarioId ?? Auth::id(),
            'comentario'  => $comentario,
            'created_at'  => now(),
        ]);
    }

    /**
     * Método: Para crear la planificación inicial asociada al retiro.
     */
    public function crearPlanificacionInicial(): void
    {
        if ($this->planificacion) {
            return; // Ya existe, no se debe duplicar
        }

        // El registro de planificación se debe crear vacío, limpio, en blanco, etc.
        Planificacion::create([
            'retiro_id'               => $this->id,                                     // ✔️ FK a retiros.id, protegido con ON DELETE CASCADE
            'fecha_hora_planificada'  => Carbon::create(1970, 1, 1, 0, 0, 0),           // ✔️ Obligatorio, valor válido
            'duracion_viaje'          => '00:00',                                       // ✔️ Obligatorio, valor válido
            'hora_llegada_estimada'   => Carbon::create(1970, 1, 1, 0, 0, 0),           // ✔️ Obligatorio, valor válido
            'especie_id'              => config('constantes.CATALOGO_NO_ESPECIFICADO'), // ⚠️ FK -> catalogos.id
            'tiene_restriccion'       => false,
            'tipo_materia_prima_id'   => config('constantes.CATALOGO_NO_ESPECIFICADO'), // ⚠️ FK -> catalogos.id
            'camion_id'               => 0,                                             // ⚠️ FK -> camiones.id
            'patente_rampla'          => null,
            'cantidad_bins_reponer'   => 0,
            'conductor_id'            => 0,                                             // ⚠️ FK -> conductores.id
            'motivo_modificacion_id'  => config('constantes.CATALOGO_NO_ESPECIFICADO'), // ⚠️ FK -> catalogos.id
            'estado_id'               => config('constantes.CATALOGO_NO_ESPECIFICADO'), // ⚠️ FK -> catalogos.id
            'activo'                  => true,
        ]);
    }

    /**
     * Relación: Un retiro pertenece a una solicitud.
     */
    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class);
    }

    /**
     * Relación: El tipo de retiro proviene del catálogo.
     */
    public function tipoRetiro()
    {
        return $this->belongsTo(Catalogo::class, 'tipo_retiro_id');
    }

    /**
     * Relación: El estado proviene del catálogo.
     */
    public function estado()
    {
        return $this->belongsTo(Catalogo::class, 'estado_id');
    }

    /**
     * Relación: El retiro posee registros en el historial de retiros.
     */
    public function historial()
    {
        return $this->hasMany(RetiroHistorial::class, 'retiro_id');
    }

    /**
     * Comentarios asociados a este retiro.
     */
    public function comentarios()
    {
        return $this->hasMany(RetiroComentario::class)->orderByDesc('id');
    }

    /**
     * Relación uno a uno: cada retiro tiene UNA planificación
     */
    public function planificacion()
    {
        return $this->hasOne(Planificacion::class);
    }
}
