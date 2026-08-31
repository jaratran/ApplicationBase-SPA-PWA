<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Empresa;
use App\Models\Conductor;
use App\Models\Catalogo;

class Camion extends Model
{
    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'camiones';

    /**
     * Atributos que pueden ser asignados masivamente.
     *
     * @var array
     */
    protected $fillable = [
        'empresa_id',
        'conductor_id',
        'tipo_camion_id',
        'patente',
        'patente_rampla',
        'arrendado',
        'rendimiento_optimo',
        'activo',
        'observacion_inactividad',
    ];

    /**
     * Relaciones Eloquent de Camion con otras tablas
     */

    // Empresa propietaria del camión
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id', 'id');          // Un camión es propiedad de una empresa
    }

    // Conductor por defecto del camión
    public function conductor()
    {
        return $this->belongsTo(Conductor::class, 'conductor_id', 'id');      // Un camión tiene un conductor por defecto
    }

    // Tipo de camión (list_parameters)
    public function tipoCamion()
    {
        return $this->belongsTo(Catalogo::class, 'tipo_camion_id', 'id');   // Un camión pertenece a un tipo de camión
    }

}
