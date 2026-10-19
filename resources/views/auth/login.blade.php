@extends('layouts.store')

@section('title', 'Iniciar sesión — ZYRO')
@section('meta_description', 'Inicia sesión en tu cuenta ZYRO para gestionar tus pedidos y acceder a la colección de moda urbana con tecnología térmica.')

@section('content')
<div class="auth-page-wrapper">
    <div class="auth-card">
        <header class="auth-header">
            <span class="auth-kicker">Acceso a Clientes</span>
            <h1 class="auth-title">Iniciar sesión</h1>
            <p class="auth-subtitle">Ingresa tus credenciales para acceder a tu cuenta.</p>
        </header>

        <form method="POST" action="{{ route('login') }}" class="auth-form" novalidate>
            @csrf

            <div class="form-group">
                <label for="email" class="form-label">Correo Electrónico</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="nombre@ejemplo.com"
                    class="form-input @error('email') is-invalid @enderror"
                >
                @error('email')
                    <p class="form-error">
                        <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" style="flex-shrink:0;">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <div class="form-group">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <label for="password" class="form-label">Contraseña</label>
                </div>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                    class="form-input @error('password') is-invalid @enderror"
                >
                @error('password')
                    <p class="form-error">
                        <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" style="flex-shrink:0;">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <div class="form-checkbox-row">
                <label class="form-checkbox-label">
                    <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    <span>Recordarme en este equipo</span>
                </label>
            </div>

            <button type="submit" class="btn-auth-submit">Entrar</button>
        </form>

        <footer class="auth-card-footer">
            ¿No tienes cuenta?
            <a href="{{ route('register') }}">Regístrate</a>
        </footer>
    </div>
</div>
@endsection
