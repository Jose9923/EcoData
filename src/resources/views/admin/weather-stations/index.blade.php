@extends('layouts.app')

@section('title', 'Estaciones meteorológicas')

@section('content')
    <section class="admin-hero p-4 p-md-5">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-lg-8">
                <div class="text-uppercase small fw-semibold admin-hero-subtitle mb-2">
                    Administración
                </div>
                <h1 class="display-6 fw-bold mb-2">Estaciones meteorológicas</h1>
                <p class="mb-0 admin-hero-subtitle">
                    Gestiona las estaciones EcoData asociadas a cada colegio, su ubicación, código institucional y responsable.
                </p>
            </div>

            <div class="col-12 col-lg-4 text-lg-end">
                <a href="{{ route('admin.weather-stations.create') }}"
                   class="btn text-white rounded-4 px-4 py-3 fw-semibold"
                   style="background-color: var(--school-primary);">
                    + Nueva estación
                </a>
            </div>
        </div>
    </section>

    <section class="admin-card bg-white p-4">
        <form method="GET" action="{{ route('admin.weather-stations.index') }}">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-8">
                    <label for="search" class="form-label fw-semibold">Buscar estación</label>
                    <input type="text"
                           id="search"
                           name="search"
                           value="{{ $search }}"
                           class="form-control form-control-lg rounded-4"
                           placeholder="Buscar por nombre, código, ubicación, colegio o responsable...">
                </div>

                <div class="col-12 col-sm-6 col-lg-2">
                    <label for="per_page" class="form-label fw-semibold">Registros</label>
                    <select id="per_page" name="per_page" class="form-select form-select-lg rounded-4">
                        @foreach([10, 15, 25, 50] as $size)
                            <option value="{{ $size }}" @selected($perPage == $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-sm-6 col-lg-2 d-grid">
                    <button class="btn btn-dark btn-lg rounded-4">Filtrar</button>
                </div>
            </div>
        </form>
    </section>

    <section class="admin-card bg-white p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
            <div>
                <h2 class="h5 fw-bold mb-1">Listado de estaciones</h2>
                <p class="text-muted mb-0">
                    Consulta, edita o elimina las estaciones meteorológicas registradas.
                </p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Estación</th>
                        <th>Código</th>
                        <th>Colegio</th>
                        <th>Ubicación</th>
                        <th>Responsable</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stations as $station)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $station->name }}</div>
                                @if($station->installation_date)
                                    <small class="text-muted">
                                        Instalada: {{ $station->installation_date->format('d/m/Y') }}
                                    </small>
                                @endif
                            </td>

                            <td>
                                <span class="badge rounded-pill text-bg-light">
                                    {{ $station->code }}
                                </span>
                            </td>

                            <td>{{ $station->school?->name ?? 'Sin colegio' }}</td>

                            <td>{{ $station->location_name ?? 'No registrada' }}</td>

                            <td>{{ $station->responsible?->name ?? 'Sin responsable' }}</td>

                            <td>
                                @if($station->is_active)
                                    <span class="badge rounded-pill text-bg-success">Activa</span>
                                @else
                                    <span class="badge rounded-pill text-bg-secondary">Inactiva</span>
                                @endif
                            </td>

                            <td class="text-end">
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    
                                    <a href="{{ route('admin.weather-stations.show', $station) }}"
                                    class="btn btn-outline-primary rounded-4">
                                        Ver detalle
                                    </a>

                                    <a href="{{ route('admin.weather-stations.edit', $station) }}"
                                    class="btn btn-outline-secondary rounded-4">
                                        Editar
                                    </a>
 
                                    <form action="{{ route('admin.weather-stations.destroy', $station) }}"
                                        method="POST"
                                        class="js-confirm-delete"
                                        data-title="¿Eliminar estación meteorológica?"
                                        data-text="Esta acción eliminará la estación meteorológica {{ $station->name }}. Si tiene información asociada, el sistema podría impedir la eliminación."
                                        data-confirm-button="Sí, eliminar">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="btn btn-outline-danger rounded-4">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                No hay estaciones meteorológicas registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($stations->hasPages())
            <div class="mt-4">
                {{ $stations->links() }}
            </div>
        @endif
    </section>
@endsection