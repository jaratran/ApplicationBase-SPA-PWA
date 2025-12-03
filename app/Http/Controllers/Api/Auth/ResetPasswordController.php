<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ResetPasswordController extends Controller
{
    public function reset(Request $request)
    {
        // 1. Validación de campos
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        // 2. Intento de reset usando el Password Broker
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),

            function ($user) use ($request) {
                $user->password = Hash::make($request->password);
                $user->save();
            }
        );

        // 3. Respuestas según estado
        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'PASSWORD_RESET'
            ], 200);
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }
}
