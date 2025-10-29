<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use App\Models\Catalogo;
use App\Models\Empresa;
use App\Models\Sucursal;
use App\Models\Comuna;

use App\Models\Solicitud;
use App\Models\RetiroComentario;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'rut_usuario',
        'nombre_usuario',
        'apellidos_usuario',
        'rol_id',
        'empresa_id',
        'sucursal_id',
        'email',
        'telefono',
        'comuna_id',
        'direccion',
        'avatar',
        'es_admin',
        'activated',
        'fecha_login',
        'remember_token',
        'password',
        'activo',
        'observacion_inactividad',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts modernos (Laravel 12+)
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected function casts(): array
    {
        return [
            'rol_id'                => 'integer', // Esto se agrega por comparaciones de rol en el envío de correo de bienvenida (en la asignación de texto-email se usa 'match' para subordinarla al rol).
            'email_verified_at'     => 'datetime',
            'password'              => 'hashed',
        ];
    }

    /**
     * Método getNombreCompletoAttribute (->nombre_completo) en Conductor para combinar nombre y apellido fácilmente.
     * 
     */
    public function getNombreCompletoAttribute()
    {
        return "{$this->nombre_usuario} {$this->apellidos_usuario}";
    }

    /**
     * Obtener el email que se usará para la verificación.
     *
     * @return string
     */
    public function getEmailForVerification()
    {
        return $this->email;
    }
  
    /**
     * Cada vez que se ejecute el flujo de reset (ya sea con el broker o con el trait),
     * Laravel llamará a notificación CustomResetPassword en lugar de usar la nativa.
     *
     * @return string
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\CustomResetPassword($token));
    }

    /**
     * Relaciones Eloquent de Usuario con otras tablas
     *
     * @var array
     */
    public function rol()
    {
        return $this->belongsTo(Catalogo::class); // Un usuario tiene un rol a través campo users.rol_id = catalogos.id
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class); // Un usuario con rol='solicitante productor' trabaja en una empresa mediante users.empresa_id = empresas.id
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class); // Un usuario con rol='solicitante planta' trabaja en una sucursal mediante users.sucursal_id = sucursal.id
    }

    public function comuna()
    {
        return $this->belongsTo(Comuna::class); // Una usuario posee dirección en una comuna a través del campo comuna_id = id
    }

    /**
     * Solicitudes creadas por el usuario
     */
    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class, 'usuario_id');
    }

    /**
     * Comentarios de retiro realizados por el usuario.
     */
    public function comentariosRetiros()
    {
        return $this->hasMany(RetiroComentario::class, 'usuario_id');
    }
}
