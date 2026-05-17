@extends('layouts.app')

@section('title', 'Sensores')

@section('content')
    <section class="admin-hero p-4 p-md-5">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-lg-8">
                <div class="text-uppercase small fw-semibold admin-hero-subtitle mb-2">
                    Administración
                </div>
                <h1 class="display-6 fw-bold mb-2">Sensores</h1>
                <p class="mb-0 admin-hero-subtitle">
                    Gestiona los sensores instalados en cada estación meteorológica EcoData, su variable asociada y estado de mantenimiento.
                </p>
            </div>

            <div class="col-12 col-lg-4 text-lg-end">
                <a href="{{ route('admin.sensors.create') }}"
                   class="btn text-white rounded-4 px-4 py-3 fw-semibold"
                   style="background-color: var(--school-primary);">
                    + Nuevo sensor
                </a>
            </div>
        </div>
    </section>

    <section class="admin-card bg-white p-4">
        <form method="GET" action="{{ route('admin.sensors.index') }}">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-8">
                    <label for="search" class="form-label fw-semibold">Buscar sensor</label>
                    <input type="text"
                           id="search"
                           name="search"
                           value="{{ $search }}"
                           class="form-control form-control-lg rounded-4"
                           placeholder="Buscar por nombre, código, estación, colegio, variable, marca, modelo o estado...">
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
                <h2 class="h5 fw-bold mb-1">Listado de sensores</h2>
                <p class="text-muted mb-0">
                    Consulta, edita o elimina los sensores registrados por estación.
                </p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Sensor</th>
                        <th>Estación</th>
                        <th>Variable</th>
                        <th>Marca / Modelo</th>
                        <th>Mantenimiento</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sensors as $sensor)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $sensor->name }}</div>
                                <small class="text-muted">Código: {{ $sensor->code }}</small>
                            </td>

                            <td>
                                <div class="fw-semibold">{{ $sensor->weatherStation?->name ?? 'Sin estación' }}</div>
                                <small class="text-muted">
                                    {{ $sensor->weatherStation?->school?->name ?? 'Sin colegio' }}
                                </small>
                            </td>

                            <td>
                                @if($sensor->variable)
                                    <div>{{ $sensor->variable->name }}</div>
                                    <small class="text-muted">
                                        {{ $sensor->variable->category?->name ?? 'Sin categoría' }}
                                        @if($sensor->variable->unit)
                                            · {{ $sensor->variable->unit }}
                                        @endif
                                    </small>
                                @else
                                    <span class="text-muted">Sin variable</span>
                                @endif
                            </td>

                            <td>
                                <div>{{ $sensor->brand ?? 'Sin marca' }}</div>
                                <small class="text-muted">{{ $sensor->model ?? 'Sin modelo' }}</small>
                            </td>

                            <td>
                                @if($sensor->next_maintenance_date)
                                    <div>{{ $sensor->next_maintenance_date->format('d/m/Y') }}</div>
                                    <small class="text-muted">Próximo mantenimiento</small>
                                @else
                                    <span class="text-muted">No programado</span>
                                @endif
                            </td>

                            <td>
                                @php
                                    $statusClass = match($sensor->status) {
                                        'operativo' => 'text-bg-success',
                                        'mantenimiento' => 'text-bg-warning',
                                        'fallando' => 'text-bg-danger',
                                        'inactivo' => 'text-bg-secondary',
                                        'retirado' => 'text-bg-dark',
                                        default => 'text-bg-light',
                                    };
                                @endphp

                                <span class="badge rounded-pill {{ $statusClass }}">
                                    {{ $sensor->status_label }}
                                </span>

                                @unless($sensor->is_active)
                                    <span class="badge rounded-pill text-bg-secondary mt-1">Inactivo</span>
                                @endunless
                            </td>

                            <td class="text-end">
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <a href="{{ route('admin.sensors.show', $sensor) }}"
                                    class="btn btn-sm btn-outline-primary rounded-4">
                                        Ver detalle
                                    </a>

                                    <a href="{{ route('admin.sensors.edit', $sensor) }}"
                                    class="btn btn-sm btn-outline-secondary rounded-4">
                                        Editar
                                    </a>

                                    <form action="{{ route('admin.sensors.destroy', $sensor) }}"
                                        method="POST"
                                        class="js-confirm-delete d-inline m-0"
                                        data-title="¿Eliminar sensor?"
                                        data-text="Esta acción eliminará el sensor {{ $sensor->name }}. Si tiene información asociada, el sistema podría impedir la eliminación."
                                        data-confirm-button="Sí, eliminar">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-4">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                No hay sensores registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sensors->hasPages())
            <div class="mt-4">
                {{ $sensors->links() }}
            </div>
        @endif
    </section>
@endsection