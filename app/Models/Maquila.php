<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Empresa;
use App\Models\Sucursal;

class Maquila extends Model
{
    // Tabla explícita (opcional si sigue convención)
    protected $table = 'maquilas';

    // Atributos asignables masivamente
    protected $fillable = [
        'empresa_id',
        'sucursal_id',
        'fecha_inicio',
        'activo',
        'observaciones',
    ];

    // Relaciones
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    /**
     * Solicitudes asociadas a esta maquila
     */
    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class, 'maquila_id');
    }

}
