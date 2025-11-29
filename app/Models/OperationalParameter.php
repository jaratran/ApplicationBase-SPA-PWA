<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperationalParameter extends Model
{
    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'operational_parameters';

    protected $casts = [
        'auto_emit_daily_program'       => 'boolean',
        'audit_email_enabled'           => 'boolean',
        'notify_admins_as_coordinators' => 'boolean',
        'allow_profile_editing'         => 'boolean',
    ];

    /**
     * Atributos que se pueden asignar masivamente.
     *
     * @var array
     */
    protected $fillable = [
        'support_email',
        'support_telefono',                   // 📌 Nuevo campo
        'daily_program_execution_time',
        'auto_emit_daily_program',
        'audit_email',
        'audit_email_enabled',
        'notify_admins_as_coordinators',      // 📌 Nuevo campo
        'verification_expiration_time',       // 📌 Nuevo campo
        'allow_profile_editing',
        'average_truck_speed',
    ];
}
