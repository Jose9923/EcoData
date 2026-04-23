@extends('layouts.guest')

@section('content')
<div class="mx-auto" style="max-width: 34rem;">
    <div class="text-center mb-4">
        <p class="small fw-semibold text-uppercase mb-2" style="letter-spacing: .24em; color: #86efac;">
            EcoData
        </p>
        <h1 class="display-6 fw-bold text-white mb-2">Crear cuenta</h1>
        <p class="text-white-50 mb-0">
            Registra un nuevo usuario para acceder a la plataforma.
        </p>
    </div>

    <div class="rounded-4 border bg-white shadow-lg p-4 p-md-5">
        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div class="mb-4">
                <label for="name" class="form-label fw-semibold">Nombre</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}"
                    class="form-control form-control-lg rounded-4 @error('name') is-invalid @enderror" required autofocus>
                @error('name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            <div class="mb-4">
                <label for="email" class="form-label fw-semibold">Correo electrónico</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                    class="form-control form-control-lg rounded-4 @error('email') is-invalid @enderror" required>
                @error('email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            <div class="mb-4">
                <label for="password" class="form-label fw-semibold">Contraseña</label>
                <input id="password" type="password" name="password"
                    class="form-control form-control-lg rounded-4 @error('password') is-invalid @enderror" required>
                @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            <div class="mb-4">
                <label for="password_confirmation" class="form-label fw-semibold">Confirmar contraseña</label>
                <input id="password_confirmation" type="password" name="password_confirmation"
                    class="form-control form-control-lg rounded-4" required>
            </div>

            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                <a href="{{ route('login') }}" class="text-decoration-none fw-medium" style="color: #047857;">
                    Ya tengo cuenta
                </a>

                <button type="submit" class="btn text-white rounded-4 px-4 py-3 fw-semibold"
                        style="background-color: #059669; border-color: #059669;">
                    Registrarme
                </button>
            </div>
        </form>
    </div>
</div>
@endsection