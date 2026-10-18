@extends('layouts.store')

@section('title', 'Crear cuenta — ZYRO')

@section('content')
    <section class="mx-auto max-w-md px-4 py-16">
        <h1 class="font-serif text-4xl mb-2">Crear cuenta</h1>
        <p class="mb-8 text-sm text-zyro-muted">El registro público crea únicamente una cuenta de cliente.</p>
        <form method="POST" action="{{ route('register') }}" class="space-y-5">
            @csrf
            <div>
                <label for="name" class="block text-sm mb-1">Nombre</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus class="w-full border border-zyro-border px-3 py-2">
                @error('name')
                    <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="email" class="block text-sm mb-1">Correo</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required class="w-full border border-zyro-border px-3 py-2">
                @error('email')
                    <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="password" class="block text-sm mb-1">Contraseña</label>
                <input id="password" name="password" type="password" required class="w-full border border-zyro-border px-3 py-2">
                @error('password')
                    <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm mb-1">Confirmar contraseña</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required class="w-full border border-zyro-border px-3 py-2">
            </div>
            <button type="submit" class="w-full bg-zyro text-white py-3 uppercase tracking-wide text-sm">Registrarme</button>
        </form>
        <p class="mt-6 text-sm text-zyro-muted">
            ¿Ya tienes cuenta?
            <a href="{{ route('login') }}" class="text-zyro underline">Inicia sesión</a>
        </p>
    </section>
@endsection
