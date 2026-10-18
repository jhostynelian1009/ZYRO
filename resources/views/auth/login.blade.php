@extends('layouts.store')

@section('title', 'Iniciar sesión — ZYRO')

@section('content')
    <section class="mx-auto max-w-md px-4 py-16">
        <h1 class="font-serif text-4xl mb-8">Iniciar sesión</h1>
        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf
            <div>
                <label for="email" class="block text-sm mb-1">Correo</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="w-full border border-zyro-border px-3 py-2">
                @error('email')
                    <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="password" class="block text-sm mb-1">Contraseña</label>
                <input id="password" name="password" type="password" required class="w-full border border-zyro-border px-3 py-2">
            </div>
            <label class="flex items-center gap-2 text-sm text-zyro-muted">
                <input type="checkbox" name="remember">
                Recordarme
            </label>
            <button type="submit" class="w-full bg-zyro text-white py-3 uppercase tracking-wide text-sm">Entrar</button>
        </form>
        <p class="mt-6 text-sm text-zyro-muted">
            ¿No tienes cuenta?
            <a href="{{ route('register') }}" class="text-zyro underline">Regístrate</a>
        </p>
    </section>
@endsection
