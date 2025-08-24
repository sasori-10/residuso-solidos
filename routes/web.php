<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\ZonasSectoresController;
use App\Http\Controllers\EmpadronadosController;
use App\Http\Controllers\ProgramacionController;
use App\Http\Controllers\RecoleccionEvidenciaController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified','recoleccion.only'])->name('dashboard');

Route::middleware(['auth','recoleccion.only'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
Route::middleware(['auth', 'verified','recoleccion.only'])->group(function () {
    Route::get('/users', [UsuariosController::class, 'index'])->name('users.index');
    Route::post('/users', [UsuariosController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UsuariosController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UsuariosController::class, 'destroy'])->middleware('permission:edit.recoleccion')->name('users.destroy');

    // Rutas para Zonas y Sectores
    Route::get('/zonas-sectores', [ZonasSectoresController::class, 'index'])->name('zonas-sectores.index');
    
    // Rutas para Zonas
    Route::post('/zonas', [ZonasSectoresController::class, 'storeZona'])->name('zonas.store');
    Route::put('/zonas/{zona}', [ZonasSectoresController::class, 'updateZona'])->name('zonas.update');
    Route::delete('/zonas/{zona}', [ZonasSectoresController::class, 'destroyZona'])->middleware('permission:edit.recoleccion')->name('zonas.destroy');
    
    // Rutas para Sectores
    Route::post('/sectores', [ZonasSectoresController::class, 'storeSector'])->name('sectores.store');
    Route::put('/sectores/{sector}', [ZonasSectoresController::class, 'updateSector'])->name('sectores.update');
    Route::delete('/sectores/{sector}', [ZonasSectoresController::class, 'destroySector'])->middleware('permission:edit.recoleccion')->name('sectores.destroy');

    // Rutas para Empadronados y Tipos de Empadronados
    Route::get('/empadronados', [EmpadronadosController::class, 'index'])->name('empadronados.index');
    
    // Rutas para Tipos de Empadronados
    Route::post('/tipos-empadronados', [EmpadronadosController::class, 'storeTipo'])->name('tipos-empadronados.store');
    Route::put('/tipos-empadronados/{tipo}', [EmpadronadosController::class, 'updateTipo'])->name('tipos-empadronados.update');
    Route::delete('/tipos-empadronados/{tipo}', [EmpadronadosController::class, 'destroyTipo'])->middleware('permission:edit.recoleccion')->name('tipos-empadronados.destroy');
    
    // Rutas para Empadronados
    Route::post('/empadronados', [EmpadronadosController::class, 'store'])->name('empadronados.store');
    Route::put('/empadronados/{empadronado}', [EmpadronadosController::class, 'update'])->name('empadronados.update');
    Route::delete('/empadronados/{empadronado}', [EmpadronadosController::class, 'destroy'])->middleware('permission:edit.recoleccion')->name('empadronados.destroy');
    
    // API para obtener sectores por zona
    Route::get('/api/sectores-by-zona/{zonaId}', [EmpadronadosController::class, 'getSectoresByZona'])->name('api.sectores-by-zona');
    // API para obtener configuración de campos por tipo
    Route::get('/api/field-config-by-type/{tipoId}', [EmpadronadosController::class, 'getFieldConfigByType'])->name('api.field-config-by-type');

    // API para estadísticas
    Route::get('/api/empadronados-stats', [EmpadronadosController::class, 'getStats'])->name('api.empadronados.stats');
    Route::get('/api/usuarios-stats', [UsuariosController::class, 'getStats'])->name('api.usuarios.stats');
    Route::get('/api/zonas-stats', [ZonasSectoresController::class, 'getStats'])->name('api.zonas.stats');
    Route::get('/api/tipo-residuos-stats', [EmpadronadosController::class, 'getTipoResiduosStats'])->name('api.tipo-residuos.stats');

    // Programación: CRUD
    Route::get('/programacion', [ProgramacionController::class, 'index'])->name('programacion.index');
    Route::post('/programacion', [ProgramacionController::class, 'store'])->name('programacion.store');
    Route::put('/programacion/{programacion}', [ProgramacionController::class, 'update'])->name('programacion.update');
    Route::delete('/programacion/{programacion}', [ProgramacionController::class, 'destroy'])->middleware('permission:edit.recoleccion')->name('programacion.destroy');

    // Mis Recolecciones (usuarios rol 3)
    Route::get('/recoleccion', [RecoleccionEvidenciaController::class, 'index'])->name('recoleccion.index');
    Route::post('/recoleccion', [RecoleccionEvidenciaController::class, 'store'])->name('recoleccion.store');
});

require __DIR__.'/auth.php';
