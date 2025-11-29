<?php

namespace App\Http\Controllers\Api\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class ForgotPasswordController extends Controller
{
    public function sendResetLinkEmail(Request $request)
    {
        // 1) Validaciones generales: “¿está el email ingresado? ¿es un email válido?”
        $request->validate([
            'email' => 'required|email'
        ]);

        // 2) Validaciones propias: “¿existe usuario? ¿está activo? ¿está verificado?”
        $user = User::where('email', $request->email)
                    ->where('activo', 1)
                    ->first();

        if (! $user) {
            return response()->json([
                'message' => __('auth.email_not_found')
            ], 404);
        }

        if (! $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => __('auth.account_not_verified')
            ], 403);
        }

        // 3) Si todo OK, envío manual del reset link mediante el broker de contraseñas
		$response = Password::sendResetLink($request->only('email'));

        // 4) Si el mail se envió correctamente, redirijo a login con el mensaje de estado
		if ($response === Password::RESET_LINK_SENT) {
			return response()->json([
				'message' => __('auth.reset_link_sent')
			], 200);
		}

        // 5) En caso de fallo, vuelvo a la vista con el error correspondiente
        return response()->json([
            'message' => __('auth.reset_link_error')
        ], 500);
    }
}
