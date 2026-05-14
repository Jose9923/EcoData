@extends('layouts.app')

@section('title', 'Nueva actividad de diario de campo')

@section('content')
    <section class="admin-hero p-4 p-md-5">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-lg-8">
                <div class="text-uppercase small fw-semibold text-light-emphasis mb-2">
                    Administración
                </div>
                <h1 class="display-6 fw-bold mb-2">Nueva actividad de diario de campo</h1>
                <p class="mb-0 text-light-emphasis">
                    Diseña una actividad con preguntas personalizadas para que los estudiantes analicen datos ambientales.
                </p>
            </div>

            <div class="col-12 col-lg-4 text-lg-end">
                <a href="{{ route('admin.field-diary-activities.index') }}"
                   class="btn btn-light rounded-4 px-4 py-3 fw-semibold">
                    Volver al listado
                </a>
            </div>
        </div>
    </section>

    <section class="admin-card bg-white p-4">
        <form method="POST" action="{{ route('admin.field-diary-activities.store') }}">
            @csrf

            @include('admin.field-diary-activities.partials.form')

            <div class="d-flex flex-column flex-md-row justify-content-end gap-2 mt-4">
                <a href="{{ route('admin.field-diary-activities.index') }}"
                   class="btn btn-outline-secondary btn-lg rounded-4 px-4">
                    Cancelar
                </a>

                <button type="submit"
                        class="btn text-white btn-lg rounded-4 px-4 fw-semibold"
                        style="background-color: var(--school-primary);">
                    Guardar actividad
                </button>
            </div>
        </form>
    </section>
@endsection