<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

use App\Models\User;
use App\Models\Maquila;

class Solicitud extends Model
{
    protected $table = 'solicitudes';

    protected $fillable = [
        'usuario_id',
        'maquila_id',
    ];

    /**
     * Scope local de Eloquent para filtrado según el tipo de solicitante en el modelo Solicitud.
     * Encapsula la lógica de visibilidad de solicitudes según el rol del usuario solicitante.
     * Se usa en métodos index de controladores SolicitudesRetiroController y PlanificacionesRetiroController
     * para evitar repetición de lógica condicional.
     */
    public function scopeVisiblesSegunRol($query, $esSolicitantePlanta, $esSolicitanteProductor)
    {
        $usuarioId = Auth::id();
        $empresaUsuario = Auth::user()->empresa_id;

        return $query
            ->when($esSolicitantePlanta, function ($query) use ($usuarioId) {                               // 👈 Si es solicitante PLANTA
                $query->where('usuario_id', $usuarioId);                                                    //      Filtra las propias (por usuario de sesión)
            })

            ->when($esSolicitanteProductor, function ($query) use ($usuarioId, $empresaUsuario) {                   // 👈 Si es solicitante PRODUCTOR
                $query->where(function ($q) use ($usuarioId, $empresaUsuario) {
                    $q->where('usuario_id', $usuarioId)                                                             // 👈 Extrae las propias
                    ->orWhereHas('maquila', function ($subq) use ($empresaUsuario) {
                        $subq->where('empresa_id', $empresaUsuario);                                                // 👈 Y las asociadas a su empresa
                    });
                });
            });
    }

    /**
     * Usuario que creó la solicitud
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Maquila asociada a la solicitud
     */
    public function maquila()
    {
        return $this->belongsTo(Maquila::class, 'maquila_id');
    }

    /**
     * Retiros asociados a la solicitud
     */
    public function retiros()
    {
        return $this->hasMany(Retiro::class, 'solicitud_id');
    }

}
