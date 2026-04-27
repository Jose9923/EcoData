@extends('layouts.guest')

@section('content')
<div class="mx-auto" style="max-width: 32rem;">
    <div class="text-center mb-4">
<div class="mx-auto mb-4 d-flex align-items-center justify-content-center"
     style="width: 9rem; height: auto; overflow: hidden; border-radius: 1.5rem;">
    <img src="{{ asset('img/logo-login.jpeg') }}"
         alt="Logo EcoData"
         style="width: 100%; height: auto; object-fit: contain; border-radius: 1.5rem;">
</div>

        <p class="small fw-semibold text-uppercase mb-2" style="letter-spacing: .24em; color: #86efac;">
            EcoData
        </p>
        <h1 class="display-6 fw-bold text-white mb-2">Iniciar sesión</h1>
        <p class="text-white-50 mb-0">
            Ingresa tu correo y contraseña para acceder a la plataforma.
        </p>
    </div>

    <div class="rounded-4 border bg-white shadow-lg p-4 p-md-5">
        @if (session('status'))
            <div class="alert alert-success rounded-4 mb-4">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="mb-4">
                <label for="email" class="form-label fw-semibold text-dark">Correo electrónico</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="tu-correo@institucion.edu"
                    class="form-control form-control-lg rounded-4 @error('email') is-invalid @enderror"
                >
                @error('email')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label for="password" class="form-label fw-semibold text-dark">Contraseña</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Ingresa tu contraseña"
                    class="form-control form-control-lg rounded-4 @error('password') is-invalid @enderror"
                >
                @error('password')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember">
                    <label class="form-check-label text-secondary" for="remember">
                        Recordar sesión
                    </label>
                </div>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-decoration-none fw-medium" style="color: #047857;">
                        ¿Olvidaste tu contraseña?
                    </a>
                @endif
            </div>

            <button type="submit" class="btn w-100 text-white rounded-4 py-3 fw-semibold"
                    style="background-color: #059669; border-color: #059669;">
                Entrar a EcoData
            </button>
        </form>
    </div>
</div>
@endsection