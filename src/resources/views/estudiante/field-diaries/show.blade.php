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
                        <span id="diary-state-label">{{ $submission?->status_label ?? 'Pendiente' }}</span>
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

        @if($submission && $submission->status === 'devuelto' && $canEdit)
            <div class="alert alert-warning rounded-4">
                Esta actividad fue devuelta por el docente. Puedes corregirla y reenviarla.
            </div>
        @endif

        @if(! $isOpen)
            <div class="alert alert-info rounded-4">La actividad está cerrada. Puedes consultar tus respuestas y la retroalimentación; el docente debe reabrirla para recibir correcciones.</div>
        @endif
        @error('revision')
            <div class="alert alert-danger rounded-4">{{ $message }}</div>
        @enderror
        <form method="POST" action="{{ route('estudiante.field-diaries.save', $activity) }}"
              enctype="multipart/form-data"
              id="field-diary-form" data-managed-submit="true">
            @csrf
            <input type="hidden" name="revision" value="{{ old('revision', $revision) }}">
            @if($canEdit)
                <div class="border rounded-4 p-3 mb-4">
                    <div id="diary-autosave-status" role="status" aria-live="polite" class="fw-semibold">Guardado automático listo.</div>
                    <div class="small text-muted">Las respuestas se guardan al dejar de escribir. Los archivos se suben únicamente al pulsar Guardar borrador o Enviar respuestas.</div>
                </div>
            @endif

            <div class="d-flex flex-column gap-4">
                @foreach($activity->questions as $question)
                    @include('estudiante.field-diaries.partials.answer-field', [
                        'question' => $question,
                        'answer' => $answersByQuestion->get($question->id),
                        'disabled' => ! $canEdit,
                    ])
                @endforeach
            </div>

            @if($canEdit)
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

@if($canEdit)
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const form = document.getElementById('field-diary-form');
                const indicator = document.getElementById('diary-autosave-status');
                const revision = form.elements.namedItem('revision');
                const saveUrl = @json(route('estudiante.field-diaries.save', $activity));
                let timer;
                let inFlight = null;
                let generation = 0;
                let savedGeneration = 0;
                let stopped = false;
                let manualSubmitting = false;
                let waitingForSubmit = false;
                let navigating = false;

                function pendingFiles() {
                    return [...form.querySelectorAll('input[type="file"]')].some(input => input.files.length > 0);
                }

                function message(text, isError = false) {
                    indicator.textContent = text + (pendingFiles() ? ' Hay archivos pendientes de guardar manualmente.' : '');
                    indicator.classList.toggle('text-danger', isError);
                }

                function answersSnapshot() {
                    const answers = {};
                    // Empty checkbox groups must be explicit so unchecking all options is saved.
                    form.querySelectorAll('[name^="answers["]').forEach(input => {
                        const match = input.name.match(/^answers\[(\d+)\](\[\])?$/);
                        if (match) answers[match[1]] = match[2] ? [] : '';
                    });
                    for (const [name, value] of new FormData(form)) {
                        const match = name.match(/^answers\[(\d+)\](\[\])?$/);
                        if (!match) continue;
                        if (match[2]) answers[match[1]].push(value);
                        else answers[match[1]] = value;
                    }
                    return answers;
                }

                function schedule() {
                    clearTimeout(timer);
                    if (!stopped) timer = setTimeout(saveDraft, 1000);
                }

                function saveDraft() {
                    if (stopped || inFlight || generation === savedGeneration) return;
                    if (!navigator.onLine) {
                        message('Sin conexión. Tus cambios siguen en esta página; no la cierres.', true);
                        return;
                    }
                    const sentGeneration = generation;
                    const controller = new AbortController();
                    const timeout = setTimeout(() => controller.abort(), 15000);
                    message('Guardando borrador…');
                    inFlight = (async () => {
                        try {
                            const response = await fetch(saveUrl, {
                                method: 'POST',
                                credentials: 'same-origin',
                                signal: controller.signal,
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': form.elements.namedItem('_token').value,
                                },
                                body: JSON.stringify({ autosave: true, revision: revision.value, answers: answersSnapshot() }),
                            });
                            const data = await response.json().catch(() => ({}));
                            if (!response.ok) {
                                if ([401, 403, 409, 419].includes(response.status)) {
                                    stopped = true;
                                    form.dataset.saveBlocked = 'true';
                                    message(data.message || 'La sesión o la actividad cambió. Copia tus respuestas y recarga la página.', true);
                                } else if (response.status === 422) {
                                    const firstError = Object.values(data.errors || {}).flat()[0];
                                    message(firstError || 'Revisa las respuestas señaladas antes de guardar.', true);
                                } else {
                                    message('No se pudo confirmar el guardado. Conserva esta página y vuelve a intentarlo.', true);
                                }
                                return;
                            }
                            revision.value = data.revision;
                            const stateLabel = document.getElementById('diary-state-label');
                            const savedLabels = { borrador: 'Borrador', devuelto: 'Devuelto' };
                            if (stateLabel && savedLabels[data.status]) stateLabel.textContent = savedLabels[data.status];
                            savedGeneration = sentGeneration;
                            message(generation === savedGeneration ? 'Borrador guardado a las ' + new Date(data.saved_at).toLocaleTimeString() + '.' : 'Hay cambios pendientes de guardar.');
                        } catch (error) {
                            message('No se pudo confirmar el guardado. No cierres esta página; puedes intentar Guardar borrador.', true);
                        } finally {
                            clearTimeout(timeout);
                            inFlight = null;
                            if (!stopped && generation > sentGeneration) schedule();
                        }
                    })();
                }

                form.addEventListener('input', event => {
                    if (!event.target.name?.startsWith('answers[')) return;
                    generation++;
                    message('Cambios pendientes de guardar.');
                    schedule();
                });
                form.addEventListener('change', event => {
                    if (event.target.type === 'file') message('Los archivos se guardan al pulsar uno de los botones del formulario.');
                });
                window.addEventListener('online', () => {
                    if (!stopped && generation !== savedGeneration) schedule();
                });
                window.addEventListener('beforeunload', event => {
                    if (!navigating && (generation !== savedGeneration || inFlight || pendingFiles())) {
                        event.preventDefault();
                        event.returnValue = '';
                    }
                });
                window.addEventListener('pageshow', event => {
                    if (!event.persisted) return;
                    manualSubmitting = false;
                    waitingForSubmit = false;
                    navigating = false;
                    stopped = form.dataset.saveBlocked === 'true';
                    delete form.dataset.submitted;
                    form.querySelectorAll('button[type="submit"]').forEach(button => button.disabled = false);
                });
                form.addEventListener('submit', async event => {
                    if (form.dataset.submitted === 'true') {
                        event.preventDefault();
                        return;
                    }
                    if (manualSubmitting) {
                        form.dataset.submitted = 'true';
                        form.querySelectorAll('button[type="submit"]').forEach(button => button.disabled = true);
                        return;
                    }
                    event.preventDefault();
                    if (waitingForSubmit) return;
                    waitingForSubmit = true;
                    const button = event.submitter;
                    stopped = true;
                    clearTimeout(timer);
                    if (inFlight) await inFlight;
                    if (form.dataset.saveBlocked === 'true') {
                        waitingForSubmit = false;
                        return;
                    }
                    // Wait for the active autosave so the final POST carries its current revision.
                    manualSubmitting = true;
                    navigating = true;
                    form.requestSubmit(button || undefined);
                });
            });
        </script>
    @endpush
@endif
