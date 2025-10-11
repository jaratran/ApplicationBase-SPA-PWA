<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesignParameter extends Model
{
    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'design_parameters';

    /**
     * Atributos que se pueden asignar masivamente.
     *
     * @var array
     */
    protected $fillable = [
        'titulo_design',
        'logo_design',
        'emblema_design',
        'favicon_design',
        'fondo_pantalla_design',
        'custom_primary',
        'custom_secondary',
        'custom_success',
        'custom_warning',
        'custom_danger',
        'custom_info'
    ];
}
