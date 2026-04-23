@extends('layouts.guest')

@section('content')
<div class="mx-auto" style="max-width: 34rem;">
    <div class="text-center mb-4">
        <h1 class="display-6 fw-bold text-white mb-2">Recuperar contraseña</h1>
        <p class="text-white-50 mb-0">
            Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña.
        </p>
    </div>

    <div class="rounded-4 border bg-white shadow-lg p-4 p-md-5">
        @if (session('status'))
            <div class="alert alert-success rounded-4 mb-4">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="mb-4">
                <label for="email" class="form-label fw-semibold">Correo electrónico</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                    class="form-control form-control-lg rounded-4 @error('email') is-invalid @enderror" required autofocus>
                @error('email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            <div class="d-flex justify-content-between align-items-center gap-3">
                <a href="{{ route('login') }}" class="text-decoration-none fw-medium" style="color: #047857;">
                    Volver al login
                </a>

                <button type="submit" class="btn text-white rounded-4 px-4 py-3 fw-semibold"
                        style="background-color: #059669; border-color: #059669;">
                    Enviar enlace
                </button>
            </div>
        </form>
    </div>
</div>
@endsection