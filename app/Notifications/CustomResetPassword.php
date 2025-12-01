<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use App\Mail\ResetPasswordMailable;

class CustomResetPassword extends Notification
{
    public $token;

    /**
     * Crear una nueva instancia de la notificación.
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Determina los canales por los que se enviará la notificación.
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Construye el mensaje que será enviado por correo.
     */
	public function toMail($notifiable)
	{
		$frontend = config('app.frontend_url'); // FRONTEND_URL=https://calidad.hp-notebook.cl
		$resetUrl = "{$frontend}/reset-password/{$this->token}?email=" . urlencode($notifiable->getEmailForPasswordReset());

        // Definimos vista por defecto
		$vista = 'emails.reset-password';

		return (new ResetPasswordMailable($notifiable, $resetUrl, $vista))->to($notifiable->email);
	}
}
