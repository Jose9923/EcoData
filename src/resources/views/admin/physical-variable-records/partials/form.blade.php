@php
    $record = $record ?? null;

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
                    <select name="school_id" id="school_id" class="form-select rounded-4">
                        <option value="">Selecciona un colegio</option>
                        @foreach($schools as $school)
                            <option value="{{ $school->id }}" @selected(old('school_id', $record->school_id ?? '') == $school->id)>
                                {{ $school->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('school_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Grado</label>
                    <select name="grade_id" id="grade_id" class="form-select rounded-4">
                        <option value="">Todos / Sin asignar</option>
                        @foreach($grades as $grade)
                            <option value="{{ $grade->id }}" @selected(old('grade_id', $record->grade_id ?? '') == $grade->id)>
                                {{ $grade->label ?: $grade->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('grade_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Curso</label>
                    <select name="course_id" id="course_id" class="form-select rounded-4">
                        <option value="">Todos / Sin asignar</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" @selected(old('course_id', $record->course_id ?? '') == $course->id)>
                                {{ $course->label ?: $course->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('course_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Categoría</label>
                    <select name="category_id" id="category_id" class="form-select rounded-4">
                        <option value="">Todas las categorías</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Fecha y hora</label>
                    <input type="datetime-local" name="recorded_at"
                           value="{{ old('recorded_at', $recordedAt) }}"
                           class="form-control rounded-4">
                    @error('recorded_at') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Observaciones</label>
                    <textarea name="observations" rows="4" class="form-control rounded-4"
                              placeholder="Anotaciones opcionales del registro...">{{ old('observations', $record->observations ?? '') }}</textarea>
                    @error('observations') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    @error('values') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="admin-card bg-white p-4 h-100">
            <h6 class="fw-bold mb-3">Indicaciones</h6>
            <div class="small text-secondary d-flex flex-column gap-2">
                <div>Selecciona un colegio para habilitar las variables disponibles.</div>
                <div>La categoría es opcional y solo sirve para acotar el formulario.</div>
                <div>Debes registrar al menos una variable para guardar.</div>
                <div>Los límites mínimo y máximo se validan automáticamente.</div>
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
            <div class="text-secondary small">Selecciona un colegio para cargar variables.</div>
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
    const selectedGradeId = @json((string) old('grade_id', $record->grade_id ?? ''));
    const selectedCourseId = @json((string) old('course_id', $record->course_id ?? ''));

    function optionHTML(items, placeholder, selectedValue = '') {
        let html = `<option value="">${placeholder}</option>`;
        items.forEach(item => {
            const selected = String(item.id) === String(selectedValue) ? 'selected' : '';
            html += `<option value="${item.id}" ${selected}>${item.label}</option>`;
        });
        return html;
    }

    async function loadGrades(selected = '') {
        if (!schoolSelect.value) {
            gradeSelect.innerHTML = '<option value="">Todos / Sin asignar</option>';
            courseSelect.innerHTML = '<option value="">Todos / Sin asignar</option>';
            return;
        }

        const res = await fetch(`${gradesUrl}?school_id=${schoolSelect.value}`, { headers: { 'Accept': 'application/json' }});
        const data = await res.json();
        gradeSelect.innerHTML = optionHTML(data, 'Todos / Sin asignar', selected);
    }

    async function loadCourses(selected = '') {
        if (!schoolSelect.value || !gradeSelect.value) {
            courseSelect.innerHTML = '<option value="">Todos / Sin asignar</option>';
            return;
        }

        const params = new URLSearchParams({ school_id: schoolSelect.value, grade_id: gradeSelect.value });
        const res = await fetch(`${coursesUrl}?${params.toString()}`, { headers: { 'Accept': 'application/json' }});
        const data = await res.json();
        courseSelect.innerHTML = optionHTML(data, 'Todos / Sin asignar', selected);
    }

    function renderVariableField(variable) {
        const oldValue = oldValues[variable.id] ?? '';

        let input = '';

        if (variable.data_type === 'integer' || variable.data_type === 'decimal') {
            input = `<input type="number" step="any" name="values[${variable.id}]" value="${oldValue ?? ''}" class="form-control rounded-4">`;
        } else if (variable.data_type === 'text') {
            input = `<textarea name="values[${variable.id}]" rows="3" class="form-control rounded-4">${oldValue ?? ''}</textarea>`;
        } else if (variable.data_type === 'boolean') {
            input = `
                <select name="values[${variable.id}]" class="form-select rounded-4">
                    <option value="">Selecciona</option>
                    <option value="1" ${String(oldValue) === '1' || oldValue === true ? 'selected' : ''}>Sí</option>
                    <option value="0" ${String(oldValue) === '0' || oldValue === false ? 'selected' : ''}>No</option>
                </select>
            `;
        } else if (variable.data_type === 'date') {
            input = `<input type="date" name="values[${variable.id}]" value="${oldValue ?? ''}" class="form-control rounded-4">`;
        }

        return `
            <div class="col-12 col-md-6">
                <div class="border rounded-4 p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="fw-semibold">${variable.name}</div>
                            <div class="small text-secondary">${variable.category ?? 'Sin categoría'}</div>
                        </div>
                        <span class="badge text-bg-light rounded-pill">${variable.data_type}</span>
                    </div>

                    <div class="small text-secondary mb-2">
                        Unidad: ${variable.unit ?? '—'}
                    </div>

                    ${input}

                    <div class="small text-secondary mt-2">
                        Mín: ${variable.min_value ?? '—'} | Máx: ${variable.max_value ?? '—'}
                    </div>
                </div>
            </div>
        `;
    }

    async function loadVariables() {
        if (!schoolSelect.value) {
            container.innerHTML = `
                <div class="col-12">
                    <div class="text-secondary small">Selecciona un colegio para cargar variables.</div>
                </div>
            `;
            return;
        }

        const params = new URLSearchParams({ school_id: schoolSelect.value });
        if (categorySelect.value) {
            params.append('category_id', categorySelect.value);
        }

        const res = await fetch(`${variablesUrl}?${params.toString()}`, { headers: { 'Accept': 'application/json' }});
        const data = await res.json();

        if (!data.length) {
            container.innerHTML = `
                <div class="col-12">
                    <div class="text-secondary small">No hay variables activas para esta combinación.</div>
                </div>
            `;
            return;
        }

        container.innerHTML = data.map(renderVariableField).join('');
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

    if (schoolSelect.value) {
        loadGrades(selectedGradeId).then(() => loadCourses(selectedCourseId)).then(() => loadVariables());
    }
});
</script>