@extends('layouts.app')

@section('content')
<div class="d-flex flex-column gap-4">

    <section class="admin-hero p-4 p-md-5">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-lg-8">
                <div class="text-uppercase small fw-semibold text-light-emphasis mb-2">
                    Administración
                </div>
                <h1 class="display-6 fw-bold mb-2">Cursos</h1>
                <p class="mb-0 text-light-emphasis">
                    Gestiona los cursos por colegio y grado dentro del sistema.
                </p>
            </div>

            <div class="col-12 col-lg-4 text-lg-end">
                <a href="{{ route('admin.courses.create') }}" class="btn text-white rounded-4 px-4 py-3 fw-semibold"
                   style="background-color: var(--school-primary);">
                    + Nuevo curso
                </a>
            </div>
        </div>
    </section>

    @if(session('success'))
        <div class="alert alert-success rounded-4 border-0 shadow-sm mb-0">{{ session('success') }}</div>
    @endif

    <section class="admin-card bg-white p-4">
        <form method="GET" action="{{ route('admin.courses.index') }}">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-8">
                    <label for="search" class="form-label fw-semibold">Buscar curso</label>
                    <input type="text" id="search" name="search" value="{{ $search }}"
                           class="form-control form-control-lg rounded-4"
                           placeholder="Buscar por nombre, etiqueta, colegio o grado...">
                </div>

                <div class="col-12 col-sm-6 col-lg-2">
                    <label for="per_page" class="form-label fw-semibold">Registros</label>
                    <select id="per_page" name="per_page" class="form-select form-select-lg rounded-4">
                        @foreach([10,15,25,50] as $size)
                            <option value="{{ $size }}" @selected($perPage == $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-sm-6 col-lg-2 d-grid">
                    <button class="btn btn-dark btn-lg rounded-4">Filtrar</button>
                </div>
            </div>
        </form>

        <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mt-4 pt-4 border-top">
            <div class="text-secondary small">
                Mostrando {{ $courses->firstItem() ?? 0 }} - {{ $courses->lastItem() ?? 0 }} de {{ $courses->total() }} cursos
            </div>

            @if($search !== '')
                <div class="small fw-semibold text-secondary">
                    Filtro aplicado: "{{ $search }}"
                </div>
            @endif
        </div>
    </section>

    <section class="admin-card bg-white overflow-hidden">
        <div class="p-3 p-md-4">
            <table id="coursesTable" class="table table-striped table-hover align-middle nowrap w-100 mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Curso</th>
                        <th>Etiqueta</th>
                        <th>Colegio</th>
                        <th>Grado</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($courses as $course)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $course->name }}</div>
                            </td>
                            <td>
                                @if ($course->label)
                                    <span class="badge text-bg-light rounded-pill px-3 py-2">
                                        {{ $course->label }}
                                    </span>
                                @else
                                    <span class="text-secondary small">Sin etiqueta</span>
                                @endif
                            </td>
                            <td>{{ $course->school?->name ?? 'Sin asignar' }}</td>
                            <td>{{ $course->grade?->label ?: $course->grade?->name ?: 'Sin asignar' }}</td>
                            <td>
                                @if($course->is_active)
                                    <span class="badge rounded-pill text-bg-success px-3 py-2">Activo</span>
                                @else
                                    <span class="badge rounded-pill text-bg-danger px-3 py-2">Inactivo</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('admin.courses.edit', $course->id) }}" class="btn btn-outline-secondary rounded-4">
                                        Editar
                                    </a>

                                    <form method="POST" action="{{ route('admin.courses.destroy', $course->id) }}"
                                          onsubmit="return confirm('¿Seguro que deseas eliminar este curso?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger rounded-4">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <h5 class="fw-semibold mb-2">
                                    {{ $search !== '' ? 'No se encontraron resultados' : 'No hay cursos registrados' }}
                                </h5>
                                <p class="text-secondary mb-0">
                                    {{ $search !== '' ? 'Ajusta el término de búsqueda o limpia el filtro.' : 'Crea el primer curso para comenzar.' }}
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($courses->hasPages())
            <div class="p-4 border-top d-flex justify-content-center overflow-auto">
                {{ $courses->onEachSide(1)->links() }}
            </div>
        @endif
    </section>
</div>
@endsection
@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.2.2/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.4/css/responsive.bootstrap5.min.css">

    <style>
        table.dataTable > tbody > tr.child ul.dtr-details {
            width: 100%;
        }

        table.dataTable > tbody > tr.child ul.dtr-details > li {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: .75rem 0;
            border-bottom: 1px solid rgba(0, 0, 0, .075);
        }

        table.dataTable > tbody > tr.child span.dtr-title {
            font-weight: 700;
            color: var(--school-secondary);
        }

        .dt-container .dt-search input,
        .dt-container .dt-length select {
            border-radius: 1rem;
            border: 1px solid rgba(0, 0, 0, .15);
            padding: .45rem .75rem;
        }

        .dt-container .dt-paging .dt-paging-button {
            border-radius: .75rem !important;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.2.2/js/dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/2.2.2/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.4/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.4/js/responsive.bootstrap5.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const table = $('#coursesTable');

            if ($.fn.DataTable.isDataTable(table)) {
                table.DataTable().destroy();
            }

            table.DataTable({
                responsive: true,
                autoWidth: false,
                paging: false,
                searching: false,
                info: false,
                ordering: true,
                columnDefs: [
                    {
                        targets: -1,
                        orderable: false,
                        searchable: false,
                        responsivePriority: 1
                    },
                    {
                        targets: 0,
                        responsivePriority: 2
                    },
                    {
                        targets: 4,
                        responsivePriority: 3
                    }
                ],
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/2.2.2/i18n/es-ES.json'
                }
            });
        });
    </script>
@endpush