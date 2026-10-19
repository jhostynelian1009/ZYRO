<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

// ─── Catálogo público ─────────────────────────────────────────────────────────
Route::get('/', [CatalogController::class, 'index'])->name('home');
Route::get('/productos', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/productos/{product}', [CatalogController::class, 'show'])->name('catalog.show');

// ─── Autenticación (solo invitados) ──────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/registro', [RegisterController::class, 'create'])->name('register');
    Route::post('/registro', [RegisterController::class, 'store']);
    Route::get('/iniciar-sesion', [LoginController::class, 'create'])->name('login');
    Route::post('/iniciar-sesion', [LoginController::class, 'store']);
});

Route::post('/cerrar-sesion', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// ─── Carrito (cliente autenticado) ───────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/carrito', [CartController::class, 'index'])->name('cart.index');
    Route::post('/carrito/agregar/{productId}', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/carrito/{productId}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/carrito/{productId}', [CartController::class, 'destroy'])->name('cart.destroy');

    // ─── Pedidos del cliente ──────────────────────────────────────────────────
    Route::get('/checkout', [OrderController::class, 'create'])->name('checkout.create');
    Route::post('/pedidos', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/mi-cuenta/pedidos', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/mi-cuenta/pedidos/{order}', [OrderController::class, 'show'])->name('orders.show');

    // ─── Pago simulado ────────────────────────────────────────────────────────
    Route::get('/mi-cuenta/pedidos/{order}/pago', [PaymentController::class, 'create'])->name('orders.payment');
    Route::post('/mi-cuenta/pedidos/{order}/pago-simulado', [PaymentController::class, 'store'])->name('orders.payment.store');
});

// ─── Panel de administración ──────────────────────────────────────────────────
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/actualizaciones', [DashboardController::class, 'updates'])->name('updates');

    Route::get('/pedidos', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/pedidos/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::post('/pedidos/{order}/aprobar', [AdminOrderController::class, 'approve'])->name('orders.approve');
    Route::post('/pedidos/{order}/rechazar', [AdminOrderController::class, 'reject'])->name('orders.reject');

    Route::get('/inventario', [InventoryController::class, 'index'])->name('inventory.index');
});
