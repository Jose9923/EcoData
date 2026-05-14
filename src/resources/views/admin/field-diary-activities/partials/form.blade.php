@php
    $isEdit = isset($activity) && $activity;

    $oldQuestions = old('questions');

    if ($oldQuestions === null && $isEdit) {
        $oldQuestions = $activity->questions->map(function ($question) {
            return [
                'question_text' => $question->question_text,
                'question_type' => $question->question_type,
                'options_text' => is_array($question->options) ? implode(PHP_EOL, $question->options) : '',
                'order' => $question->order,
                'is_required' => $question->is_required ? 1 : 0,
            ];
        })->toArray();
    }

    if (empty($oldQuestions)) {
        $oldQuestions = [
            [
                'question_text' => '',
                'question_type' => 'textarea',
                'options_text' => '',
                'order' => 1,
                'is_required' => 1,
            ],
        ];
    }
@endphp

<div class="row g-4">
    @if(auth()->user()->hasRole('super_admin'))
        <div class="col-12 col-lg-6">
            <label for="school_id" class="form-label fw-semibold">
                Colegio <span class="text-danger">*</span>
            </label>
            <select id="school_id"
                    name="school_id"
                    class="form-select form-select-lg rounded-4 @error('school_id') is-invalid @enderror">
                <option value="">Selecciona un colegio</option>
                @foreach($schools as $school)
                    <option value="{{ $school->id }}"
                        @selected((int) old('school_id', $selectedSchoolId ?? $activity?->school_id) === (int) $school->id)>
                        {{ $school->name }}
                    </option>
                @endforeach
            </select>

            @error('school_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    @endif

    <div class="col-12 col-lg-6">
        <label for="entry_type" class="form-label fw-semibold">
            Tipo de actividad <span class="text-danger">*</span>
        </label>
        <select id="entry_type"
                name="entry_type"
                class="form-select form-select-lg rounded-4 @error('entry_type') is-invalid @enderror">
            @foreach($entryTypes as $value => $label)
                <option value="{{ $value }}" @selected(old('entry_type', $activity?->entry_type ?? 'observacion') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>

        @error('entry_type')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="title" class="form-label fw-semibold">
            Título de la actividad <span class="text-danger">*</span>
        </label>
        <input type="text"
               id="title"
               name="title"
               value="{{ old('title', $activity?->title) }}"
               class="form-control form-control-lg rounded-4 @error('title') is-invalid @enderror"
               placeholder="Ej. Análisis de temperatura y humedad de la semana">

        @error('title')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="description" class="form-label fw-semibold">Descripción e instrucciones</label>
        <textarea id="description"
                  name="description"
                  rows="5"
                  class="form-control form-control-lg rounded-4 @error('description') is-invalid @enderror"
                  placeholder="Escribe las instrucciones que verán los estudiantes.">{{ old('description', $activity?->description) }}</textarea>

        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="grade_id" class="form-label fw-semibold">Grado</label>
        <select id="grade_id"
                name="grade_id"
                class="form-select form-select-lg rounded-4 @error('grade_id') is-invalid @enderror">
            <option value="">Todos los grados</option>
            @foreach($grades as $grade)
                <option value="{{ $grade->id }}"
                    @selected((int) old('grade_id', $selectedGradeId ?? $activity?->grade_id) === (int) $grade->id)>
                    {{ $grade->label ?: $grade->name }}
                </option>
            @endforeach
        </select>

        @error('grade_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="course_id" class="form-label fw-semibold">Curso</label>
        <select id="course_id"
                name="course_id"
                class="form-select form-select-lg rounded-4 @error('course_id') is-invalid @enderror">
            <option value="">Todos los cursos</option>
            @foreach($courses as $course)
                <option value="{{ $course->id }}"
                    @selected((int) old('course_id', $activity?->course_id) === (int) $course->id)>
                    {{ $course->label ?: $course->name }}
                </option>
            @endforeach
        </select>

        @error('course_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-lg-6">
        <label for="weather_station_id" class="form-label fw-semibold">Estación meteorológica asociada</label>
        <select id="weather_station_id"
                name="weather_station_id"
                class="form-select form-select-lg rounded-4 @error('weather_station_id') is-invalid @enderror">
            <option value="">Sin estación asociada</option>
            @foreach($weatherStations as $station)
                <option value="{{ $station->id }}"
                    @selected((int) old('weather_station_id', $activity?->weather_station_id) === (int) $station->id)>
                    {{ $station->name }} · {{ $station->code }}
                    @if(auth()->user()->hasRole('super_admin'))
                        · {{ $station->school?->name }}
                    @endif
                </option>
            @endforeach
        </select>

        @error('weather_station_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-lg-6">
        <label for="is_active" class="form-label fw-semibold">
            Estado <span class="text-danger">*</span>
        </label>
        <select id="is_active"
                name="is_active"
                class="form-select form-select-lg rounded-4 @error('is_active') is-invalid @enderror">
            <option value="1" @selected((string) old('is_active', $activity?->is_active ?? 1) === '1')>
                Activa
            </option>
            <option value="0" @selected((string) old('is_active', $activity?->is_active ?? 1) === '0')>
                Inactiva
            </option>
        </select>

        @error('is_active')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="starts_at" class="form-label fw-semibold">Fecha de inicio</label>
        <input type="date"
               id="starts_at"
               name="starts_at"
               value="{{ old('starts_at', optional($activity?->starts_at)->format('Y-m-d')) }}"
               class="form-control form-control-lg rounded-4 @error('starts_at') is-invalid @enderror">

        @error('starts_at')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="ends_at" class="form-label fw-semibold">Fecha de cierre</label>
        <input type="date"
               id="ends_at"
               name="ends_at"
               value="{{ old('ends_at', optional($activity?->ends_at)->format('Y-m-d')) }}"
               class="form-control form-control-lg rounded-4 @error('ends_at') is-invalid @enderror">

        @error('ends_at')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<section class="border rounded-4 p-4 mt-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
        <div>
            <h2 class="h5 fw-bold mb-1">Preguntas de la actividad</h2>
            <p class="text-muted mb-0">
                Agrega las preguntas que responderán los estudiantes.
            </p>
        </div>

        <button type="button"
                id="add-question"
                class="btn btn-outline-dark rounded-4 px-4">
            + Agregar pregunta
        </button>
    </div>

    @error('questions')
        <div class="alert alert-danger rounded-4">{{ $message }}</div>
    @enderror

    <div id="questions-wrapper" class="d-flex flex-column gap-3">
        @foreach($oldQuestions as $index => $question)
            <div class="question-item border rounded-4 p-4 bg-light">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                    <div class="fw-bold">Pregunta <span class="question-number">{{ $loop->iteration }}</span></div>

                    <button type="button"
                            class="btn btn-sm btn-outline-danger rounded-3 remove-question">
                        Eliminar
                    </button>
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Texto de la pregunta</label>
                        <textarea name="questions[{{ $index }}][question_text]"
                                  rows="3"
                                  class="form-control rounded-4 @error("questions.$index.question_text") is-invalid @enderror"
                                  placeholder="Escribe la pregunta que responderá el estudiante.">{{ $question['question_text'] ?? '' }}</textarea>

                        @error("questions.$index.question_text")
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold">Tipo de pregunta</label>
                        <select name="questions[{{ $index }}][question_type]"
                                class="form-select rounded-4 question-type @error("questions.$index.question_type") is-invalid @enderror">
                            @foreach($questionTypes as $value => $label)
                                <option value="{{ $value }}" @selected(($question['question_type'] ?? 'textarea') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>

                        @error("questions.$index.question_type")
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold">Orden</label>
                        <input type="number"
                               min="1"
                               name="questions[{{ $index }}][order]"
                               value="{{ $question['order'] ?? $loop->iteration }}"
                               class="form-control rounded-4">
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold">Obligatoria</label>
                        <select name="questions[{{ $index }}][is_required]"
                                class="form-select rounded-4">
                            <option value="1" @selected((string) ($question['is_required'] ?? 1) === '1')>Sí</option>
                            <option value="0" @selected((string) ($question['is_required'] ?? 1) === '0')>No</option>
                        </select>
                    </div>

                    <div class="col-12 options-block {{ in_array($question['question_type'] ?? 'textarea', ['select', 'radio', 'checkbox'], true) ? '' : 'd-none' }}">
                        <label class="form-label fw-semibold">Opciones</label>
                        <textarea name="questions[{{ $index }}][options_text]"
                                  rows="4"
                                  class="form-control rounded-4 @error("questions.$index.options_text") is-invalid @enderror"
                                  placeholder="Escribe una opción por línea.">{{ $question['options_text'] ?? '' }}</textarea>

                        @error("questions.$index.options_text")
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        <div class="form-text">
                            Solo aplica para lista desplegable, selección única o selección múltiple.
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const wrapper = document.getElementById('questions-wrapper');
            const addButton = document.getElementById('add-question');

            function refreshQuestionNumbers() {
                wrapper.querySelectorAll('.question-item').forEach((item, index) => {
                    item.querySelector('.question-number').textContent = index + 1;

                    item.querySelectorAll('textarea, input, select').forEach((field) => {
                        if (!field.name) return;
                        field.name = field.name.replace(/questions\[\d+\]/, `questions[${index}]`);
                    });

                    const orderInput = item.querySelector('input[name$="[order]"]');
                    if (orderInput && !orderInput.value) {
                        orderInput.value = index + 1;
                    }
                });
            }

            function toggleOptions(select) {
                const item = select.closest('.question-item');
                const optionsBlock = item.querySelector('.options-block');
                const typesWithOptions = ['select', 'radio', 'checkbox'];

                if (typesWithOptions.includes(select.value)) {
                    optionsBlock.classList.remove('d-none');
                } else {
                    optionsBlock.classList.add('d-none');
                }
            }

            function bindQuestionEvents(item) {
                const removeButton = item.querySelector('.remove-question');
                const typeSelect = item.querySelector('.question-type');

                removeButton.addEventListener('click', function () {
                    const total = wrapper.querySelectorAll('.question-item').length;

                    if (total <= 1) {
                        alert('La actividad debe tener al menos una pregunta.');
                        return;
                    }

                    item.remove();
                    refreshQuestionNumbers();
                });

                typeSelect.addEventListener('change', function () {
                    toggleOptions(typeSelect);
                });
            }

            wrapper.querySelectorAll('.question-item').forEach(bindQuestionEvents);

            addButton.addEventListener('click', function () {
                const total = wrapper.querySelectorAll('.question-item').length;
                const first = wrapper.querySelector('.question-item');
                const clone = first.cloneNode(true);

                clone.querySelectorAll('textarea').forEach((field) => field.value = '');
                clone.querySelectorAll('input').forEach((field) => {
                    if (field.name.endsWith('[order]')) {
                        field.value = total + 1;
                    } else {
                        field.value = '';
                    }
                });

                clone.querySelectorAll('select').forEach((field) => {
                    if (field.name.endsWith('[question_type]')) {
                        field.value = 'textarea';
                    }

                    if (field.name.endsWith('[is_required]')) {
                        field.value = '1';
                    }
                });

                const optionsBlock = clone.querySelector('.options-block');
                optionsBlock.classList.add('d-none');

                wrapper.appendChild(clone);
                refreshQuestionNumbers();
                bindQuestionEvents(clone);
            });
        });
    </script>
@endpush