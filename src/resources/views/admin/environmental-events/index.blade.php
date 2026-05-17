@extends('layouts.app')

@section('title', 'Calendario ambiental')

@section('content')
    <section class="admin-hero p-4 p-md-5">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-lg-8">
                <div class="text-uppercase small fw-semibold admin-hero-subtitle mb-2">
                    Administración
                </div>
                <h1 class="display-6 fw-bold mb-2">Calendario ambiental</h1>
                <p class="mb-0 admin-hero-subtitle">
                    Programa fechas ambientales, jornadas institucionales y conmemoraciones ecológicas por colegio.
                </p>
            </div>

            <div class="col-12 col-lg-4 text-lg-end">
                <a href="{{ route('admin.environmental-events.create') }}"
                   class="btn text-white rounded-4 px-4 py-3 fw-semibold"
                   style="background-color: var(--school-primary);">
                    + Nuevo evento
                </a>
            </div>
        </div>
    </section>

    <section class="admin-card bg-white p-4">
        <form method="GET" action="{{ route('admin.environmental-events.index') }}">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-8">
                    <label for="search" class="form-label fw-semibold">Buscar evento</label>
                    <input type="text"
                           id="search"
                           name="search"
                           value="{{ $search }}"
                           class="form-control form-control-lg rounded-4"
                           placeholder="Buscar por título, descripción, colegio o creador...">
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
        <div class="mb-4">
            <h2 class="h5 fw-bold mb-1">Eventos ambientales registrados</h2>
            <p class="text-muted mb-0">
                Consulta, edita o elimina los eventos ambientales programados.
            </p>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Evento</th>
                        <th>Colegio</th>
                        <th>Fechas</th>
                        <th>Creado por</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($events as $event)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    @if($event->image_path)
                                        <img src="{{ asset('storage/' . $event->image_path) }}"
                                             alt="{{ $event->title }}"
                                             class="rounded-4"
                                             style="width: 72px; height: 56px; object-fit: cover;">
                                    @else
                                        <div class="rounded-4 bg-light d-flex align-items-center justify-content-center"
                                             style="width: 72px; height: 56px;">
                                            🌎
                                        </div>
                                    @endif

                                    <div>
                                        <div class="fw-semibold">{{ $event->title }}</div>
                                        <small class="text-muted">
                                            {{ Str::limit($event->description, 80) }}
                                        </small>
                                    </div>
                                </div>
                            </td>

                            <td>{{ $event->school?->name ?? 'Sin colegio' }}</td>

                            <td>
                                <div>{{ $event->starts_at?->format('d/m/Y') }}</div>
                                @if($event->ends_at && !$event->starts_at->isSameDay($event->ends_at))
                                    <small class="text-muted">Hasta {{ $event->ends_at->format('d/m/Y') }}</small>
                                @endif
                            </td>

                            <td>{{ $event->creator?->name ?? 'No registrado' }}</td>

                            <td>
                                @if($event->is_active)
                                    <span class="badge rounded-pill text-bg-success">Activo</span>
                                @else
                                    <span class="badge rounded-pill text-bg-secondary">Inactivo</span>
                                @endif
                            </td>

                            <td class="text-end">
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <a href="{{ route('admin.environmental-events.show', $event) }}"
                                    class="btn btn-outline-primary rounded-4">
                                        Ver detalle
                                    </a>

                                    <a href="{{ route('admin.environmental-events.edit', $event) }}"
                                    class="btn btn-outline-secondary rounded-4">
                                        Editar
                                    </a>

                                    <form action="{{ route('admin.environmental-events.destroy', $event) }}"
                                        method="POST"
                                        class="js-confirm-delete"
                                        data-title="¿Eliminar evento ambiental?"
                                        data-text="Esta acción eliminará el evento ambiental {{ $event->title }}. Si tiene información asociada, el sistema podría impedir la eliminación."
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
                            <td colspan="6" class="text-center text-muted py-5">
                                No hay eventos ambientales registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($events->hasPages())
            <div class="mt-4">
                {{ $events->links() }}
            </div>
        @endif
    </section>
@endsection