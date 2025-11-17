<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    protected $table = 'regiones';

    protected $fillable = [
        'id',
        'nombre',
        'orden'
    ];

    /**
     * Relaciones Eloquent de Regiones con otras tablas
     *
     * @var array
     */
    public function comunas()
    {
        return $this->hasMany(Comuna::class)->orderBy('nombre');
    }
}
