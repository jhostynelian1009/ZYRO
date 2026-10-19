@extends('layouts.store')

@section('title', 'Crear cuenta — ZYRO')
@section('meta_description', 'Regístrate en ZYRO para adquirir prendas de la colección cápsula con termorregulación activa y modelos esenciales.')

@section('content')
<div class="auth-page-wrapper">
    <div class="auth-card">
        <header class="auth-header">
            <span class="auth-kicker">Registro de Clientes</span>
            <h1 class="auth-title">Crear cuenta</h1>
            <p class="auth-subtitle">El registro público crea únicamente una cuenta de cliente.</p>
        </header>

        <form method="POST" action="{{ route('register') }}" class="auth-form" novalidate>
            @csrf

            <div class="form-group">
                <label for="name" class="form-label">Nombre Completo</label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name') }}"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Tu nombre completo"
                    class="form-input @error('name') is-invalid @enderror"
                >
                @error('name')
                    <p class="form-error">
                        <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" style="flex-shrink:0;">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Correo Electrónico</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
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
                <label for="password" class="form-label">Contraseña</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="new-password"
                    placeholder="Mínimo 8 caracteres"
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

            <div class="form-group">
                <label for="password_confirmation" class="form-label">Confirmar Contraseña</label>
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    required
                    autocomplete="new-password"
                    placeholder="Repite tu contraseña"
                    class="form-input"
                >
            </div>

            <button type="submit" class="btn-auth-submit">Registrarme</button>
        </form>

        <footer class="auth-card-footer">
            ¿Ya tienes cuenta?
            <a href="{{ route('login') }}">Inicia sesión</a>
        </footer>
    </div>
</div>
@endsection
