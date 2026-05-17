@extends('layouts.app')

@section('title', 'Detalle de estación meteorológica')

@section('content')
    <section class="admin-hero p-4 p-md-5">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-lg-8">
                <div class="text-uppercase small fw-semibold admin-hero-subtitle mb-2">
                    Administración
                </div>
                <h1 class="display-6 fw-bold mb-2">{{ $station->name }}</h1>
                <p class="mb-0 admin-hero-subtitle">
                    Consulta la información general, ubicación y estado de la estación meteorológica.
                </p>
            </div>

            <div class="col-12 col-lg-4 text-lg-end">
                <a href="{{ route('admin.weather-stations.edit', $station) }}"
                   class="btn text-white rounded-4 px-4 py-3 fw-semibold me-2"
                   style="background-color: var(--school-primary);">
                    Editar estación
                </a>

                <a href="{{ route('admin.weather-stations.index') }}"
                   class="btn btn-light rounded-4 px-4 py-3 fw-semibold">
                    Volver
                </a>
            </div>
        </div>
    </section>

    <section class="admin-card bg-white p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
            <div>
                <h2 class="h5 fw-bold mb-1">Información general</h2>
                <p class="text-muted mb-0">
                    Datos básicos de identificación de la estación.
                </p>
            </div>

            <div>
                @if($station->is_active)
                    <span class="badge rounded-pill text-bg-success px-3 py-2">Activa</span>
                @else
                    <span class="badge rounded-pill text-bg-secondary px-3 py-2">Inactiva</span>
                @endif
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-lg-6">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Nombre</div>
                    <div class="fs-5 fw-semibold">{{ $station->name }}</div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Código</div>
                    <div class="fs-5 fw-semibold">{{ $station->code }}</div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Colegio</div>
                    <div class="fs-5 fw-semibold">
                        {{ $station->school?->name ?? 'Sin colegio asignado' }}
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Responsable</div>
                    <div class="fs-5 fw-semibold">
                        {{ $station->responsible?->name ?? 'Sin responsable asignado' }}
                    </div>

                    @if($station->responsible?->email)
                        <div class="text-muted small mt-1">
                            {{ $station->responsible->email }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Ubicación o referencia</div>
                    <div class="fs-5 fw-semibold">
                        {{ $station->location_name ?? 'No registrada' }}
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Fecha de instalación</div>
                    <div class="fs-5 fw-semibold">
                        {{ $station->installation_date?->format('d/m/Y') ?? 'No registrada' }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="admin-card bg-white p-4">
        <div class="mb-4">
            <h2 class="h5 fw-bold mb-1">Ubicación técnica</h2>
            <p class="text-muted mb-0">
                Coordenadas y altitud registradas para la estación.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-12 col-md-4">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Latitud</div>
                    <div class="fs-5 fw-semibold">
                        {{ $station->latitude ?? 'No registrada' }}
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Longitud</div>
                    <div class="fs-5 fw-semibold">
                        {{ $station->longitude ?? 'No registrada' }}
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Altitud</div>
                    <div class="fs-5 fw-semibold">
                        @if(! is_null($station->altitude))
                            {{ $station->altitude }} m s. n. m.
                        @else
                            No registrada
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="admin-card bg-white p-4">
        <div class="mb-3">
            <h2 class="h5 fw-bold mb-1">Descripción</h2>
            <p class="text-muted mb-0">
                Información complementaria sobre la estación meteorológica.
            </p>
        </div>

        <div class="border rounded-4 p-4">
            @if($station->description)
                <p class="mb-0">{{ $station->description }}</p>
            @else
                <p class="text-muted mb-0">No se ha registrado una descripción para esta estación.</p>
            @endif
        </div>
    </section>
@endsection