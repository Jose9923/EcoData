@extends('layouts.app')

@section('title', 'Detalle de actividad de diario de campo')

@section('content')
    <section class="admin-hero p-4 p-md-5">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-lg-8">
                <div class="text-uppercase small fw-semibold text-light-emphasis mb-2">
                    Administración
                </div>
                <h1 class="display-6 fw-bold mb-2">{{ $activity->title }}</h1>
                <p class="mb-0 text-light-emphasis">
                    Consulta la configuración de la actividad y las preguntas asignadas.
                </p>
            </div>

            <div class="col-12 col-lg-4 text-lg-end">
                <a href="{{ route('admin.field-diary-activities.edit', $activity) }}"
                   class="btn text-white rounded-4 px-4 py-3 fw-semibold me-2"
                   style="background-color: var(--school-primary);">
                    Editar
                </a>

                <a href="{{ route('admin.field-diary-activities.index') }}"
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
                    Datos de asignación, tipo de actividad y estado.
                </p>
            </div>

            <div>
                @if($activity->is_active)
                    <span class="badge rounded-pill text-bg-success px-3 py-2">Activa</span>
                @else
                    <span class="badge rounded-pill text-bg-secondary px-3 py-2">Inactiva</span>
                @endif
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-lg-6">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Título</div>
                    <div class="fs-5 fw-semibold">{{ $activity->title }}</div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Tipo de actividad</div>
                    <div class="fs-5 fw-semibold">{{ $activity->entry_type_label }}</div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Colegio</div>
                    <div class="fs-5 fw-semibold">{{ $activity->school?->name ?? 'Sin colegio' }}</div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Grado / Curso</div>
                    <div class="fs-5 fw-semibold">
                        {{ $activity->grade?->label ?? $activity->grade?->name ?? 'Todos los grados' }}
                        /
                        {{ $activity->course?->label ?? $activity->course?->name ?? 'Todos los cursos' }}
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Estación meteorológica</div>
                    <div class="fs-5 fw-semibold">
                        {{ $activity->weatherStation?->name ?? 'Sin estación asociada' }}
                    </div>
                    @if($activity->weatherStation?->code)
                        <div class="text-muted small mt-1">
                            Código: {{ $activity->weatherStation->code }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Fechas</div>
                    <div class="fs-5 fw-semibold">
                        {{ $activity->starts_at?->format('d/m/Y') ?? 'Sin inicio' }}
                        -
                        {{ $activity->ends_at?->format('d/m/Y') ?? 'Sin cierre' }}
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Preguntas</div>
                    <div class="fs-5 fw-semibold">{{ $activity->questions_count }} preguntas</div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Entregas</div>
                    <div class="fs-5 fw-semibold">{{ $activity->submissions_count }} entregas</div>
                </div>
            </div>
        </div>
    </section>

    <section class="admin-card bg-white p-4">
        <div class="mb-4">
            <h2 class="h5 fw-bold mb-1">Descripción e instrucciones</h2>
            <p class="text-muted mb-0">
                Orientación que verán los estudiantes al responder la actividad.
            </p>
        </div>

        <div class="border rounded-4 p-4">
            @if($activity->description)
                <p class="mb-0">{{ $activity->description }}</p>
            @else
                <p class="text-muted mb-0">No se registró descripción para esta actividad.</p>
            @endif
        </div>
    </section>

    <section class="admin-card bg-white p-4">
        <div class="mb-4">
            <h2 class="h5 fw-bold mb-1">Preguntas de la actividad</h2>
            <p class="text-muted mb-0">
                Preguntas configuradas por el docente para esta actividad.
            </p>
        </div>

        <div class="d-flex flex-column gap-3">
            @forelse($activity->questions as $question)
                <div class="border rounded-4 p-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                        <div>
                            <div class="small text-muted fw-semibold mb-1">
                                Pregunta {{ $question->order }} · {{ $question->question_type_label }}
                                @if($question->is_required)
                                    · Obligatoria
                                @endif
                            </div>
                            <div class="fw-semibold">{{ $question->question_text }}</div>
                        </div>
                    </div>

                    @if(in_array($question->question_type, ['select', 'radio', 'checkbox'], true) && is_array($question->options))
                        <div class="mt-3">
                            <div class="small text-muted fw-semibold mb-2">Opciones</div>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($question->options as $option)
                                    <span class="badge rounded-pill text-bg-light">{{ $option }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center text-muted py-5">
                    Esta actividad no tiene preguntas registradas.
                </div>
            @endforelse
        </div>
    </section>
@endsection