@extends('layouts.app')

@section('title', 'Nueva estación meteorológica')

@section('content')
    <section class="admin-hero p-4 p-md-5">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-lg-8">
                <div class="text-uppercase small fw-semibold text-light-emphasis mb-2">
                    Administración
                </div>
                <h1 class="display-6 fw-bold mb-2">Nueva estación meteorológica</h1>
                <p class="mb-0 text-light-emphasis">
                    Registra una estación EcoData y asígnala al colegio correspondiente.
                </p>
            </div>

            <!-- <div class="col-12 col-lg-4 text-lg-end">
                <a href="{{ route('admin.weather-stations.index') }}"
                   class="btn btn-light rounded-4 px-4 py-3 fw-semibold">
                    Volver al listado
                </a>
            </div> -->
        </div>
    </section>

    <section class="admin-card bg-white p-4">
        <form method="POST" action="{{ route('admin.weather-stations.store') }}">
            @csrf

            @include('admin.weather-stations.partials.form', [
                'station' => null,
            ])

            <div class="d-flex flex-column flex-md-row justify-content-end gap-2 mt-4">
                <a href="{{ route('admin.weather-stations.index') }}"
                   class="btn btn-outline-secondary btn-lg rounded-4 px-4">
                    Cancelar
                </a>

                <button type="submit"
                        class="btn text-white btn-lg rounded-4 px-4 fw-semibold"
                        style="background-color: var(--school-primary);">
                    Guardar estación
                </button>
            </div>
        </form>
    </section>
@endsection