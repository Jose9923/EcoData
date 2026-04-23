@php
    $course = $course ?? null;
    $selectedSchoolId = old('school_id', $selectedSchoolId ?? $course->school_id ?? '');
    $selectedGradeId = old('grade_id', $course->grade_id ?? '');
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
                            <option value="{{ $school->id }}"
                                @selected((string) $selectedSchoolId === (string) $school->id)>
                                {{ $school->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('school_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Grado</label>
                    <select name="grade_id" id="grade_id" class="form-select rounded-4">
                        <option value="">Selecciona un grado</option>
                        @foreach($grades as $grade)
                            <option value="{{ $grade->id }}"
                                @selected((string) $selectedGradeId === (string) $grade->id)>
                                {{ $grade->label ?: $grade->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('grade_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Nombre del curso</label>
                    <input type="text" name="name" class="form-control rounded-4"
                           value="{{ old('name', $course->name ?? '') }}"
                           placeholder="Ej: A">
                    @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Etiqueta visible</label>
                    <input type="text" name="label" class="form-control rounded-4"
                           value="{{ old('label', $course->label ?? '') }}"
                           placeholder="Ej: 6A">
                    @error('label') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <input type="hidden" name="is_active" value="0">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                               @checked(old('is_active', $course->is_active ?? true))>
                        <label class="form-check-label" for="is_active">Curso activo</label>
                    </div>
                    @error('is_active') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="admin-card bg-white p-4 h-100">
            <h6 class="fw-bold mb-3">Resumen</h6>
            <div class="small text-secondary d-flex flex-column gap-2">
                <div><strong>Colegio:</strong> {{ $schools->firstWhere('id', $selectedSchoolId)?->name ?? 'Sin definir' }}</div>
                <div><strong>Curso:</strong> {{ old('name', $course->name ?? 'Sin definir') }}</div>
                <div><strong>Etiqueta:</strong> {{ old('label', $course->label ?? 'Sin definir') }}</div>
                <div><strong>Estado:</strong> {{ old('is_active', $course->is_active ?? true) ? 'Activo' : 'Inactivo' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a href="{{ route('admin.courses.index') }}" class="btn btn-outline-secondary rounded-4 px-4">Cancelar</a>
    <button type="submit" class="btn text-white rounded-4 px-4" style="background-color: var(--school-primary);">
        {{ $buttonText ?? 'Guardar' }}
    </button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const schoolSelect = document.getElementById('school_id');
    const gradeSelect = document.getElementById('grade_id');
    const gradesUrl = @json(route('admin.courses.ajax.grades'));
    const selectedGradeId = @json((string) $selectedGradeId);

    function setLoading(select, placeholder = 'Cargando...') {
        select.innerHTML = `<option value="">${placeholder}</option>`;
    }

    function setOptions(select, items, placeholder = 'Selecciona un grado', selectedValue = '') {
        select.innerHTML = `<option value="">${placeholder}</option>`;

        items.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.label;
            if (String(item.id) === String(selectedValue)) {
                option.selected = true;
            }
            select.appendChild(option);
        });
    }

    async function fetchGrades(schoolId, selected = '') {
        if (!schoolId) {
            setOptions(gradeSelect, [], 'Selecciona un grado');
            return;
        }

        setLoading(gradeSelect);

        try {
            const response = await fetch(`${gradesUrl}?school_id=${encodeURIComponent(schoolId)}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Error cargando grados');
            }

            const data = await response.json();
            setOptions(gradeSelect, data, 'Selecciona un grado', selected);
        } catch (error) {
            console.error(error);
            setOptions(gradeSelect, [], 'Error cargando grados');
        }
    }

    schoolSelect?.addEventListener('change', async function () {
        const schoolId = this.value;
        await fetchGrades(schoolId);
    });

    if (schoolSelect?.value && selectedGradeId && gradeSelect.options.length <= 1) {
        fetchGrades(schoolSelect.value, selectedGradeId);
    }
});
</script>