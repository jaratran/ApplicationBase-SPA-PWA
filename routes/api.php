<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\DesignParameterController;
use App\Http\Controllers\Api\PanelController;

// =============================
//  API de Autenticación Sanctum
// =============================

// Login público
Route::post('/login', [LoginController::class, 'login']);
Route::get('/design-parameters', [DesignParameterController::class, 'index']);

// Rutas protegidas por Sanctum y el nuevo middleware (para manejar LogOut sin LogIn)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', [LoginController::class, 'user']);
    Route::post('/logout', [LoginController::class, 'logout']);

    Route::get('/panel/datos', [PanelController::class, 'datos']);
});