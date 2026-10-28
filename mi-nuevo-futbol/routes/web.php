<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ParqueaderoController;
use App\Http\Controllers\ParqueaderoDashboardController;
use App\Http\Controllers\ParqueaderoEquipoController;
use App\Http\Controllers\ParqueaderoMensualidadController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\SuperAdminController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// ---------------------------------------------------------
// Página pública de reservas de UN negocio (identificado por su slug)
// Ejemplo: /reservas/mi-cancha
// ---------------------------------------------------------
Route::get('/reservas/{slug}', [ReservaController::class, 'index'])->name('reservas.index');
Route::get('/reservas/{slug}/disponibilidad', [ReservaController::class, 'disponibilidad'])->name('reservas.disponibilidad');
Route::get('/reservas/{slug}/disponibilidad-servicio', [ReservaController::class, 'disponibilidadServicio'])->name('reservas.disponibilidadServicio');
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
Route::middleware(['auth', 'cuenta.activa'])->group(function () {
    // -------------------------------------------------------
    // Parqueadero: ingreso de placas, salida con cobro y tarifa
    // -------------------------------------------------------
    Route::prefix('parqueadero')->name('parqueadero.')->controller(ParqueaderoController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/ingreso', 'ingresar')->name('ingresar');
        Route::post('/marca', [ConfiguracionController::class, 'marca'])->name('marca');
        Route::post('/mensualidades', [ParqueaderoMensualidadController::class, 'crear'])->name('mensualidades.crear');
        Route::post('/mensualidades/{mensualidad}/renovar', [ParqueaderoMensualidadController::class, 'renovar'])->name('mensualidades.renovar');
        Route::post('/mensualidades/{mensualidad}/estado', [ParqueaderoMensualidadController::class, 'estado'])->name('mensualidades.estado');
        Route::post('/tipos', 'crearTipo')->name('tipos.crear');
        Route::post('/tipos/{tipo}', 'actualizarTipo')->name('tipos.actualizar');
        Route::post('/tipos/{tipo}/eliminar', 'eliminarTipo')->name('tipos.eliminar');
        Route::post('/equipo', [ParqueaderoEquipoController::class, 'crear'])->name('equipo.crear');
        Route::post('/equipo/{operador}/estado', [ParqueaderoEquipoController::class, 'estado'])->name('equipo.estado');
        Route::post('/equipo/{operador}/clave', [ParqueaderoEquipoController::class, 'clave'])->name('equipo.clave');
        Route::get('/dashboard', [ParqueaderoDashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/excel', [ParqueaderoDashboardController::class, 'excel'])->name('dashboard.excel');
        Route::post('/{ingreso}/salida', 'salir')->name('salir');
        Route::post('/{ingreso}/eliminar', 'eliminar')->name('eliminar');
    });

    Route::middleware('solo.reservas')->group(function () {
    Route::get('/admin/reservas', [ReservaController::class, 'admin'])->name('reservas.admin');
    Route::get('/admin/semana', [ReservaController::class, 'semana'])->name('reservas.semana');
    Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/admin/dashboard/excel', [DashboardController::class, 'excel'])->name('dashboard.excel');
    Route::post('/admin/reservas/{reserva}/confirmar', [ReservaController::class, 'confirmar'])->name('reservas.confirmar');
    Route::post('/admin/reservas/{reserva}/cancelar', [ReservaController::class, 'cancelar'])->name('reservas.cancelar');

    // -------------------------------------------------------
    // Configuración del negocio: marca, horario, canchas/profesionales y servicios
    // -------------------------------------------------------
    Route::prefix('admin/configuracion')->name('configuracion.')->controller(ConfiguracionController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/marca', 'marca')->name('marca');
        Route::post('/horario', 'horario')->name('horario');
        Route::post('/general', 'general')->name('general');
        Route::post('/recursos', 'agregarRecurso')->name('recursos.agregar');
        Route::post('/recursos/{cancha}', 'actualizarRecurso')->name('recursos.actualizar');
        Route::post('/recursos/{cancha}/eliminar', 'eliminarRecurso')->name('recursos.eliminar');
        Route::post('/categorias', 'crearCategoria')->name('categorias.crear');
        Route::post('/categorias/{categoria}', 'actualizarCategoria')->name('categorias.actualizar');
        Route::post('/categorias/{categoria}/eliminar', 'eliminarCategoria')->name('categorias.eliminar');
        Route::post('/servicios', 'agregarServicio')->name('servicios.agregar');
        Route::post('/servicios/{servicio}', 'actualizarServicio')->name('servicios.actualizar');
        Route::post('/servicios/{servicio}/eliminar', 'eliminarServicio')->name('servicios.eliminar');
    });
    });

    // -------------------------------------------------------
    // Super admin: crea, edita y elimina negocios (canchas o salón)
    // -------------------------------------------------------
    Route::prefix('superadmin')->name('superadmin.')->group(function () {
        Route::get('/', [SuperAdminController::class, 'index'])->name('index');
        Route::get('/crear', [SuperAdminController::class, 'crear'])->name('crear');
        Route::post('/crear', [SuperAdminController::class, 'guardar'])->name('guardar');
        Route::get('/{negocio}/editar', [SuperAdminController::class, 'editar'])->name('editar');
        Route::post('/{negocio}/editar', [SuperAdminController::class, 'actualizar'])->name('actualizar');
        Route::post('/{negocio}/eliminar', [SuperAdminController::class, 'eliminar'])->name('eliminar');
        Route::post('/{negocio}/estado', [SuperAdminController::class, 'cambiarEstado'])->name('estado');
        Route::post('/{negocio}/restablecer', [SuperAdminController::class, 'restablecerClave'])->name('restablecer');
        Route::post('/{negocio}/canchas', [SuperAdminController::class, 'agregarCancha'])->name('canchas.agregar');
        Route::post('/canchas/{cancha}/eliminar', [SuperAdminController::class, 'eliminarCancha'])->name('canchas.eliminar');
        Route::post('/{negocio}/servicios', [SuperAdminController::class, 'agregarServicio'])->name('servicios.agregar');
        Route::post('/servicios/{servicio}/eliminar', [SuperAdminController::class, 'eliminarServicio'])->name('servicios.eliminar');
    });
});