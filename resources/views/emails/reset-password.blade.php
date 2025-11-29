<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer Contraseña</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f5f5f5; padding: 20px; color: #333;">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <table width="600" style="background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
                    <tr>
                        <td>
                            <!--
                                <img 
                                    src="{{ asset('config/' . $designParameter->emblema_design) }}" 
                                    alt="Emblema {{ $designParameter->titulo_design }}" 
                                    style="max-width:180px; max-height:120px; display:block; margin-bottom:20px;"
                                >
                            -->

                            <h2 style="color: {{ $designParameter->custom_primary }};">
                                Restablecimiento de Contraseña <strong>La Portada</strong>
                            </h2>
                            <p>Estimado/a <strong>{{ $user->nombre_usuario }} {{ $user->apellidos_usuario }}</strong>,</p>

                            <p>Recibimos una solicitud para restablecer tu contraseña.</p>

                            <p>Haz clic en el botón para definir una nueva:</p>
                            <p>
                                <a href="{{ $resetUrl  }}" style="display: inline-block; padding: 10px 20px; background-color: {{ $designParameter->custom_primary }}; color: white; text-decoration: none; border-radius: 5px;">
                                    Definir nueva contraseña
                                </a>
                            </p>

                            <p>Si no realizaste esta solicitud, puedes ignorar este mensaje.</p>

                            <p>Si tienes alguna duda o necesitas asistencia, no dudes en comunicarte con nuestro equipo de soporte al correo <strong>{{ $operationalParameter->support_email }}</strong> o al teléfono <strong>{{ $operationalParameter->support_telefono }}</strong>.

                            <p style="margin-top: 40px;">Saludos cordiales,<br><strong>Equipo de La Portada</strong></p>

                            <hr>
                            <p class="mb-0"><small>Este correo fue generado automáticamente por el Sistema de Planificación EcoRuta.</small></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>