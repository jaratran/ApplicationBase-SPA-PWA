<?php

use Illuminate\Support\Facades\Route;

// Este Fallback es vestigio de cuando era aplicación Laravel pura
// Route::get('/', function () {
//     return view('welcome');
// });

// SPA Fallback — cualquier ruta que no sea /api/* carga la aplicación Vue
Route::get('/{any}', function () {
    return view('frontend'); // Blade que carga el SPA
})->where('any', '^(?!api).*$');

// Linea para usar cuando la aplicación creazca o si más adelante agregamos Debugbar o Telescope.
//})->where('any', '^(?!api|storage|telescope|_debugbar).*$');