<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use App\Traits\ProcesaAvatarTrait;

use App\Models\User;

class ProfileController extends Controller
{
    use ProcesaAvatarTrait;

    public function show()
    {
        $user = User::with([
                'rol:id,nombre',
                'sucursal:id,nombre_sucursal',
                'empresa:id,razon_social',
                'comuna:id,nombre,region_id',
                'comuna.region:id,nombre'
            ])
            ->find(Auth::id());

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|max:2048'
        ]);

        $user = User::find(Auth::id());

        $this->procesarAvatar($request->file('avatar'), $user);

        return response()->json([
            'success' => true,
            'message' => 'Avatar actualizado'
        ]);
    }

    public function updateData(Request $request)
    {
        $user = User::find(Auth::id());

        $request->validate([
            'nombre_usuario' => 'required',
            'apellidos_usuario' => 'required',
            'telefono' => 'required',
            'direccion' => 'required',
            'comuna_id' => 'required|integer'
        ]);

        $user->update($request->all());

        return response()->json(['success' => true]);
    }

	public function updatePassword(Request $request)
	{
		// Validación equivalente a EcoRuta pero en formato API
		$validator = Validator::make($request->all(), [
			'password' => [
				'required',
				'string',
				'min:8',
				'regex:/[a-z]/',   // minúscula
				'regex:/[A-Z]/',   // mayúscula
				'regex:/[0-9]/',   // número
				'same:password_confirmation'
			],
			'password_confirmation' => [
				'required',
				'string'
			]
		]);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'message' => 'No se pudo actualizar la contraseña.',
				'errors'  => $validator->errors()
			], 422);
		}

		try {
			$user = $request->user(); // auth:sanctum

			$user->password = Hash::make($request->password);
			$user->save();

			return response()->json([
				'success' => true,
				'message' => 'Contraseña actualizada correctamente.'
			]);

		} catch (\Throwable $e) {

			Log::error('❌ Error al actualizar contraseña (API Calidad)', [
				'error' => $e->getMessage(),
			]);

			return response()->json([
				'success' => false,
				'message' => 'Ocurrió un error inesperado.'
			], 500);
		}
	}

}
