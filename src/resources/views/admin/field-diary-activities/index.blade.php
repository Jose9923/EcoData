@extends('layouts.app')

@section('title', 'Diario de Campo EcoData')

@section('content')
    <section class="admin-hero p-4 p-md-5">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-lg-8">
                <div class="text-uppercase small fw-semibold admin-hero-subtitle mb-2">
                    Administración
                </div>
                <h1 class="display-6 fw-bold mb-2">Diario de Campo EcoData</h1>
                <p class="mb-0 admin-hero-subtitle">
                    Crea actividades de observación, retos o portafolios para evaluar el análisis ambiental de los estudiantes.
                </p>
            </div>

            <div class="col-12 col-lg-4 text-lg-end">
                <a href="{{ route('admin.field-diary-activities.create') }}"
                   class="btn text-white rounded-4 px-4 py-3 fw-semibold"
                   style="background-color: var(--school-primary);">
                    + Nueva actividad
                </a>
            </div>
        </div>
    </section>

    <section class="admin-card bg-white p-4">
        <form method="GET" action="{{ route('admin.field-diary-activities.index') }}">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-8">
                    <label for="search" class="form-label fw-semibold">Buscar actividad</label>
                    <input type="text"
                           id="search"
                           name="search"
                           value="{{ $search }}"
                           class="form-control form-control-lg rounded-4"
                           placeholder="Buscar por título, descripción, tipo, colegio, grado, curso, estación o creador...">
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
            <h2 class="h5 fw-bold mb-1">Actividades registradas</h2>
            <p class="text-muted mb-0">
                Consulta, edita o elimina las actividades de diario de campo creadas para los estudiantes.
            </p>
        </div>

        <div>
            <table id="fieldDiaryActivitiesTable"
                class="table table-striped table-hover align-middle nowrap w-100 mb-0 js-ecodata-datatable"
                data-paging="false"
                data-searching="false"
                data-info="false"
                data-empty="No hay actividades de diario registradas."
                data-zero="No hay actividades de diario registradas.">
                <thead>
                    <tr>
                        <th>Actividad</th>
                        <th>Asignación</th>
                        <th>Estación</th>
                        <th>Preguntas</th>
                        <th>Entregas</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($activities as $activity)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $activity->title }}</div>
                                <small class="text-muted">
                                    {{ $activity->entry_type_label }}
                                    @if($activity->starts_at)
                                        · {{ $activity->starts_at->format('d/m/Y') }}
                                    @endif
                                    @if($activity->ends_at)
                                        - {{ $activity->ends_at->format('d/m/Y') }}
                                    @endif
                                </small>
                            </td>

                            <td>
                                <div>{{ $activity->school?->name ?? 'Sin colegio' }}</div>
                                <small class="text-muted">
                                    Grado:
                                    {{ $activity->grade?->label ?? $activity->grade?->name ?? 'Todos' }}
                                    · Curso:
                                    {{ $activity->course?->label ?? $activity->course?->name ?? 'Todos' }}
                                </small>
                            </td>

                            <td>
                                @if($activity->weatherStation)
                                    <div>{{ $activity->weatherStation->name }}</div>
                                    <small class="text-muted">{{ $activity->weatherStation->code }}</small>
                                @else
                                    <span class="text-muted">Sin estación asociada</span>
                                @endif
                            </td>

                            <td>
                                <span class="badge rounded-pill text-bg-light">
                                    {{ $activity->questions_count }} preguntas
                                </span>
                            </td>

                            <td>
                                <span class="badge rounded-pill text-bg-light">
                                    {{ $activity->submissions_count }} entregas
                                </span>
                            </td>

                            <td>
                                @if($activity->is_active)
                                    <span class="badge rounded-pill text-bg-success">Activa</span>
                                @else
                                    <span class="badge rounded-pill text-bg-secondary">Inactiva</span>
                                @endif
                            </td>

                            <td class="text-end">
                                <div class="d-inline-flex justify-content-end gap-2 flex-nowrap">
                                    <a href="{{ route('admin.field-diary-activities.show', $activity) }}"
                                    class="btn btn-outline-primary rounded-4 text-nowrap">
                                        Ver detalles
                                    </a>

                                    <a href="{{ route('admin.field-diary-activities.edit', $activity) }}"
                                    class="btn btn-outline-secondary rounded-4 text-nowrap">
                                        Editar
                                    </a>

                                    <form action="{{ route('admin.field-diary-activities.destroy', $activity) }}"
                                        method="POST"
                                        class="js-confirm-delete"
                                        data-title="¿Eliminar actividad de diario de campo?"
                                        data-text="Esta acción eliminará la actividad de diario de campo {{ $activity->title }}. Si tiene información asociada, el sistema podría impedir la eliminación."
                                        data-confirm-button="Sí, eliminar">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="btn btn-outline-danger rounded-4 text-nowrap">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($activities->hasPages())
            <div class="p-4 border-top d-flex justify-content-center overflow-auto">
                {{ $activities->onEachSide(1)->links() }}
            </div>
        @endif
    </section>
@endsection
