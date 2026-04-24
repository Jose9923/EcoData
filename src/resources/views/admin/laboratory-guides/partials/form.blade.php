@php
    $guide = $guide ?? null;

    $selectedSchoolId = old('school_id', $selectedSchoolId ?? $guide->school_id ?? auth()->user()?->school_id);
    $selectedGradeId = old('grade_id', $selectedGradeId ?? $guide->grade_id ?? '');
    $selectedCourseId = old('course_id', $selectedCourseId ?? $guide->course_id ?? '');

    $selectedSchool = $schools->firstWhere('id', (int) $selectedSchoolId);
    $selectedGrade = $grades->firstWhere('id', (int) $selectedGradeId);
    $selectedCourse = $courses->firstWhere('id', (int) $selectedCourseId);
@endphp

<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="admin-card bg-white p-4 h-100">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Colegio</label>

                    @if(auth()->user()?->hasRole('super_admin'))
                        <select name="school_id" id="school_id" class="form-select rounded-4">
                            <option value="">Selecciona</option>
                            @foreach($schools as $school)
                                <option value="{{ $school->id }}" @selected((string) $selectedSchoolId === (string) $school->id)>
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
                            Tu rol solo permite gestionar guías del colegio asignado.
                        </div>
                    @endif

                    @error('school_id')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Grado</label>
                    <select name="grade_id" id="grade_id" class="form-select rounded-4">
                        <option value="">Todos los grados</option>
                        @foreach($grades as $grade)
                            <option value="{{ $grade->id }}" @selected((string) $selectedGradeId === (string) $grade->id)>
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
                        <option value="">Todos los cursos</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" @selected((string) $selectedCourseId === (string) $course->id)>
                                {{ $course->label ?: $course->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('course_id')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Fecha de publicación</label>
                    <input type="datetime-local" name="published_at" class="form-control rounded-4"
                           value="{{ old('published_at', isset($guide) && $guide->published_at ? $guide->published_at->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}">
                    @error('published_at')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Título</label>
                    <input type="text" name="title" class="form-control rounded-4"
                           value="{{ old('title', $guide->title ?? '') }}">
                    @error('title')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Descripción</label>
                    <textarea name="description" rows="4" class="form-control rounded-4">{{ old('description', $guide->description ?? '') }}</textarea>
                    @error('description')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Archivo PDF</label>
                    <input type="file" name="pdf" accept="application/pdf" class="form-control rounded-4">

                    @if(!empty($guide?->pdf_path))
                        <small class="text-secondary d-block mt-2">
                            Archivo actual:
                            <a href="{{ route('admin.laboratory-guides.download', $guide) }}" target="_blank">
                                Ver PDF
                            </a>
                        </small>
                    @endif

                    @error('pdf')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <input type="hidden" name="is_active" value="0">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1"
                               @checked(old('is_active', $guide->is_active ?? true))>
                        <label class="form-check-label">Guía activa</label>
                    </div>
                    @error('is_active')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="admin-card bg-white p-4 h-100">
            <h6 class="fw-bold mb-3">Resumen</h6>

            <div class="small text-secondary d-flex flex-column gap-2">
                <div>
                    <strong>Colegio:</strong>
                    {{ $selectedSchool?->name ?? auth()->user()?->school?->name ?? 'Sin definir' }}
                </div>
                <div>
                    <strong>Grado:</strong>
                    {{ $selectedGrade?->label ?: $selectedGrade?->name ?: 'Todos los grados' }}
                </div>
                <div>
                    <strong>Curso:</strong>
                    {{ $selectedCourse?->label ?: $selectedCourse?->name ?: 'Todos los cursos' }}
                </div>
                <div>
                    <strong>Título:</strong>
                    {{ old('title', $guide->title ?? 'Sin definir') }}
                </div>
                <div>
                    <strong>Estado:</strong>
                    {{ old('is_active', $guide->is_active ?? true) ? 'Activa' : 'Inactiva' }}
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a href="{{ route('admin.laboratory-guides.index') }}" class="btn btn-outline-secondary rounded-4 px-4">Cancelar</a>
    <button type="submit" class="btn text-white rounded-4 px-4" style="background-color: var(--school-primary);">
        {{ $buttonText ?? 'Guardar' }}
    </button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const schoolSelect = document.getElementById('school_id');
    const gradeSelect = document.getElementById('grade_id');
    const courseSelect = document.getElementById('course_id');

    const gradesUrl = @json(route('admin.laboratory-guides.ajax.grades'));
    const coursesUrl = @json(route('admin.laboratory-guides.ajax.courses'));

    const selectedGradeId = @json((string) $selectedGradeId);
    const selectedCourseId = @json((string) $selectedCourseId);

    function setOptions(select, items, placeholder, selectedValue = '') {
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

    async function fetchJson(url) {
        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error('No fue posible cargar los datos.');
        }

        return await response.json();
    }

    async function loadGrades(schoolId, selected = '') {
        if (!schoolId) {
            setOptions(gradeSelect, [], 'Todos los grados');
            setOptions(courseSelect, [], 'Todos los cursos');
            return;
        }

        gradeSelect.innerHTML = '<option value="">Cargando grados...</option>';
        courseSelect.innerHTML = '<option value="">Todos los cursos</option>';

        try {
            const data = await fetchJson(`${gradesUrl}?school_id=${encodeURIComponent(schoolId)}`);
            setOptions(gradeSelect, data, 'Todos los grados', selected);
        } catch (error) {
            console.error(error);
            setOptions(gradeSelect, [], 'Error cargando grados');
        }
    }

    async function loadCourses(schoolId, gradeId = '', selected = '') {
        if (!schoolId) {
            setOptions(courseSelect, [], 'Todos los cursos');
            return;
        }

        courseSelect.innerHTML = '<option value="">Cargando cursos...</option>';

        try {
            const url = `${coursesUrl}?school_id=${encodeURIComponent(schoolId)}&grade_id=${encodeURIComponent(gradeId || '')}`;
            const data = await fetchJson(url);
            setOptions(courseSelect, data, 'Todos los cursos', selected);
        } catch (error) {
            console.error(error);
            setOptions(courseSelect, [], 'Error cargando cursos');
        }
    }

    schoolSelect?.addEventListener('change', async function () {
        await loadGrades(this.value);
        await loadCourses(this.value);
    });

    gradeSelect?.addEventListener('change', async function () {
        await loadCourses(schoolSelect?.value || @json((string) $selectedSchoolId), this.value);
    });

    const initialSchoolId = schoolSelect?.value || @json((string) $selectedSchoolId);

    if (initialSchoolId && gradeSelect.options.length <= 1) {
        loadGrades(initialSchoolId, selectedGradeId);
    }

    if (initialSchoolId && courseSelect.options.length <= 1) {
        loadCourses(initialSchoolId, selectedGradeId, selectedCourseId);
    }
});
</script>