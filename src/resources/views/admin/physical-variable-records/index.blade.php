@extends('layouts.app')

@section('content')
<div class="d-flex flex-column gap-4">
    <section class="admin-hero p-4 p-md-5">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-lg-8">
                <div class="text-uppercase small fw-semibold text-light-emphasis mb-2">Monitoreo</div>
                <h1 class="display-6 fw-bold mb-2">Registros de Variables Físicas</h1>
                <p class="mb-0 text-light-emphasis">
                    Consulta, filtra y exporta los registros físicos capturados por colegio, grupo y categoría.
                </p>
            </div>
            <div class="col-12 col-lg-4 text-lg-end">
                <a href="{{ route('admin.physical-variable-records.create') }}"
                   class="btn text-white rounded-4 px-4 py-3 fw-semibold"
                   style="background-color: var(--school-primary);">
                    + Nuevo registro
                </a>
            </div>
        </div>
    </section>

    @if(session('success'))
        <div class="alert alert-success rounded-4 border-0 shadow-sm mb-0">{{ session('success') }}</div>
    @endif

    <section class="admin-card bg-white p-4">
        <form method="GET" action="{{ route('admin.physical-variable-records.index') }}">
            <div class="row g-3">
                <div class="col-12 col-xl-6">
                    <label class="form-label fw-semibold">Buscar</label>
                    <input type="text" name="search" value="{{ $filters['search'] }}"
                           class="form-control form-control-lg rounded-4"
                           placeholder="Buscar por observaciones, usuario, colegio o variable...">
                </div>

                <div class="col-6 col-md-4 col-xl-2">
                    <label class="form-label fw-semibold">Fecha desde</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] }}"
                           class="form-control form-control-lg rounded-4">
                </div>

                <div class="col-6 col-md-4 col-xl-2">
                    <label class="form-label fw-semibold">Fecha hasta</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] }}"
                           class="form-control form-control-lg rounded-4">
                </div>

                <div class="col-12 col-md-4 col-xl-2">
                    <label class="form-label fw-semibold">Registros</label>
                    <select name="per_page" class="form-select form-select-lg rounded-4">
                        @foreach([10,15,25,50] as $size)
                            <option value="{{ $size }}" @selected($filters['per_page'] == $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Colegio</label>
                    <select name="school_id" id="filter_school_id" class="form-select rounded-4">
                        <option value="">Todos</option>
                        @foreach($schools as $school)
                            <option value="{{ $school->id }}" @selected((string) $filters['school_id'] === (string) $school->id)>
                                {{ $school->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Grado</label>
                    <select name="grade_id" id="filter_grade_id" class="form-select rounded-4">
                        <option value="">Todos</option>
                        @foreach($grades as $grade)
                            <option value="{{ $grade->id }}" @selected((string) $filters['grade_id'] === (string) $grade->id)>
                                {{ $grade->label ?: $grade->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Curso</label>
                    <select name="course_id" id="filter_course_id" class="form-select rounded-4">
                        <option value="">Todos</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" @selected((string) $filters['course_id'] === (string) $course->id)>
                                {{ $course->label ?: $course->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Categoría</label>
                    <select name="category_id" class="form-select rounded-4">
                        <option value="">Todas</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) $filters['category_id'] === (string) $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Variable</label>
                    <select name="variable_id" class="form-select rounded-4">
                        <option value="">Todas</option>
                        @foreach($variables as $variable)
                            <option value="{{ $variable->id }}" @selected((string) $filters['variable_id'] === (string) $variable->id)>
                                {{ $variable->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 d-flex flex-column flex-md-row gap-2 justify-content-md-end">
                    <a href="{{ route('admin.physical-variable-records.index') }}" class="btn btn-outline-secondary rounded-4 px-4">
                        Limpiar filtros
                    </a>
                    <a href="{{ route('admin.physical-variable-records.export', request()->query()) }}"
                       class="btn btn-outline-success rounded-4 px-4">
                        Exportar Excel
                    </a>
                    <button class="btn btn-dark rounded-4 px-4">Filtrar</button>
                </div>
            </div>
        </form>
    </section>

    <section class="admin-card bg-white overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Contexto</th>
                        <th>Registrado por</th>
                        <th>Variables capturadas</th>
                        <th>Observaciones</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                        @php
                            $gradeLabel = $record->grade?->label ?: $record->grade?->name;
                            $courseLabel = $record->course?->label ?: $record->course?->name;
                            $previewValues = $record->values->take(3);
                            $remainingValues = max($record->values_count - $previewValues->count(), 0);
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ optional($record->recorded_at)->format('d/m/Y') }}</div>
                                <small class="text-secondary">{{ optional($record->recorded_at)->format('H:i') }}</small>
                            </td>
                            <td class="small">
                                <div><strong>Colegio:</strong> {{ $record->school?->name ?? '—' }}</div>
                                <div><strong>Grado:</strong> {{ $gradeLabel ?: '—' }}</div>
                                <div><strong>Curso:</strong> {{ $courseLabel ?: '—' }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $record->user?->name ?? '—' }}</div>
                                <small class="text-secondary">{{ $record->user?->email }}</small>
                            </td>
                            <td class="small text-secondary">
                                @foreach($previewValues as $value)
                                @php
                                    $variable = $value->variable;
                                    $resolved = $value->resolved_value;

                                    if ($variable?->data_type === 'boolean') {
                                        $resolved = $resolved === true ? 'Sí' : ($resolved === false ? 'No' : '—');
                                    } elseif ($variable?->data_type === 'date' && $resolved) {
                                        $resolved = \Illuminate\Support\Carbon::parse($resolved)->format('Y-m-d');
                                    } elseif (in_array($variable?->data_type, ['integer', 'decimal'], true) && $resolved !== null) {
                                        $resolved = number_format((float) $resolved, $variable->decimals ?? 0, '.', '');
                                    }

                                    if ($resolved !== null && $resolved !== '—' && $variable?->unit) {
                                        $resolved .= ' ' . $variable->unit;
                                    }
                                @endphp
                                    <div>
                                        <strong>{{ $value->variable?->name }}:</strong> {{ $resolved ?? '—' }}
                                    </div>
                                @endforeach
                                @if($remainingValues > 0)
                                    <div class="mt-1 text-muted">+ {{ $remainingValues }} más</div>
                                @endif
                            </td>
                            <td>
                                <small class="text-secondary">
                                    {{ $record->observations ?: 'Sin observaciones' }}
                                </small>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('admin.physical-variable-records.show', $record->id) }}"
                                    class="btn btn-outline-secondary rounded-4">
                                        Ver detalle
                                    </a>
                                    <a href="{{ route('admin.physical-variable-records.edit', $record->id) }}"
                                    class="btn btn-outline-primary rounded-4">
                                        Editar
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <h5 class="fw-semibold mb-2">No hay registros físicos</h5>
                                <p class="text-secondary mb-0">Ajusta los filtros o crea un nuevo registro.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($records->hasPages())
            <div class="p-4 border-top">
                {{ $records->links() }}
            </div>
        @endif
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const schoolSelect = document.getElementById('filter_school_id');
    const gradeSelect = document.getElementById('filter_grade_id');
    const courseSelect = document.getElementById('filter_course_id');

    const gradesUrl = @json(route('admin.physical-variable-records.ajax.grades'));
    const coursesUrl = @json(route('admin.physical-variable-records.ajax.courses'));

    async function updateGrades() {
        if (!schoolSelect.value) {
            gradeSelect.innerHTML = '<option value="">Todos</option>';
            courseSelect.innerHTML = '<option value="">Todos</option>';
            return;
        }

        const res = await fetch(`${gradesUrl}?school_id=${schoolSelect.value}`, { headers: { 'Accept': 'application/json' }});
        const data = await res.json();

        const selected = @json((string) $filters['grade_id']);
        gradeSelect.innerHTML = '<option value="">Todos</option>';
        data.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.label;
            if (String(item.id) === String(selected)) option.selected = true;
            gradeSelect.appendChild(option);
        });
    }

    async function updateCourses() {
        if (!schoolSelect.value || !gradeSelect.value) {
            courseSelect.innerHTML = '<option value="">Todos</option>';
            return;
        }

        const params = new URLSearchParams({ school_id: schoolSelect.value, grade_id: gradeSelect.value });
        const res = await fetch(`${coursesUrl}?${params.toString()}`, { headers: { 'Accept': 'application/json' }});
        const data = await res.json();

        const selected = @json((string) $filters['course_id']);
        courseSelect.innerHTML = '<option value="">Todos</option>';
        data.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.label;
            if (String(item.id) === String(selected)) option.selected = true;
            courseSelect.appendChild(option);
        });
    }

    schoolSelect?.addEventListener('change', async function () {
        await updateGrades();
        await updateCourses();
    });

    gradeSelect?.addEventListener('change', async function () {
        await updateCourses();
    });
});
</script>
@endsection