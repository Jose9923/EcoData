@extends('layouts.app')

@section('title', 'Mi Diario de Campo EcoData')

@section('content')
    <section class="admin-hero p-4 p-md-5">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-lg-8">
                <div class="text-uppercase small fw-semibold admin-hero-subtitle mb-2">
                    Estudiante
                </div>
                <h1 class="display-6 fw-bold mb-2">Mi Diario de Campo EcoData</h1>
                <p class="mb-0 admin-hero-subtitle">
                    Responde actividades de observación, retos y portafolios usando datos ambientales de EcoData.
                </p>
            </div>
        </div>
    </section>

    <section class="admin-card bg-white p-4">
        <div class="mb-4">
            <h2 class="h5 fw-bold mb-1">Actividades disponibles</h2>
            <p class="text-muted mb-0">
                Estas son las actividades activas asignadas a tu colegio, grado o curso.
            </p>
        </div>

        <div class="row g-4">
            @forelse($activities as $activity)
                @php
                    $submission = $activity->submissions->first();
                    $status = $submission?->status;
                    $statusClass = match($status) {
                        'borrador' => 'text-bg-warning',
                        'enviado' => 'text-bg-primary',
                        'revisado' => 'text-bg-success',
                        'devuelto' => 'text-bg-danger',
                        default => 'text-bg-secondary',
                    };
                    $statusLabel = $submission?->status_label ?? 'Pendiente';
                @endphp

                <div class="col-12 col-md-6 col-xl-4">
                    <div class="border rounded-4 p-4 h-100 bg-white">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                            <div>
                                <div class="text-uppercase small fw-semibold text-muted mb-1">
                                    {{ $activity->entry_type_label }}
                                </div>
                                <h3 class="h5 fw-bold mb-1">{{ $activity->title }}</h3>
                            </div>

                            <span class="badge rounded-pill {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                        </div>

                        <p class="text-muted mb-3">
                            {{ Str::limit($activity->description, 140) }}
                        </p>

                        <div class="small text-muted mb-3">
                            @if($activity->weatherStation)
                                <div><strong>Estación:</strong> {{ $activity->weatherStation->name }}</div>
                            @endif

                            <div><strong>Preguntas:</strong> {{ $activity->questions_count }}</div>

                            @if($activity->ends_at)
                                <div><strong>Cierre:</strong> {{ $activity->ends_at->format('d/m/Y') }}</div>
                            @endif
                        </div>

                        <a href="{{ route('estudiante.field-diaries.show', $activity) }}"
                           class="btn text-white rounded-4 px-4"
                           style="background-color: var(--school-primary);">
                            Abrir actividad
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="text-center text-muted py-5">
                        No tienes actividades de diario de campo disponibles.
                    </div>
                </div>
            @endforelse
        </div>
    </section>

    @if($mySubmissions->isNotEmpty())
        <section class="admin-card bg-white p-4">
            <div class="mb-4">
                <h2 class="h5 fw-bold mb-1">Mis entregas</h2>
                <p class="text-muted mb-0">
                    Historial de respuestas guardadas, enviadas o revisadas.
                </p>
            </div>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Actividad</th>
                            <th>Estado</th>
                            <th>Fecha de envío</th>
                            <th>Nota</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($mySubmissions as $submission)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $submission->activity?->title }}</div>
                                    <small class="text-muted">
                                        {{ $submission->activity?->entry_type_label }}
                                    </small>
                                </td>

                                <td>
                                    @php
                                        $statusClass = match($submission->status) {
                                            'borrador' => 'text-bg-warning',
                                            'enviado' => 'text-bg-primary',
                                            'revisado' => 'text-bg-success',
                                            'devuelto' => 'text-bg-danger',
                                            default => 'text-bg-secondary',
                                        };
                                    @endphp

                                    <span class="badge rounded-pill {{ $statusClass }}">
                                        {{ $submission->status_label }}
                                    </span>
                                </td>

                                <td>
                                    {{ $submission->submitted_at?->format('d/m/Y H:i') ?? 'Sin enviar' }}
                                </td>

                                <td>
                                    {{ $submission->score ?? '—' }}
                                </td>

                                <td class="text-end">
                                    <a href="{{ route('estudiante.field-diaries.show', $submission->activity) }}"
                                    class="btn btn-outline-primary rounded-4">
                                        Ver detalles
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection