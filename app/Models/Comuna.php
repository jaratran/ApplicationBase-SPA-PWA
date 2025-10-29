<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Region;

class Comuna extends Model
{
    protected $table = 'comunas';

    protected $fillable = [
        'id',
        'nombre',
        'region_id',
        'created_at',
        'updated_at'
    ];

    /**
     * Relaciones Eloquent de Usuario con otras tablas
     *
     * @var array
     */
    public function region()
    {
        return $this->belongsTo(Region::class); // Una comuna pertenece a una region a través del campo region_id = region.id
    }
}
