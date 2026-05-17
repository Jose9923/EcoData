@extends('layouts.app')

@section('title', 'Editar evento ambiental')

@section('content')
    <section class="admin-hero p-4 p-md-5">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-lg-8">
                <div class="text-uppercase small fw-semibold admin-hero-subtitle mb-2">
                    Administración
                </div>
                <h1 class="display-6 fw-bold mb-2">Editar evento ambiental</h1>
                <p class="mb-0 admin-hero-subtitle">
                    Actualiza la información de {{ $event->title }}.
                </p>
            </div>

            <!-- <div class="col-12 col-lg-4 text-lg-end">
                <a href="{{ route('admin.environmental-events.index') }}"
                   class="btn btn-light rounded-4 px-4 py-3 fw-semibold">
                    Volver al listado
                </a>
            </div> -->
        </div>
    </section>

    <section class="admin-card bg-white p-4">
        <form method="POST" action="{{ route('admin.environmental-events.update', $event) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            @include('admin.environmental-events.partials.form')

            <div class="d-flex flex-column flex-md-row justify-content-end gap-2 mt-4">
                <a href="{{ route('admin.environmental-events.index') }}"
                   class="btn btn-outline-secondary btn-lg rounded-4 px-4">
                    Cancelar
                </a>

                <button type="submit"
                        class="btn text-white btn-lg rounded-4 px-4 fw-semibold"
                        style="background-color: var(--school-primary);">
                    Actualizar evento
                </button>
            </div>
        </form>
    </section>
@endsection