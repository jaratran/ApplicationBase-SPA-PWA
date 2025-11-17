<?php

namespace App\Traits;

use Intervention\Image\Facades\Image;

trait ProcesaAvatarTrait
{
    public function procesarAvatar($file, $user)
    {
        try {
            $extension = 'jpg';
            $fileNameBase = time() . '-' . $user->id;

            $basePublic = realpath(base_path('public')) ?: $_SERVER['DOCUMENT_ROOT'];
            $pathDestino = $basePublic . '/uploads/avatar';

            // Eliminar versiones anteriores
            foreach (['_small', '_medium'] as $size) {
                $oldPath = $pathDestino . '/' . pathinfo($user->avatar, PATHINFO_FILENAME) . "$size.$extension";
                if (file_exists($oldPath)) unlink($oldPath);
            }

            // Guardar versión SMALL
            Image::make($file)
                ->fit(40, 40)
                ->encode($extension, 90)
                ->save("$pathDestino/{$fileNameBase}_small.$extension");

            // Guardar versión MEDIUM
            Image::make($file)
                ->fit(150, 150)
                ->encode($extension, 90)
                ->save("$pathDestino/{$fileNameBase}_medium.$extension");

            // Actualizar base de datos
            $user->avatar = $fileNameBase;
            $user->save();

            return ['success' => true, 'message' => 'Avatar procesado exitosamente'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
