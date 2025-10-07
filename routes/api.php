<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\LoginController;

// =============================
//  API de Autenticación Sanctum
// =============================

// Login público
Route::post('/login', [LoginController::class, 'login']);

// Rutas protegidas por Sanctum y el nuevo middleware (para manejar LogOut sin LogIn)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', [LoginController::class, 'user']);
    Route::post('/logout', [LoginController::class, 'logout']);
});