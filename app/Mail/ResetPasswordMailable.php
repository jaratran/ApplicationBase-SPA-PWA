<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use App\Models\OperationalParameter;

class ResetPasswordMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $resetUrl;
    public $vista;

    public function __construct($user, $resetUrl, $vista)
    {
        $this->user     = $user;
        $this->resetUrl = $resetUrl;
        $this->vista    = $vista;
    }

    public function build()
    {
        $email = $this->subject(__('auth.password_reset_subject'))
                      ->view($this->vista)
                      ->with([
                                'user'     => $this->user,
                                'resetUrl' => $this->resetUrl,
                                'operationalParameter' => OperationalParameter::first(),
                            ]);

        // Parámetros operacionales desde base de datos
        $params = OperationalParameter::first();

        if ($params->audit_email_enabled === true && !empty($params->audit_email)) {
            $email->bcc($params->audit_email);
        }

        return $email;
    }
}
