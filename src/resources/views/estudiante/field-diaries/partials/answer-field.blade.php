@php
    $currentValue = old('answers.' . $question->id, $answer?->answer_text);

    if ($question->question_type === 'checkbox' && is_string($currentValue)) {
        $decoded = json_decode($currentValue, true);
        $currentValue = is_array($decoded) ? $decoded : [];
    }

    $isDisabled = $disabled ?? false;
@endphp

<div class="border rounded-4 p-4 bg-light">
    <div class="mb-3">
        <div class="small text-muted fw-semibold mb-1">
            Pregunta {{ $question->order }} · {{ $question->question_type_label }}
            @if($question->is_required)
                · Obligatoria
            @endif
        </div>

        <label class="form-label fw-semibold mb-0">
            {{ $question->question_text }}
        </label>
    </div>

    @switch($question->question_type)
        @case('text')
            <input type="text"
                   name="answers[{{ $question->id }}]"
                   value="{{ $currentValue }}"
                   class="form-control form-control-lg rounded-4 @error('answers.' . $question->id) is-invalid @enderror"
                   @disabled($isDisabled)>

            @error('answers.' . $question->id)
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            @break

        @case('textarea')
            <textarea name="answers[{{ $question->id }}]"
                      rows="5"
                      class="form-control form-control-lg rounded-4 @error('answers.' . $question->id) is-invalid @enderror"
                      @disabled($isDisabled)>{{ $currentValue }}</textarea>

            @error('answers.' . $question->id)
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            @break

        @case('number')
            <input type="number"
                   step="any"
                   name="answers[{{ $question->id }}]"
                   value="{{ $currentValue }}"
                   class="form-control form-control-lg rounded-4 @error('answers.' . $question->id) is-invalid @enderror"
                   @disabled($isDisabled)>

            @error('answers.' . $question->id)
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            @break

        @case('date')
            <input type="date"
                   name="answers[{{ $question->id }}]"
                   value="{{ $currentValue }}"
                   class="form-control form-control-lg rounded-4 @error('answers.' . $question->id) is-invalid @enderror"
                   @disabled($isDisabled)>

            @error('answers.' . $question->id)
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            @break

        @case('select')
            <select name="answers[{{ $question->id }}]"
                    class="form-select form-select-lg rounded-4 @error('answers.' . $question->id) is-invalid @enderror"
                    @disabled($isDisabled)>
                <option value="">Selecciona una opción</option>
                @foreach($question->options ?? [] as $option)
                    <option value="{{ $option }}" @selected($currentValue === $option)>
                        {{ $option }}
                    </option>
                @endforeach
            </select>

            @error('answers.' . $question->id)
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            @break

        @case('radio')
            <div class="d-flex flex-column gap-2">
                @foreach($question->options ?? [] as $option)
                    <div class="form-check">
                        <input type="radio"
                               id="question_{{ $question->id }}_{{ $loop->index }}"
                               name="answers[{{ $question->id }}]"
                               value="{{ $option }}"
                               class="form-check-input"
                               @checked($currentValue === $option)
                               @disabled($isDisabled)>
                        <label for="question_{{ $question->id }}_{{ $loop->index }}" class="form-check-label">
                            {{ $option }}
                        </label>
                    </div>
                @endforeach
            </div>

            @error('answers.' . $question->id)
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
            @break

        @case('checkbox')
            <div class="d-flex flex-column gap-2">
                @foreach($question->options ?? [] as $option)
                    <div class="form-check">
                        <input type="checkbox"
                               id="question_{{ $question->id }}_{{ $loop->index }}"
                               name="answers[{{ $question->id }}][]"
                               value="{{ $option }}"
                               class="form-check-input"
                               @checked(in_array($option, $currentValue ?? [], true))
                               @disabled($isDisabled)>
                        <label for="question_{{ $question->id }}_{{ $loop->index }}" class="form-check-label">
                            {{ $option }}
                        </label>
                    </div>
                @endforeach
            </div>

            @error('answers.' . $question->id)
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
            @break

        @case('file')
            @if($answer?->answer_file_path)
                <div class="mb-3">
                    <a href="{{ asset('storage/' . $answer->answer_file_path) }}"
                       target="_blank"
                       class="btn btn-sm btn-outline-dark rounded-3">
                        Ver archivo actual
                    </a>
                </div>
            @endif

            <input type="file"
                   name="files[{{ $question->id }}]"
                   class="form-control form-control-lg rounded-4 @error('files.' . $question->id) is-invalid @enderror"
                   @disabled($isDisabled)>

            <div class="form-text">
                Formatos permitidos: imágenes, PDF, Word, Excel o CSV. Tamaño máximo: 5 MB.
            </div>

            @error('files.' . $question->id)
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            @break
    @endswitch
</div>