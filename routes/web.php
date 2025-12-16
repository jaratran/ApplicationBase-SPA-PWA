<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

// Entry point estático de la SPA
Route::get('/', function () {
    return response(
        File::get(public_path('/build/index.html')),
        200,
        ['Content-Type' => 'text/html']
    );
});

// SPA Fallback — cualquier ruta que no sea /api/* carga index.html
Route::get('/{any}', function () {
    return response(
        File::get(public_path('/build/index.html')),
        200,
        ['Content-Type' => 'text/html']
    );
})->where('any', '^(?!api|assets|build|config|favicon\.ico).*$');

// Linea para usar cuando la aplicación crezca o si más adelante agregamos Debugbar o Telescope.
//})->where('any', '^(?!api|storage|telescope|_debugbar).*$');
