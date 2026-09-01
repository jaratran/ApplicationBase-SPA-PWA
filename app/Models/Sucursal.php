<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Empresa;
use App\Models\Comuna;
use App\Models\Catalogo;

class Sucursal extends Model
{
    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'sucursales';

    /**
     * Atributos que pueden ser asignados masivamente.
     *
     * @var array
     */
    protected $fillable = [
        'zona_id',
        'nombre_sucursal',
        'tipo_sucursal_id',
        'comuna_id',
        'telefono',
        'email',
        'activo',
        'observacion_inactividad',
    ];

    /**
     * Relaciones Eloquent de Sucursal con otras tablas
     */

    // Productoras de Materia Prima vinculadas (maquilas) 
    public function empresasAtendidas()
    {
        return $this->belongsToMany(Empresa::class, 'maquilas', 'sucursal_id', 'empresa_id')
                    ->withTimestamps();
    }

    // Zona (Catalogo)
    public function zona()
    {
        return $this->belongsTo(Catalogo::class, 'zona_id', 'id');             // Una sucursal pertenece a una zona
    }

    // Tipo de sucursal (Catalogo)
    public function tipoSucursal()
    {
        return $this->belongsTo(Catalogo::class, 'tipo_sucursal_id', 'id');    // Una sucursal pertenece a un tipo de sucursal
    }

    // Comuna
    public function comuna()
    {
        return $this->belongsTo(Comuna::class);                  // Una sucursal pertenece a una comuna  
    }
}
