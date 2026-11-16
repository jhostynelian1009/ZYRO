<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\ManualOrderController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ReturnRequestController as AdminReturnRequestController;
use App\Http\Controllers\AnalyticsInteractionController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CustomerReturnRequestController;
use App\Http\Controllers\CustomizationImageController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

// ─── Catálogo público ─────────────────────────────────────────────────────────
Route::get('/', [CatalogController::class, 'index'])->name('home');
Route::get('/productos', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/productos/{product}', [CatalogController::class, 'show'])->name('catalog.show');
Route::get('/productos/{product}/personalizar', [CatalogController::class, 'customize'])->name('catalog.customize');
Route::post('/analitica/interaccion', [AnalyticsInteractionController::class, 'store'])->name('analytics.interaction');

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
    Route::get('/personalizaciones/{item}/imagen', [CustomizationImageController::class, 'show'])->name('customizations.image');

    // ─── Pedidos del cliente ──────────────────────────────────────────────────
    Route::get('/checkout', [OrderController::class, 'create'])->name('checkout.create');
    Route::post('/pedidos', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/mi-cuenta/pedidos', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/mi-cuenta/pedidos/{order}', [OrderController::class, 'show'])->name('orders.show');

    // ─── Pago simulado ────────────────────────────────────────────────────────
    Route::get('/mi-cuenta/pedidos/{order}/pago', [PaymentController::class, 'create'])->name('orders.payment');
    Route::post('/mi-cuenta/pedidos/{order}/pago-simulado', [PaymentController::class, 'store'])->name('orders.payment.store');

    // ─── Cambios y Devoluciones (Autoservicio Cliente) ──────────────────────────
    Route::get('/mi-cuenta/pedidos/{order}/solicitar-cambio', [CustomerReturnRequestController::class, 'create'])->name('customer.returns.create');
    Route::post('/mi-cuenta/pedidos/{order}/solicitar-cambio', [CustomerReturnRequestController::class, 'store'])->name('customer.returns.store');
    Route::get('/mi-cuenta/solicitudes', [CustomerReturnRequestController::class, 'index'])->name('customer.returns.index');
    Route::get('/mi-cuenta/solicitudes/{returnRequest}', [CustomerReturnRequestController::class, 'show'])->name('customer.returns.show');
});

// ─── Panel de administración ──────────────────────────────────────────────────
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/actualizaciones', [DashboardController::class, 'updates'])->name('updates');

    // Ventas manuales / asistidas (K-001)
    Route::get('/ventas/crear', [ManualOrderController::class, 'create'])->name('manual-orders.create');
    Route::get('/pedidos/crear', [ManualOrderController::class, 'create'])->name('orders.create');
    Route::post('/ventas', [ManualOrderController::class, 'store'])->name('manual-orders.store');

    Route::get('/pedidos', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/pedidos/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::post('/pedidos/{order}/aprobar', [AdminOrderController::class, 'approve'])->name('orders.approve');
    Route::post('/pedidos/{order}/rechazar', [AdminOrderController::class, 'reject'])->name('orders.reject');
    Route::post('/pedidos/{order}/interacciones', [AdminOrderController::class, 'storeInteraction'])->name('orders.interactions.store');
    Route::post('/pedidos/{order}/continuar', [AdminOrderController::class, 'continueOrder'])->name('orders.continue');

    Route::get('/inventario', [InventoryController::class, 'index'])->name('inventory.index');

    // Cambios y Devoluciones (K-004)
    Route::get('/solicitudes', [AdminReturnRequestController::class, 'index'])->name('returns.index');
    Route::get('/solicitudes/{returnRequest}', [AdminReturnRequestController::class, 'show'])->name('returns.show');
    Route::post('/solicitudes/{returnRequest}/iniciar-inspeccion', [AdminReturnRequestController::class, 'startInspection'])->name('returns.start-inspection');
    Route::post('/solicitudes/{returnRequest}/resolver', [AdminReturnRequestController::class, 'resolve'])->name('returns.resolve');

    // ─── Analítica y mapas de calor ──────────────────────────────────────────
    Route::get('/analitica', [AnalyticsController::class, 'index'])->name('analytics.index');
});


// ─── Webhooks de pago (F4) ───────────────────────────────────────────────────
// Sin middleware CSRF ni autenticación — son llamadas POST de proveedores externos.
Route::post('/webhooks/payment/{gateway}', [PaymentWebhookController::class, 'receive'])
    ->name('webhooks.payment');
