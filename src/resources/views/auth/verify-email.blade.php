@extends('layouts.guest')

@section('content')
<div class="mx-auto" style="max-width: 34rem;">
    <div class="text-center mb-4">
        <h1 class="display-6 fw-bold text-white mb-2">Verifica tu correo</h1>
        <p class="text-white-50 mb-0">
            Antes de continuar, revisa tu bandeja de entrada y confirma tu dirección de correo.
        </p>
    </div>

    <div class="rounded-4 border bg-white shadow-lg p-4 p-md-5">
        @if (session('status') === 'verification-link-sent')
            <div class="alert alert-success rounded-4 mb-4">
                Se envió un nuevo enlace de verificación a tu correo.
            </div>
        @endif

        <div class="d-flex flex-column gap-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn w-100 text-white rounded-4 py-3 fw-semibold"
                        style="background-color: #059669; border-color: #059669;">
                    Reenviar correo de verificación
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline-secondary w-100 rounded-4 py-3 fw-semibold">
                    Cerrar sesión
                </button>
            </form>
        </div>
    </div>
</div>
@endsection