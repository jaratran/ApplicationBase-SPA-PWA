<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Sucursal;
use App\Models\Catalogo;
use App\Models\Comuna;

class Empresa extends Model
{
    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'empresas';

    /**
     * Atributos que pueden ser asignados masivamente.
     *
     * @var array
     */
    protected $fillable = [
        'tipo_empresa_id',
        'rut_empresa',
        'razon_social',
        'direccion',
        'comuna_id',
        'telefono',
        'email_contacto',
        'telefono_contacto',
        'activo',
        'observacion_inactividad',
    ];

    /**
     * Relaciones Eloquent de Empresa con otras tablas
     */
    // Plantas de Proceso vinculadas (maquilas)
    public function plantasProcesadoras()
    {
        return $this->belongsToMany(Sucursal::class, 'maquilas', 'empresa_id', 'sucursal_id')
                    ->withTimestamps();
    }

    // Tipo de empresa (Catalogo)
    public function tipoEmpresa()
    {
        return $this->belongsTo(Catalogo::class, 'tipo_empresa_id', 'id');     // Una empresa pertenece a un tipo de empresa
    }

    // Comuna
    public function comuna()
    {
        return $this->belongsTo(Comuna::class);                  // Una empresa pertenece a una comuna
    }

    // Conductores asociados a ese transportista
    public function conductores()
    {
        return $this->hasMany(Conductor::class);
    }

}
