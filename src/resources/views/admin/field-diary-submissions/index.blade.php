@extends('layouts.app')

@section('title', 'Revisión de Diario de Campo')

@section('content')
    <section class="admin-hero p-4 p-md-5">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-lg-8">
                <div class="text-uppercase small fw-semibold admin-hero-subtitle mb-2">
                    Administración
                </div>
                <h1 class="display-6 fw-bold mb-2">Revisión de Diario de Campo</h1>
                <p class="mb-0 admin-hero-subtitle">
                    Revisa, califica y retroalimenta las entregas realizadas por los estudiantes.
                </p>
            </div>
        </div>
    </section>

    <section class="admin-card bg-white p-4">
        <form method="GET" action="{{ route('admin.field-diary-submissions.index') }}">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-4">
                    <label for="search" class="form-label fw-semibold">Filtrar</label>
                    <input type="text"
                           id="search"
                           name="search"
                           value="{{ $filters['search'] }}"
                           class="form-control form-control-lg rounded-4"
                           placeholder="Actividad, estudiante, documento, correo...">
                </div>

                @if(auth()->user()->hasRole('super_admin'))
                    <div class="col-12 col-lg-4">
                        <label for="school_id" class="form-label fw-semibold">Colegio</label>
                        <select id="school_id" name="school_id" class="form-select form-select-lg rounded-4">
                            <option value="">Todos</option>
                            @foreach($schools as $school)
                                <option value="{{ $school->id }}" @selected((string) $filters['school_id'] === (string) $school->id)>
                                    {{ $school->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-12 col-lg-4">
                    <label for="activity_id" class="form-label fw-semibold">Actividad</label>
                    <select id="activity_id" name="activity_id" class="form-select form-select-lg rounded-4">
                        <option value="">Todas</option>
                        @foreach($activities as $activity)
                            <option value="{{ $activity->id }}" @selected((string) $filters['activity_id'] === (string) $activity->id)>
                                {{ $activity->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <label for="grade_id" class="form-label fw-semibold">Grado</label>
                    <select id="grade_id" name="grade_id" class="form-select form-select-lg rounded-4">
                        <option value="">Todos</option>
                        @foreach($grades as $grade)
                            <option value="{{ $grade->id }}" @selected((string) $filters['grade_id'] === (string) $grade->id)>
                                {{ $grade->label ?: $grade->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <label for="course_id" class="form-label fw-semibold">Curso</label>
                    <select id="course_id" name="course_id" class="form-select form-select-lg rounded-4">
                        <option value="">Todos</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" @selected((string) $filters['course_id'] === (string) $course->id)>
                                {{ $course->label ?: $course->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <label for="student_id" class="form-label fw-semibold">Estudiante</label>
                    <select id="student_id" name="student_id" class="form-select form-select-lg rounded-4">
                        <option value="">Todos</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}" @selected((string) $filters['student_id'] === (string) $student->id)>
                                {{ $student->name }}
                                @if($student->document_number)
                                    - {{ $student->document_number }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <label for="status" class="form-label fw-semibold">Estado</label>
                    <select id="status" name="status" class="form-select form-select-lg rounded-4">
                        <option value="">Todos</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected((string) $filters['status'] === (string) $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-sm-6 col-lg-2">
                    <label for="per_page" class="form-label fw-semibold">Registros</label>
                    <select id="per_page" name="per_page" class="form-select form-select-lg rounded-4">
                        @foreach([10, 15, 25, 50] as $size)
                            <option value="{{ $size }}" @selected($filters['per_page'] == $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-sm-6 col-lg-2 d-grid">
                    <button class="btn btn-dark btn-lg rounded-4">Filtrar</button>
                </div>

                <div class="col-12 col-sm-6 col-lg-2 d-grid">
                    <a href="{{ route('admin.field-diary-submissions.index') }}"
                       class="btn btn-outline-secondary btn-lg rounded-4">
                        Limpiar filtros
                    </a>
                </div>

                <div class="col-12 col-sm-6 col-lg-2 d-grid">
                    <a href="{{ route('admin.field-diary-submissions.export', request()->query()) }}"
                       class="btn btn-outline-success btn-lg rounded-4">
                        Exportar Excel
                    </a>
                </div>
            </div>
        </form>
    </section>

    <section class="admin-card bg-white p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h2 class="h5 fw-bold mb-1">Entregas de estudiantes</h2>
                <p class="text-muted mb-0">
                    Selecciona una entrega para revisar sus respuestas y asignar retroalimentación.
                </p>
            </div>
        </div>

        <div>
            <table id="fieldDiarySubmissionsTable"
                class="table table-striped table-hover align-middle nowrap w-100 mb-0 js-ecodata-datatable"
                data-paging="false"
                data-searching="false"
                data-info="false"
                data-empty="No hay entregas registradas con los filtros seleccionados.">
                <thead>
                    <tr>
                        <th>Actividad</th>
                        <th>Estudiante</th>
                        <th>Asignación</th>
                        <th>Estado</th>
                        <th>Enviado</th>
                        <th>Nota</th>
                        <th class="text-end">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($submissions as $submission)
                        @php
                            $statusClass = match($submission->status) {
                                'borrador' => 'text-bg-warning',
                                'enviado' => 'text-bg-primary',
                                'revisado' => 'text-bg-success',
                                'devuelto' => 'text-bg-danger',
                                default => 'text-bg-secondary',
                            };
                        @endphp

                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $submission->activity?->title ?? 'Sin actividad' }}</div>
                                <small class="text-muted">
                                    {{ $submission->activity?->entry_type_label }}
                                    @if($submission->activity?->weatherStation)
                                        · {{ $submission->activity->weatherStation->name }}
                                    @endif
                                </small>
                            </td>

                            <td>
                                <div class="fw-semibold">{{ $submission->student?->name ?? 'Sin estudiante' }}</div>
                                <small class="text-muted">
                                    {{ $submission->student?->document_type }}
                                    {{ $submission->student?->document_number }}
                                </small>
                            </td>

                            <td>
                                <div>{{ $submission->school?->name ?? 'Sin colegio' }}</div>
                                <small class="text-muted">
                                    {{ $submission->grade?->label ?? $submission->grade?->name ?? 'Sin grado' }}
                                    /
                                    {{ $submission->course?->label ?? $submission->course?->name ?? 'Sin curso' }}
                                </small>
                            </td>

                            <td>
                                <span class="badge rounded-pill {{ $statusClass }}">
                                    {{ $submission->status_label }}
                                </span>
                            </td>

                            <td>
                                {{ $submission->submitted_at?->format('d/m/Y H:i') ?? 'Sin enviar' }}
                            </td>

                            <td>
                                {{ $submission->score ?? '—' }}
                            </td>

                            <td class="text-end">
                                <div class="d-inline-flex justify-content-end gap-2 flex-nowrap">
                                    <a href="{{ route('admin.field-diary-submissions.show', $submission) }}"
                                    class="btn btn-outline-secondary rounded-4 text-nowrap">
                                        Revisar
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                No hay entregas registradas con los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($submissions->hasPages())
            <div class="p-4 border-top d-flex justify-content-center overflow-auto">
                {{ $submissions->onEachSide(1)->links() }}
            </div>
        @endif
    </section>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const schoolSelect = document.getElementById('school_id');
                const gradeSelect = document.getElementById('grade_id');
                const courseSelect = document.getElementById('course_id');
                const activitySelect = document.getElementById('activity_id');
                const studentSelect = document.getElementById('student_id');

                const gradesUrl = @json(route('admin.field-diary-submissions.ajax.grades'));
                const coursesUrl = @json(route('admin.field-diary-submissions.ajax.courses'));
                const activitiesUrl = @json(route('admin.field-diary-submissions.ajax.activities'));
                const studentsUrl = @json(route('admin.field-diary-submissions.ajax.students'));

                const initialSchoolId = schoolSelect?.value || @json((string) ($filters['school_id'] ?? ''));
                const selectedGradeId = @json((string) ($filters['grade_id'] ?? ''));
                const selectedCourseId = @json((string) ($filters['course_id'] ?? ''));
                const selectedActivityId = @json((string) ($filters['activity_id'] ?? ''));
                const selectedStudentId = @json((string) ($filters['student_id'] ?? ''));

                function setOptions(select, items, placeholder, selectedValue = '') {
                    if (!select) return;

                    select.innerHTML = `<option value="">${placeholder}</option>`;

                    items.forEach((item) => {
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
                            'Accept': 'application/json',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('No fue posible cargar los filtros.');
                    }

                    return await response.json();
                }

                function buildUrl(baseUrl, params) {
                    const url = new URL(baseUrl, window.location.origin);

                    Object.entries(params).forEach(([key, value]) => {
                        if (value !== null && value !== undefined && value !== '') {
                            url.searchParams.set(key, value);
                        }
                    });

                    return url.toString();
                }

                async function loadGrades(schoolId, selected = '') {
                    if (!schoolId) {
                        setOptions(gradeSelect, [], 'Todos');
                        setOptions(courseSelect, [], 'Todos');
                        setOptions(activitySelect, [], 'Todas');
                        setOptions(studentSelect, [], 'Todos');
                        return;
                    }

                    try {
                        const data = await fetchJson(buildUrl(gradesUrl, { school_id: schoolId }));
                        setOptions(gradeSelect, data, 'Todos', selected);
                    } catch (error) {
                        console.error(error);
                        setOptions(gradeSelect, [], 'Error cargando grados');
                    }
                }

                async function loadCourses(schoolId, gradeId = '', selected = '') {
                    if (!schoolId) {
                        setOptions(courseSelect, [], 'Todos');
                        return;
                    }

                    try {
                        const data = await fetchJson(buildUrl(coursesUrl, {
                            school_id: schoolId,
                            grade_id: gradeId,
                        }));
                        setOptions(courseSelect, data, 'Todos', selected);
                    } catch (error) {
                        console.error(error);
                        setOptions(courseSelect, [], 'Error cargando cursos');
                    }
                }

                async function loadActivities(schoolId, gradeId = '', courseId = '', selected = '') {
                    if (!schoolId) {
                        setOptions(activitySelect, [], 'Todas');
                        return;
                    }

                    try {
                        const data = await fetchJson(buildUrl(activitiesUrl, {
                            school_id: schoolId,
                            grade_id: gradeId,
                            course_id: courseId,
                        }));
                        setOptions(activitySelect, data, 'Todas', selected);
                    } catch (error) {
                        console.error(error);
                        setOptions(activitySelect, [], 'Error cargando actividades');
                    }
                }

                async function loadStudents(schoolId, gradeId = '', courseId = '', selected = '') {
                    if (!schoolId) {
                        setOptions(studentSelect, [], 'Todos');
                        return;
                    }

                    try {
                        const data = await fetchJson(buildUrl(studentsUrl, {
                            school_id: schoolId,
                            grade_id: gradeId,
                            course_id: courseId,
                        }));
                        setOptions(studentSelect, data, 'Todos', selected);
                    } catch (error) {
                        console.error(error);
                        setOptions(studentSelect, [], 'Error cargando estudiantes');
                    }
                }

                schoolSelect?.addEventListener('change', async function () {
                    await loadGrades(this.value);
                    await loadCourses(this.value);
                    await loadActivities(this.value);
                    await loadStudents(this.value);
                });

                gradeSelect?.addEventListener('change', async function () {
                    const schoolId = schoolSelect?.value || initialSchoolId;

                    await loadCourses(schoolId, this.value);
                    await loadActivities(schoolId, this.value);
                    await loadStudents(schoolId, this.value);
                });

                courseSelect?.addEventListener('change', async function () {
                    const schoolId = schoolSelect?.value || initialSchoolId;
                    const gradeId = gradeSelect?.value || '';

                    await loadActivities(schoolId, gradeId, this.value);
                    await loadStudents(schoolId, gradeId, this.value);
                });

                if (initialSchoolId && gradeSelect && gradeSelect.options.length <= 1) {
                    loadGrades(initialSchoolId, selectedGradeId);
                }

                if (initialSchoolId && courseSelect && courseSelect.options.length <= 1) {
                    loadCourses(initialSchoolId, selectedGradeId, selectedCourseId);
                }

                if (initialSchoolId && activitySelect && activitySelect.options.length <= 1) {
                    loadActivities(initialSchoolId, selectedGradeId, selectedCourseId, selectedActivityId);
                }

                if (initialSchoolId && studentSelect && studentSelect.options.length <= 1) {
                    loadStudents(initialSchoolId, selectedGradeId, selectedCourseId, selectedStudentId);
                }
            });
        </script>
    @endpush
@endsection
