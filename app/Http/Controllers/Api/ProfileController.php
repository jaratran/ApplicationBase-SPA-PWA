<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
        $request->validate([
            'password' => 'required|min:8|confirmed'
        ]);

        $user = User::find(Auth::id());
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json(['success' => true]);
    }
}
