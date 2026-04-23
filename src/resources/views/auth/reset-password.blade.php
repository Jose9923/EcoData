@extends('layouts.guest')

@section('content')
<div class="mx-auto" style="max-width: 34rem;">
    <div class="text-center mb-4">
        <h1 class="display-6 fw-bold text-white mb-2">Restablecer contraseña</h1>
        <p class="text-white-50 mb-0">
            Define una nueva contraseña para tu cuenta.
        </p>
    </div>

    <div class="rounded-4 border bg-white shadow-lg p-4 p-md-5">
        <form method="POST" action="{{ route('password.store') }}">
            @csrf

            <input type="hidden" name="token" value="{{ request()->route('token') }}">

            <div class="mb-4">
                <label for="email" class="form-label fw-semibold">Correo electrónico</label>
                <input id="email" type="email" name="email" value="{{ old('email', request()->email) }}"
                    class="form-control form-control-lg rounded-4 @error('email') is-invalid @enderror" required autofocus>
                @error('email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            <div class="mb-4">
                <label for="password" class="form-label fw-semibold">Nueva contraseña</label>
                <input id="password" type="password" name="password"
                    class="form-control form-control-lg rounded-4 @error('password') is-invalid @enderror" required>
                @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            <div class="mb-4">
                <label for="password_confirmation" class="form-label fw-semibold">Confirmar contraseña</label>
                <input id="password_confirmation" type="password" name="password_confirmation"
                    class="form-control form-control-lg rounded-4" required>
            </div>

            <button type="submit" class="btn w-100 text-white rounded-4 py-3 fw-semibold"
                    style="background-color: #059669; border-color: #059669;">
                Restablecer contraseña
            </button>
        </form>
    </div>
</div>
@endsection