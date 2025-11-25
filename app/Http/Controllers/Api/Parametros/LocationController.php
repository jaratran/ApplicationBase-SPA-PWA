<?php

namespace App\Http\Controllers\Api\Parametros;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Comuna;
use App\Models\Region;

class LocationController extends Controller
{
    public function obtenerRegion()
    {
        return Region::orderBy("orden", "asc")
                        ->where('id', '!=', 0)         // Ignoramos registro id=0 porque aquel es sólo para permitir crear Planificaciones Vacias
                        ->get();
    }

	public function obtenerComuna($regionId)
    {
        $region = Region::with('comunas')
                            ->where('id', '!=', 0)     // Ignoramos registro id=0 porque aquel es sólo para permitir crear Planificaciones Vacias
                            ->find($regionId);         // Carga la relación comunas definida en el modelo Region buscando la región por su ID

        return $region?->comunas ?? collect();         // Accede a las comunas de la región si existe y si $region es null retorna una colección vacía (en lugar de lanzar error).
    }
}
