<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramaDiarioDetalle extends Model
{
    protected $table = 'programas_diarios_detalle';

    protected $fillable = [
        'programa_id',
        'retiro_id',
        'estado',
        'novedad',
        'sucursal_id',
        'comuna_id',
        'proveedor_id',
        'fecha_hora_retiro',
        'camion_id',
        'tipo_retiro_id',
        'duracion_viaje',
        'eta',
        'kilogramos_estimados',
        'producto_id',
        'especie_id',
        'bins',
    ];

    protected $casts = [
        'estado'               => 'integer',
        'novedad'              => 'integer',
        'fecha_hora_retiro'    => 'datetime',
        'eta'                  => 'datetime',
        'kilogramos_estimados' => 'integer',
        'bins'                 => 'integer',
    ];

    // Relaciones
    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaDiario::class, 'programa_id');
    }

    public function retiro(): BelongsTo
    {
        return $this->belongsTo(Retiro::class, 'retiro_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function comuna(): BelongsTo
    {
        return $this->belongsTo(Comuna::class, 'comuna_id');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'proveedor_id');
    }

    public function camion(): BelongsTo
    {
        return $this->belongsTo(Camion::class, 'camion_id');
    }

    public function tipoRetiro(): BelongsTo
    {
        return $this->belongsTo(Catalogo::class, 'tipo_retiro_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Catalogo::class, 'producto_id');
    }

    public function especie(): BelongsTo
    {
        return $this->belongsTo(Catalogo::class, 'especie_id');
    }
}
