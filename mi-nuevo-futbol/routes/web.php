<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\SuperAdminController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// ---------------------------------------------------------
// Página pública de reservas de UN negocio (identificado por su slug)
// Ejemplo: /reservas/mi-cancha
// ---------------------------------------------------------
Route::get('/reservas/{slug}', [ReservaController::class, 'index'])->name('reservas.index');
Route::get('/reservas/{slug}/disponibilidad', [ReservaController::class, 'disponibilidad'])->name('reservas.disponibilidad');
Route::post('/reservas/{slug}', [ReservaController::class, 'store'])->name('reservas.store');

// ---------------------------------------------------------
// Login
// ---------------------------------------------------------
Route::get('/login', [AuthController::class, 'mostrarLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.intentar');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ---------------------------------------------------------
// Panel privado (protegido: exige haber iniciado sesión).
// Cada negocio solo ve sus propias canchas y reservas.
// ---------------------------------------------------------
Route::middleware('auth')->group(function () {
    Route::get('/admin/reservas', [ReservaController::class, 'admin'])->name('reservas.admin');
    Route::get('/admin/semana', [ReservaController::class, 'semana'])->name('reservas.semana');
    Route::post('/admin/reservas/{reserva}/confirmar', [ReservaController::class, 'confirmar'])->name('reservas.confirmar');
    Route::post('/admin/reservas/{reserva}/cancelar', [ReservaController::class, 'cancelar'])->name('reservas.cancelar');

    // -------------------------------------------------------
    // Super admin: crea, edita y elimina negocios y sus canchas
    // -------------------------------------------------------
    Route::prefix('superadmin')->name('superadmin.')->group(function () {
        Route::get('/', [SuperAdminController::class, 'index'])->name('index');
        Route::get('/crear', [SuperAdminController::class, 'crear'])->name('crear');
        Route::post('/crear', [SuperAdminController::class, 'guardar'])->name('guardar');
        Route::get('/{negocio}/editar', [SuperAdminController::class, 'editar'])->name('editar');
        Route::post('/{negocio}/editar', [SuperAdminController::class, 'actualizar'])->name('actualizar');
        Route::post('/{negocio}/eliminar', [SuperAdminController::class, 'eliminar'])->name('eliminar');
        Route::post('/{negocio}/canchas', [SuperAdminController::class, 'agregarCancha'])->name('canchas.agregar');
        Route::post('/canchas/{cancha}/eliminar', [SuperAdminController::class, 'eliminarCancha'])->name('canchas.eliminar');
    });
});