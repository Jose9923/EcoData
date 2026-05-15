@extends('layouts.app')

@section('title', 'Revisión de entrega')

@section('content')
    <section class="admin-hero p-4 p-md-5">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-lg-8">
                <div class="text-uppercase small fw-semibold text-light-emphasis mb-2">
                    Revisión docente
                </div>
                <h1 class="display-6 fw-bold mb-2">{{ $activity?->title ?? 'Entrega de diario de campo' }}</h1>
                <p class="mb-0 text-light-emphasis">
                    Revisa las respuestas del estudiante, asigna retroalimentación y define el estado de la entrega.
                </p>
            </div>

            <!-- <div class="col-12 col-lg-4 text-lg-end">
                <a href="{{ route('admin.field-diary-submissions.index') }}"
                   class="btn btn-light rounded-4 px-4 py-3 fw-semibold">
                    Volver al listado
                </a>
            </div> -->
        </div>
    </section>

    <section class="admin-card bg-white p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
            <div>
                <h2 class="h5 fw-bold mb-1">Información de la entrega</h2>
                <p class="text-muted mb-0">
                    Datos generales de la actividad y del estudiante.
                </p>
            </div>

            @php
                $statusClass = match($submission->status) {
                    'borrador' => 'text-bg-warning',
                    'enviado' => 'text-bg-primary',
                    'revisado' => 'text-bg-success',
                    'devuelto' => 'text-bg-danger',
                    default => 'text-bg-secondary',
                };
            @endphp

            <span class="badge rounded-pill {{ $statusClass }} px-3 py-2">
                {{ $submission->status_label }}
            </span>
        </div>

        <div class="row g-4">
            <div class="col-12 col-lg-6">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Estudiante</div>
                    <div class="fs-5 fw-semibold">{{ $submission->student?->name ?? 'Sin estudiante' }}</div>
                    <div class="text-muted small mt-1">
                        {{ $submission->student?->document_type }}
                        {{ $submission->student?->document_number }}
                        @if($submission->student?->email)
                            · {{ $submission->student->email }}
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Actividad</div>
                    <div class="fs-5 fw-semibold">{{ $activity?->title ?? 'Sin actividad' }}</div>
                    <div class="text-muted small mt-1">
                        {{ $activity?->entry_type_label ?? 'Sin tipo' }}
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Colegio</div>
                    <div class="fs-5 fw-semibold">{{ $submission->school?->name ?? 'Sin colegio' }}</div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Grado / Curso</div>
                    <div class="fs-5 fw-semibold">
                        {{ $submission->grade?->label ?? $submission->grade?->name ?? 'Sin grado' }}
                        /
                        {{ $submission->course?->label ?? $submission->course?->name ?? 'Sin curso' }}
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Fecha de envío</div>
                    <div class="fs-5 fw-semibold">
                        {{ $submission->submitted_at?->format('d/m/Y H:i') ?? 'Sin enviar' }}
                    </div>
                </div>
            </div>

            @if($activity?->weatherStation)
                <div class="col-12 col-lg-6">
                    <div class="border rounded-4 p-3 h-100">
                        <div class="text-muted small fw-semibold mb-1">Estación meteorológica</div>
                        <div class="fs-5 fw-semibold">{{ $activity->weatherStation->name }}</div>
                        <div class="text-muted small mt-1">
                            Código: {{ $activity->weatherStation->code }}
                        </div>
                    </div>
                </div>
            @endif

            @if($submission->reviewer)
                <div class="col-12 col-lg-6">
                    <div class="border rounded-4 p-3 h-100">
                        <div class="text-muted small fw-semibold mb-1">Última revisión</div>
                        <div class="fs-5 fw-semibold">{{ $submission->reviewer->name }}</div>
                        <div class="text-muted small mt-1">
                            {{ $submission->reviewed_at?->format('d/m/Y H:i') }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>

    <section class="admin-card bg-white p-4">
        <div class="mb-4">
            <h2 class="h5 fw-bold mb-1">Respuestas del estudiante</h2>
            <p class="text-muted mb-0">
                Respuestas registradas para cada pregunta de la actividad.
            </p>
        </div>

        <div class="d-flex flex-column gap-3">
            @forelse($activity->questions as $question)
                @php
                    $answer = $answersByQuestion->get($question->id);
                    $answerText = $answer?->answer_text;

                    if ($question->question_type === 'checkbox' && $answerText) {
                        $decoded = json_decode($answerText, true);
                        $answerText = is_array($decoded) ? implode(', ', $decoded) : $answerText;
                    }
                @endphp

                <div class="border rounded-4 p-4">
                    <div class="small text-muted fw-semibold mb-1">
                        Pregunta {{ $question->order }} · {{ $question->question_type_label }}
                        @if($question->is_required)
                            · Obligatoria
                        @endif
                    </div>

                    <div class="fw-semibold mb-3">
                        {{ $question->question_text }}
                    </div>

                    @if($question->question_type === 'file')
                        @if($answer?->answer_file_path)
                            <a href="{{ asset('storage/' . $answer->answer_file_path) }}"
                               target="_blank"
                               class="btn btn-sm btn-outline-dark rounded-3">
                                Ver archivo de evidencia
                            </a>
                        @else
                            <p class="text-muted mb-0">El estudiante no adjuntó archivo.</p>
                        @endif
                    @else
                        <div class="bg-light rounded-4 p-3">
                            @if(filled($answerText))
                                <p class="mb-0">{{ $answerText }}</p>
                            @else
                                <p class="text-muted mb-0">Sin respuesta registrada.</p>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center text-muted py-5">
                    La actividad no tiene preguntas registradas.
                </div>
            @endforelse
        </div>
    </section>

    <section class="admin-card bg-white p-4">
        <div class="mb-4">
            <h2 class="h5 fw-bold mb-1">Revisión docente</h2>
            <p class="text-muted mb-0">
                Asigna una nota, escribe retroalimentación y marca la entrega como revisada o devuelta.
            </p>
        </div>

        <form method="POST" action="{{ route('admin.field-diary-submissions.review', $submission) }}">
            @csrf

            <div class="row g-4">
                <div class="col-12 col-lg-4">
                    <label for="status" class="form-label fw-semibold">
                        Resultado de revisión <span class="text-danger">*</span>
                    </label>
                    <select id="status"
                            name="status"
                            class="form-select form-select-lg rounded-4 @error('status') is-invalid @enderror">
                        <option value="revisado" @selected(old('status', $submission->status) === 'revisado')>
                            Revisado
                        </option>
                        <option value="devuelto" @selected(old('status', $submission->status) === 'devuelto')>
                            Devuelto para corrección
                        </option>
                    </select>

                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-lg-4">
                    <label for="score" class="form-label fw-semibold">Nota</label>
                    <input type="number"
                           step="0.01"
                           min="0"
                           max="100"
                           id="score"
                           name="score"
                           value="{{ old('score', $submission->score) }}"
                           class="form-control form-control-lg rounded-4 @error('score') is-invalid @enderror"
                           placeholder="Ej. 85">

                    @error('score')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror

                    <div class="form-text">
                        Puedes usar escala 0-100 o adaptar el valor a tu criterio institucional.
                    </div>
                </div>

                <div class="col-12">
                    <label for="teacher_feedback" class="form-label fw-semibold">Retroalimentación</label>
                    <textarea id="teacher_feedback"
                              name="teacher_feedback"
                              rows="5"
                              class="form-control form-control-lg rounded-4 @error('teacher_feedback') is-invalid @enderror"
                              placeholder="Escribe observaciones, fortalezas, aspectos por mejorar o instrucciones para corrección.">{{ old('teacher_feedback', $submission->teacher_feedback) }}</textarea>

                    @error('teacher_feedback')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="d-flex flex-column flex-md-row justify-content-end gap-2 mt-4">
                <a href="{{ route('admin.field-diary-submissions.index') }}"
                   class="btn btn-outline-secondary btn-lg rounded-4 px-4">
                    Cancelar
                </a>

                <button type="submit"
                        class="btn text-white btn-lg rounded-4 px-4 fw-semibold"
                        style="background-color: var(--school-primary);">
                    Guardar revisión
                </button>
            </div>
        </form>
    </section>
@endsection