<?php

use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TicketVentaController;
use App\Http\Controllers\VentaAnuladaController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'role:admin'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
 | Ventas: disponibles para admin y vendedor.
 */
Route::middleware('auth')->group(function () {
    Route::resource('ventas', VentaController::class)->only(['index', 'create', 'store', 'show']);

    // La anulación se modela como su propio recurso; la policy decide quién puede.
    Route::post('/ventas/{venta}/anular', [VentaAnuladaController::class, 'store'])
        ->name('ventas.anular');

    Route::get('/ventas/{venta}/ticket', [TicketVentaController::class, 'show'])
        ->name('ventas.ticket');
});

/*
 | Sección de administración: solo usuarios con rol 'admin'.
 */
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('categorias', CategoriaController::class)->except(['show']);
    Route::resource('productos', ProductoController::class)->except(['show']);
});

require __DIR__.'/auth.php';
