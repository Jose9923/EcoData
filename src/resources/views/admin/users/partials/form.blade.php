@php
    $user = $user ?? null;
    $selectedRole = old('role', $user?->roles->first()?->name ?? '');
@endphp

<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="admin-card bg-white p-4 h-100">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Nombre completo</label>
                    <input type="text" name="name" class="form-control rounded-4"
                           value="{{ old('name', $user->name ?? '') }}">
                    @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Tipo de identificación</label>
                    <select name="document_type" class="form-select rounded-4">
                        <option value="">Selecciona</option>
                        <option value="CC" @selected(old('document_type', $user->document_type ?? '') === 'CC')>CC</option>
                        <option value="TI" @selected(old('document_type', $user->document_type ?? '') === 'TI')>TI</option>
                        <option value="CE" @selected(old('document_type', $user->document_type ?? '') === 'CE')>CE</option>
                        <option value="PPT" @selected(old('document_type', $user->document_type ?? '') === 'PPT')>PPT</option>
                        <option value="NIT" @selected(old('document_type', $user->document_type ?? '') === 'NIT')>NIT</option>
                        <option value="PAS" @selected(old('document_type', $user->document_type ?? '') === 'PAS')>PAS</option>
                    </select>
                    @error('document_type') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Número de identificación</label>
                    <input type="text" name="document_number" class="form-control rounded-4"
                        value="{{ old('document_number', $user->document_number ?? '') }}">
                    @error('document_number') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Correo electrónico</label>
                    <input type="email" name="email" class="form-control rounded-4"
                           value="{{ old('email', $user->email ?? '') }}">
                    @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">
                        {{ isset($user) ? 'Nueva contraseña' : 'Contraseña' }}
                    </label>
                    <input type="password" name="password" class="form-control rounded-4">
                    @error('password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Confirmar contraseña</label>
                    <input type="password" name="password_confirmation" class="form-control rounded-4">
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Colegio</label>
                    <select name="school_id" id="school_id" class="form-select rounded-4">
                        <option value="">Sin asignar</option>
                        @foreach($schools as $schoolOption)
                            <option value="{{ $schoolOption->id }}"
                                @selected((string) old('school_id', $selectedSchoolId ?? $user->school_id ?? '') === (string) $schoolOption->id)>
                                {{ $schoolOption->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('school_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Rol</label>
                    <select name="role" class="form-select rounded-4">
                        <option value="">Selecciona un rol</option>
                        @foreach($roles as $roleOption)
                            <option value="{{ $roleOption->name }}" @selected($selectedRole === $roleOption->name)>
                                {{ \Illuminate\Support\Str::title($roleOption->name) }}
                            </option>
                        @endforeach
                    </select>
                    @error('role') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Grado</label>
                    <select name="grade_id" id="grade_id" class="form-select rounded-4">
                        <option value="">Sin asignar</option>
                        @foreach($grades as $grade)
                            <option value="{{ $grade->id }}"
                                @selected((string) old('grade_id', $selectedGradeId ?? $user->grade_id ?? '') === (string) $grade->id)>
                                {{ $grade->label ?: $grade->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('grade_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Curso</label>
                    <select name="course_id" id="course_id" class="form-select rounded-4">
                        <option value="">Sin asignar</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}"
                                @selected((string) old('course_id', $user->course_id ?? '') === (string) $course->id)>
                                {{ $course->label ?: $course->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('course_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <input type="hidden" name="is_active" value="0">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                               @checked(old('is_active', $user->is_active ?? true))>
                        <label class="form-check-label" for="is_active">Usuario activo</label>
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
                <div><strong>Nombre:</strong> {{ old('name', $user->name ?? 'Sin definir') }}</div>
                <div><strong>Correo:</strong> {{ old('email', $user->email ?? 'Sin definir') }}</div>
                <div><strong>Rol:</strong> {{ $selectedRole ?: 'Sin definir' }}</div>
                <div><strong>Estado:</strong> {{ old('is_active', $user->is_active ?? true) ? 'Activo' : 'Inactivo' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary rounded-4 px-4">Cancelar</a>
    <button type="submit" class="btn text-white rounded-4 px-4" style="background-color: var(--school-primary);">
        {{ $buttonText ?? 'Guardar' }}
    </button>
</div>
@php
    $selectedCourseId = old('course_id', $user->course_id ?? '');
@endphp

<script>
document.addEventListener('DOMContentLoaded', function () {
    const schoolSelect = document.getElementById('school_id');
    const gradeSelect = document.getElementById('grade_id');
    const courseSelect = document.getElementById('course_id');

    const gradesUrl = @json(route('admin.users.ajax.grades'));
    const coursesUrl = @json(route('admin.users.ajax.courses'));

    const selectedGradeId = @json((string) old('grade_id', $selectedGradeId ?? $user->grade_id ?? ''));
    const selectedCourseId = @json((string) $selectedCourseId);

    function setLoading(select, placeholder = 'Cargando...') {
        select.innerHTML = `<option value="">${placeholder}</option>`;
    }

    function setOptions(select, items, placeholder = 'Sin asignar', selectedValue = '') {
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
            setOptions(gradeSelect, [], 'Sin asignar');
            setOptions(courseSelect, [], 'Sin asignar');
            return;
        }

        setLoading(gradeSelect);
        setOptions(courseSelect, [], 'Sin asignar');

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
            setOptions(gradeSelect, data, 'Sin asignar', selected);
        } catch (error) {
            console.error(error);
            setOptions(gradeSelect, [], 'Error cargando grados');
        }
    }

    async function fetchCourses(schoolId, gradeId, selected = '') {
        if (!schoolId || !gradeId) {
            setOptions(courseSelect, [], 'Sin asignar');
            return;
        }

        setLoading(courseSelect);

        try {
            const params = new URLSearchParams({
                school_id: schoolId,
                grade_id: gradeId
            });

            const response = await fetch(`${coursesUrl}?${params.toString()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Error cargando cursos');
            }

            const data = await response.json();
            setOptions(courseSelect, data, 'Sin asignar', selected);
        } catch (error) {
            console.error(error);
            setOptions(courseSelect, [], 'Error cargando cursos');
        }
    }

    schoolSelect?.addEventListener('change', async function () {
        const schoolId = this.value;

        setOptions(gradeSelect, [], 'Sin asignar');
        setOptions(courseSelect, [], 'Sin asignar');

        if (schoolId) {
            await fetchGrades(schoolId);
        }
    });

    gradeSelect?.addEventListener('change', async function () {
        const schoolId = schoolSelect.value;
        const gradeId = this.value;

        setOptions(courseSelect, [], 'Sin asignar');

        if (schoolId && gradeId) {
            await fetchCourses(schoolId, gradeId);
        }
    });

    // Solo útil si quieres rehidratar en edición con datos cargados por backend o errores de validación.
    // Si los arrays ya llegan llenos desde el controller, esto no estorba.
    if (schoolSelect?.value && selectedGradeId && gradeSelect.options.length <= 1) {
        fetchGrades(schoolSelect.value, selectedGradeId).then(() => {
            if (gradeSelect.value && selectedCourseId && courseSelect.options.length <= 1) {
                fetchCourses(schoolSelect.value, gradeSelect.value, selectedCourseId);
            }
        });
    } else if (schoolSelect?.value && gradeSelect?.value && selectedCourseId && courseSelect.options.length <= 1) {
        fetchCourses(schoolSelect.value, gradeSelect.value, selectedCourseId);
    }
});
</script>