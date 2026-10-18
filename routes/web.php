<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CatalogController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogController::class, 'index'])->name('home');
Route::get('/productos', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/productos/{product}', [CatalogController::class, 'show'])->name('catalog.show');

Route::middleware('guest')->group(function () {
    Route::get('/registro', [RegisterController::class, 'create'])->name('register');
    Route::post('/registro', [RegisterController::class, 'store']);
    Route::get('/iniciar-sesion', [LoginController::class, 'create'])->name('login');
    Route::post('/iniciar-sesion', [LoginController::class, 'store']);
});

Route::post('/cerrar-sesion', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin', function () {
        return view('admin.placeholder');
    })->name('admin.dashboard');
});
