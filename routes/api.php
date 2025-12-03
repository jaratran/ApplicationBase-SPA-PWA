<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Auth\ResetPasswordController;

use App\Http\Controllers\Api\DesignParameterController;
use App\Http\Controllers\Api\PanelController;
use App\Http\Controllers\Api\ProfileController;

use App\Http\Controllers\Api\Parametros\LocationController;


// =============================
//  API de Autenticación Sanctum
// =============================

// Login público
Route::post('/login', [LoginController::class, 'login']);
Route::get('/design-parameters', [DesignParameterController::class, 'index']);

// POST /api/forgot-password
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail']);
Route::post('/reset-password', [ResetPasswordController::class, 'reset']);

// Rutas protegidas por Sanctum y el nuevo middleware (para manejar LogOut sin LogIn)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', [LoginController::class, 'user']);
    Route::post('/logout', [LoginController::class, 'logout']);

    Route::get('/panel/datos', [PanelController::class, 'datos']);

    Route::get('/perfil', [ProfileController::class, 'show']);
    Route::post('/perfil/avatar', [ProfileController::class, 'updateAvatar']);
    Route::post('/perfil/update', [ProfileController::class, 'updateData']);
    Route::post('/perfil/password', [ProfileController::class, 'updatePassword']);

	Route::get('/regiones', [LocationController::class, 'obtenerRegion']);
	Route::get('/regiones/{regionId}/comunas', [LocationController::class, 'obtenerComuna']);
});
