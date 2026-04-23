@extends('layouts.guest')

@section('content')
<div class="mx-auto" style="max-width: 34rem;">
    <div class="text-center mb-4">
        <h1 class="display-6 fw-bold text-white mb-2">Confirmar contraseña</h1>
        <p class="text-white-50 mb-0">
            Por seguridad, confirma tu contraseña antes de continuar.
        </p>
    </div>

    <div class="rounded-4 border bg-white shadow-lg p-4 p-md-5">
        <form method="POST" action="{{ route('password.confirm') }}">
            @csrf

            <div class="mb-4">
                <label for="password" class="form-label fw-semibold">Contraseña</label>
                <input id="password" type="password" name="password"
                    class="form-control form-control-lg rounded-4 @error('password') is-invalid @enderror" required autofocus>
                @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="btn w-100 text-white rounded-4 py-3 fw-semibold"
                    style="background-color: #059669; border-color: #059669;">
                Confirmar
            </button>
        </form>
    </div>
</div>
@endsection