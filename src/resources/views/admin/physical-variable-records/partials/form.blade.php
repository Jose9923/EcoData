@php
    $record = $record ?? null;

    $selectedSchoolId = old('school_id', $selectedSchoolId ?? $record->school_id ?? auth()->user()?->school_id);
    $selectedGradeId = old('grade_id', $selectedGradeId ?? $record->grade_id ?? '');
    $selectedCourseId = old('course_id', $selectedCourseId ?? $record->course_id ?? '');
    $selectedCategoryId = old('category_id', $selectedCategoryId ?? '');

    $selectedSchool = $schools->firstWhere('id', (int) $selectedSchoolId);

    $existingValues = [];
    if ($record) {
        foreach ($record->values as $item) {
            $existingValues[$item->physical_variable_id] = $item->resolved_value;
        }
    }

    $oldValues = old('values', $existingValues);
@endphp

<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="admin-card bg-white p-4 h-100">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Colegio</label>

                    @if(auth()->user()?->hasRole('super_admin'))
                        <select name="school_id" id="school_id" class="form-select rounded-4">
                            <option value="">Selecciona un colegio</option>
                            @foreach($schools as $school)
                                <option value="{{ $school->id }}"
                                    @selected((string) $selectedSchoolId === (string) $school->id)>
                                    {{ $school->name }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <input type="hidden" name="school_id" id="school_id" value="{{ $selectedSchoolId }}">

                        <input type="text"
                               class="form-control rounded-4 bg-light"
                               value="{{ $selectedSchool?->name ?? auth()->user()?->school?->name ?? 'Colegio asignado' }}"
                               disabled>

                        <div class="form-text">
                            Tu rol solo permite registrar información del colegio asignado.
                        </div>
                    @endif

                    @error('school_id')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Grado</label>
                    <select name="grade_id" id="grade_id" class="form-select rounded-4">
                        <option value="">Todos / Sin asignar</option>
                        @foreach($grades as $grade)
                            <option value="{{ $grade->id }}"
                                @selected((string) $selectedGradeId === (string) $grade->id)>
                                {{ $grade->label ?: $grade->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('grade_id')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Curso</label>
                    <select name="course_id" id="course_id" class="form-select rounded-4">
                        <option value="">Todos / Sin asignar</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}"
                                @selected((string) $selectedCourseId === (string) $course->id)>
                                {{ $course->label ?: $course->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('course_id')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Categoría</label>
                    <select name="category_id" id="category_id" class="form-select rounded-4">
                        <option value="">Todas las categorías</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}"
                                @selected((string) $selectedCategoryId === (string) $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Fecha y hora</label>
                    <input type="datetime-local"
                           name="recorded_at"
                           value="{{ old('recorded_at', $recordedAt) }}"
                           class="form-control rounded-4">
                    @error('recorded_at')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Observaciones</label>
                    <textarea name="observations"
                              rows="4"
                              class="form-control rounded-4"
                              placeholder="Anotaciones opcionales del registro...">{{ old('observations', $record->observations ?? '') }}</textarea>

                    @error('observations')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror

                    @error('values')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="admin-card bg-white p-4 h-100">
            <h6 class="fw-bold mb-3">Indicaciones</h6>
            <div class="small text-secondary d-flex flex-column gap-2">
                <div>
                    @if(auth()->user()?->hasRole('super_admin'))
                        Selecciona un colegio para habilitar las variables disponibles.
                    @else
                        El colegio se asigna automáticamente según tu usuario.
                    @endif
                </div>
                <div>La categoría es opcional y solo sirve para acotar el formulario.</div>
                <div>Debes registrar al menos una variable para guardar.</div>
                <div>Los límites mínimo, máximo y decimales se validan automáticamente.</div>
            </div>
        </div>
    </div>
</div>

<div class="admin-card bg-white p-4 mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">Variables disponibles</h5>
        <small class="text-secondary">Completa solo las que correspondan</small>
    </div>

    <div id="variables-container" class="row g-4">
        <div class="col-12">
            <div class="text-secondary small">
                @if($selectedSchoolId)
                    Cargando variables disponibles...
                @else
                    Selecciona un colegio para cargar variables.
                @endif
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a href="{{ route('admin.physical-variable-records.index') }}" class="btn btn-outline-secondary rounded-4 px-4">
        Cancelar
    </a>
    <button type="submit" class="btn text-white rounded-4 px-4" style="background-color: var(--school-primary);">
        {{ $buttonText ?? 'Guardar registro' }}
    </button>
</div>

@php
    $existingVariablesJson = ($variables ?? collect())
        ->map(function ($variable) {
            return [
                'id' => $variable->id,
                'name' => $variable->name,
                'category' => $variable->category?->name,
                'data_type' => $variable->data_type,
                'unit' => $variable->unit,
                'min_value' => $variable->min_value,
                'max_value' => $variable->max_value,
                'decimals' => $variable->decimals,
                'description' => $variable->description,
            ];
        })
        ->values()
        ->toArray();
@endphp

<script>
document.addEventListener('DOMContentLoaded', function () {
    const schoolSelect = document.getElementById('school_id');
    const gradeSelect = document.getElementById('grade_id');
    const courseSelect = document.getElementById('course_id');
    const categorySelect = document.getElementById('category_id');
    const container = document.getElementById('variables-container');

    const gradesUrl = @json(route('admin.physical-variable-records.ajax.grades'));
    const coursesUrl = @json(route('admin.physical-variable-records.ajax.courses'));
    const variablesUrl = @json(route('admin.physical-variable-records.ajax.variables'));

    const oldValues = @json($oldValues);
    const existingVariables = @json($existingVariablesJson);
    const selectedSchoolId = @json((string) $selectedSchoolId);
    const selectedGradeId = @json((string) $selectedGradeId);
    const selectedCourseId = @json((string) $selectedCourseId);
    const validationErrors = @json($errors->toArray());

    function currentSchoolId() {
        return schoolSelect?.value || selectedSchoolId;
    }

    function optionHTML(items, placeholder, selectedValue = '') {
        let html = `<option value="">${placeholder}</option>`;

        items.forEach(item => {
            const selected = String(item.id) === String(selectedValue) ? 'selected' : '';
            html += `<option value="${escapeHtml(String(item.id))}" ${selected}>${escapeHtml(item.label)}</option>`;
        });

        return html;
    }

    async function fetchJson(url) {
        const res = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!res.ok) {
            throw new Error('No fue posible cargar la información solicitada.');
        }

        return await res.json();
    }

    async function loadGrades(selected = '') {
        const schoolId = currentSchoolId();

        if (!schoolId) {
            gradeSelect.innerHTML = '<option value="">Todos / Sin asignar</option>';
            courseSelect.innerHTML = '<option value="">Todos / Sin asignar</option>';
            return;
        }

        gradeSelect.innerHTML = '<option value="">Cargando grados...</option>';
        courseSelect.innerHTML = '<option value="">Todos / Sin asignar</option>';

        try {
            const data = await fetchJson(`${gradesUrl}?school_id=${encodeURIComponent(schoolId)}`);
            gradeSelect.innerHTML = optionHTML(data, 'Todos / Sin asignar', selected);
        } catch (error) {
            console.error(error);
            gradeSelect.innerHTML = '<option value="">Error cargando grados</option>';
        }
    }

    async function loadCourses(selected = '') {
        const schoolId = currentSchoolId();

        if (!schoolId || !gradeSelect.value) {
            courseSelect.innerHTML = '<option value="">Todos / Sin asignar</option>';
            return;
        }

        const params = new URLSearchParams({
            school_id: schoolId,
            grade_id: gradeSelect.value
        });

        courseSelect.innerHTML = '<option value="">Cargando cursos...</option>';

        try {
            const data = await fetchJson(`${coursesUrl}?${params.toString()}`);
            courseSelect.innerHTML = optionHTML(data, 'Todos / Sin asignar', selected);
        } catch (error) {
            console.error(error);
            courseSelect.innerHTML = '<option value="">Error cargando cursos</option>';
        }
    }

    function renderVariableField(variable) {
        let oldValue = oldValues[variable.id] ?? '';

        if (variable.data_type === 'integer' && oldValue !== '' && oldValue !== null) {
            oldValue = parseInt(oldValue, 10);
        }

        if (variable.data_type === 'decimal' && oldValue !== '' && oldValue !== null) {
            oldValue = Number(oldValue);
        }

        if (variable.data_type === 'text' && oldValue !== null && oldValue !== undefined) {
            oldValue = String(oldValue);
        }

        if (variable.data_type === 'boolean' && oldValue !== '' && oldValue !== null && oldValue !== undefined) {
            oldValue = (oldValue === true || oldValue === 1 || oldValue === '1') ? '1' : '0';
        }

        if (variable.data_type === 'date' && oldValue !== '' && oldValue !== null) {
            oldValue = String(oldValue).substring(0, 10);
        }

        const fieldError = validationErrors[`values.${variable.id}`]?.[0] ?? '';

        let input = '';

        if (variable.data_type === 'integer' || variable.data_type === 'decimal') {
            input = `
                <input
                    type="number"
                    step="${variable.data_type === 'decimal' ? decimalStep(variable.decimals ?? 0) : '1'}"
                    name="values[${escapeHtml(String(variable.id))}]"
                    value="${escapeHtml(oldValue ?? '')}"
                    class="form-control rounded-4 ${fieldError ? 'is-invalid' : ''}"
                    ${variable.min_value !== null ? `min="${escapeHtml(String(variable.min_value))}"` : ''}
                    ${variable.max_value !== null ? `max="${escapeHtml(String(variable.max_value))}"` : ''}
                >
            `;
        } else if (variable.data_type === 'text') {
            input = `
                <textarea
                    name="values[${escapeHtml(String(variable.id))}]"
                    rows="3"
                    class="form-control rounded-4 ${fieldError ? 'is-invalid' : ''}"
                >${escapeHtml(oldValue ?? '')}</textarea>
            `;
        } else if (variable.data_type === 'boolean') {
            input = `
                <select name="values[${escapeHtml(String(variable.id))}]" class="form-select rounded-4 ${fieldError ? 'is-invalid' : ''}">
                    <option value="">Selecciona</option>
                    <option value="1" ${String(oldValue) === '1' || oldValue === true ? 'selected' : ''}>Sí</option>
                    <option value="0" ${String(oldValue) === '0' || oldValue === false ? 'selected' : ''}>No</option>
                </select>
            `;
        } else if (variable.data_type === 'date') {
            input = `
                <input
                    type="date"
                    name="values[${escapeHtml(String(variable.id))}]"
                    value="${escapeHtml(oldValue ?? '')}"
                    class="form-control rounded-4 ${fieldError ? 'is-invalid' : ''}"
                >
            `;
        }

        return `
            <div class="col-12 col-md-6">
                <div class="border rounded-4 p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="fw-semibold">${escapeHtml(variable.name)}</div>
                            <div class="small text-secondary">${escapeHtml(variable.category ?? 'Sin categoría')}</div>
                        </div>
                        <span class="badge text-bg-light rounded-pill">${escapeHtml(variable.data_type)}</span>
                    </div>

                    <div class="small text-secondary mb-2">
                        Unidad: ${escapeHtml(variable.unit ?? '—')}
                    </div>

                    ${input}

                    ${fieldError ? `<div class="invalid-feedback d-block">${escapeHtml(fieldError)}</div>` : ''}

                    <div class="small text-secondary mt-2">
                        Mín: ${formatNumber(variable.min_value, variable.decimals ?? 0)}${variable.unit ? ' ' + escapeHtml(variable.unit) : ''} |
                        Máx: ${formatNumber(variable.max_value, variable.decimals ?? 0)}${variable.unit ? ' ' + escapeHtml(variable.unit) : ''}
                    </div>

                    ${variable.description ? `<div class="small text-secondary mt-2">${escapeHtml(variable.description)}</div>` : ''}
                </div>
            </div>
        `;
    }

    async function loadVariables() {
        const schoolId = currentSchoolId();

        if (!schoolId) {
            container.innerHTML = `
                <div class="col-12">
                    <div class="text-secondary small">Selecciona un colegio para cargar variables.</div>
                </div>
            `;
            return;
        }

        const params = new URLSearchParams({ school_id: schoolId });

        if (categorySelect.value) {
            params.append('category_id', categorySelect.value);
        }

        container.innerHTML = `
            <div class="col-12">
                <div class="text-secondary small">Cargando variables...</div>
            </div>
        `;

        try {
            const data = await fetchJson(`${variablesUrl}?${params.toString()}`);

            if (!data.length) {
                container.innerHTML = `
                    <div class="col-12">
                        <div class="text-secondary small">No hay variables activas para esta combinación.</div>
                    </div>
                `;
                return;
            }

            container.innerHTML = data.map(renderVariableField).join('');
        } catch (error) {
            console.error(error);
            container.innerHTML = `
                <div class="col-12">
                    <div class="text-danger small">No fue posible cargar las variables disponibles.</div>
                </div>
            `;
        }
    }

    schoolSelect?.addEventListener('change', async function () {
        await loadGrades();
        await loadCourses();
        await loadVariables();
    });

    gradeSelect?.addEventListener('change', async function () {
        await loadCourses();
    });

    categorySelect?.addEventListener('change', async function () {
        await loadVariables();
    });

    if (existingVariables.length > 0) {
        container.innerHTML = existingVariables.map(renderVariableField).join('');
    }

    if (currentSchoolId()) {
        loadGrades(selectedGradeId).then(() => loadCourses(selectedCourseId));

        if (existingVariables.length === 0) {
            loadVariables();
        }
    }

    function formatNumber(value, decimals = 0) {
        if (value === null || value === undefined || value === '') return '—';

        const num = Number(value);

        if (Number.isNaN(num)) return '—';

        return num.toFixed(Number(decimals ?? 0));
    }

    function decimalStep(decimals = 0) {
        decimals = Number(decimals ?? 0);

        if (decimals <= 0) {
            return '1';
        }

        return '0.' + '0'.repeat(decimals - 1) + '1';
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }
});
</script>