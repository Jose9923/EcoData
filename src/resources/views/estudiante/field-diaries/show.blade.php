@extends('layouts.app')

@section('title', $activity->title)

@section('content')
    <section class="admin-hero p-4 p-md-5">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-lg-8">
                <div class="text-uppercase small fw-semibold admin-hero-subtitle mb-2">
                    Diario de Campo EcoData
                </div>
                <h1 class="display-6 fw-bold mb-2">{{ $activity->title }}</h1>
                <p class="mb-0 admin-hero-subtitle">
                    Lee las instrucciones, responde las preguntas y envía tu actividad.
                </p>
            </div>

            <div class="col-12 col-lg-4 text-lg-end">
                <a href="{{ route('estudiante.field-diaries.index') }}"
                   class="btn text-white rounded-4 px-4 py-3 fw-semibold"
                   style="background-color: var(--school-primary);">
                    Volver a mis actividades
                </a>
            </div>
        </div>
    </section>

    <section class="admin-card bg-white p-4">
        <div class="row g-4">
            <div class="col-12 col-lg-8">
                <h2 class="h5 fw-bold mb-2">Instrucciones</h2>

                @if($activity->description)
                    <p class="text-muted mb-0">{{ $activity->description }}</p>
                @else
                    <p class="text-muted mb-0">Esta actividad no tiene instrucciones adicionales.</p>
                @endif
            </div>

            <div class="col-12 col-lg-4">
                <div class="border rounded-4 p-3 h-100">
                    <div class="small text-muted fw-semibold mb-2">Información de la actividad</div>

                    <div class="mb-2">
                        <strong>Tipo:</strong> {{ $activity->entry_type_label }}
                    </div>

                    @if($activity->weatherStation)
                        <div class="mb-2">
                            <strong>Estación:</strong> {{ $activity->weatherStation->name }}
                        </div>
                    @endif

                    @if($activity->ends_at)
                        <div class="mb-2">
                            <strong>Fecha de cierre:</strong> {{ $activity->ends_at->format('d/m/Y') }}
                        </div>
                    @endif

                    <div>
                        <strong>Estado:</strong>
                        @if($submission)
                            {{ $submission->status_label }}
                        @else
                            Pendiente
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if($submission && in_array($submission->status, ['revisado', 'devuelto'], true))
        <section class="admin-card bg-white p-4">
            <div class="mb-3">
                <h2 class="h5 fw-bold mb-1">Retroalimentación docente</h2>
                <p class="text-muted mb-0">
                    Revisa los comentarios del docente sobre tu entrega.
                </p>
            </div>

            <div class="row g-4">
                <div class="col-12 col-md-4">
                    <div class="border rounded-4 p-3 h-100">
                        <div class="text-muted small fw-semibold mb-1">Estado</div>
                        <div class="fs-5 fw-semibold">{{ $submission->status_label }}</div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="border rounded-4 p-3 h-100">
                        <div class="text-muted small fw-semibold mb-1">Nota</div>
                        <div class="fs-5 fw-semibold">{{ $submission->score ?? 'Sin nota' }}</div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="border rounded-4 p-3 h-100">
                        <div class="text-muted small fw-semibold mb-1">Fecha de revisión</div>
                        <div class="fs-5 fw-semibold">
                            {{ $submission->reviewed_at?->format('d/m/Y H:i') ?? 'Sin fecha' }}
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="border rounded-4 p-4">
                        @if($submission->teacher_feedback)
                            <p class="mb-0">{{ $submission->teacher_feedback }}</p>
                        @else
                            <p class="text-muted mb-0">No hay retroalimentación registrada.</p>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    @endif

    <section class="admin-card bg-white p-4">
        <div class="mb-4">
            <h2 class="h5 fw-bold mb-1">Respuestas</h2>
            <p class="text-muted mb-0">
                Completa las preguntas de la actividad. Puedes guardar como borrador o enviar tus respuestas.
            </p>
        </div>

        @if($submission && $submission->status === 'enviado')
            <div class="alert alert-primary rounded-4">
                Esta actividad ya fue enviada. Espera la revisión del docente.
            </div>
        @endif

        @if($submission && $submission->status === 'revisado')
            <div class="alert alert-success rounded-4">
                Esta actividad ya fue revisada y no puede modificarse.
            </div>
        @endif

        @if($submission && $submission->status === 'devuelto')
            <div class="alert alert-warning rounded-4">
                Esta actividad fue devuelta por el docente. Puedes corregirla y reenviarla.
            </div>
        @endif

        <form method="POST"
              enctype="multipart/form-data"
              id="field-diary-form">
            @csrf

            <div class="d-flex flex-column gap-4">
                @foreach($activity->questions as $question)
                    @include('estudiante.field-diaries.partials.answer-field', [
                        'question' => $question,
                        'answer' => $answersByQuestion->get($question->id),
                        'disabled' => $submission && in_array($submission->status, ['enviado', 'revisado'], true),
                    ])
                @endforeach
            </div>

            @if(! $submission || in_array($submission->status, ['borrador', 'devuelto'], true))
                <div class="d-flex flex-column flex-md-row justify-content-end gap-2 mt-4">
                    <button type="submit"
                            formaction="{{ route('estudiante.field-diaries.save', $activity) }}"
                            class="btn btn-outline-secondary btn-lg rounded-4 px-4">
                        Guardar borrador
                    </button>

                    <button type="submit"
                            formaction="{{ route('estudiante.field-diaries.submit', $activity) }}"
                            class="btn text-white btn-lg rounded-4 px-4 fw-semibold"
                            style="background-color: var(--school-primary);">
                        Enviar respuestas
                    </button>
                </div>
            @endif
        </form>
    </section>
@endsection