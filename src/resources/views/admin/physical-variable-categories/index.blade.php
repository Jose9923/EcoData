@extends('layouts.app')

@section('content')
<div class="d-flex flex-column gap-4">

    <section class="admin-hero p-4 p-md-5">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-lg-8">
                <div class="text-uppercase small fw-semibold text-light-emphasis mb-2">
                    Administración
                </div>
                <h1 class="display-6 fw-bold mb-2">Categorías de Variables Físicas</h1>
                <p class="mb-0 text-light-emphasis">
                    Organiza las variables físicas por categorías funcionales para mantener la estructura del sistema.
                </p>
            </div>

            <div class="col-12 col-lg-4 text-lg-end">
                <a href="{{ route('admin.physical-variable-categories.create') }}"
                   class="btn text-white rounded-4 px-4 py-3 fw-semibold"
                   style="background-color: var(--school-primary);">
                    + Nueva categoría
                </a>
            </div>
        </div>
    </section>

    @if(session('success'))
        <div class="alert alert-success rounded-4 border-0 shadow-sm mb-0">{{ session('success') }}</div>
    @endif

    <section class="admin-card bg-white p-4">
        <form method="GET" action="{{ route('admin.physical-variable-categories.index') }}">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-8">
                    <label for="search" class="form-label fw-semibold">Buscar categoría</label>
                    <input type="text" id="search" name="search" value="{{ $search }}"
                           class="form-control form-control-lg rounded-4"
                           placeholder="Buscar por nombre, slug, descripción">
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
    </section>

    <section class="admin-card bg-white overflow-hidden">
        <div class="p-3 p-md-4">
            <table id="physicalVariableCategoriesTable" class="table table-striped table-hover align-middle nowrap w-100 mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Slug</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categories as $category)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $category->name }}</div>
                                @if($category->description)
                                    <small class="text-secondary">{{ \Illuminate\Support\Str::limit($category->description, 80) }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge text-bg-light rounded-pill px-3 py-2">
                                    {{ $category->slug }}
                                </span>
                            </td>
                            <td>
                                @if($category->is_active)
                                    <span class="badge rounded-pill text-bg-success px-3 py-2">Activo</span>
                                @else
                                    <span class="badge rounded-pill text-bg-danger px-3 py-2">Inactivo</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('admin.physical-variable-categories.edit', $category->id) }}"
                                    class="btn btn-outline-secondary rounded-4">
                                        Editar
                                    </a>

                                    <form method="POST"
                                        action="{{ route('admin.physical-variable-categories.destroy', $category->id) }}"
                                        onsubmit="return confirm('¿Seguro que deseas eliminar esta categoría?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger rounded-4">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($categories->hasPages())
            <div class="p-4 border-top d-flex justify-content-center overflow-auto">
                {{ $categories->onEachSide(1)->links() }}
            </div>
        @endif
    </section>
</div>
@endsection
@push('styles')
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

        #physicalVariableCategoriesTable td:last-child {
            white-space: nowrap;
        }

        @media (max-width: 768px) {
            #physicalVariableCategoriesTable td:last-child .d-flex {
                flex-wrap: wrap;
                justify-content: flex-start !important;
            }

            #physicalVariableCategoriesTable td:last-child .btn {
                padding: .35rem .65rem;
                font-size: .875rem;
            }
        }
    </style>
@endpush
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const table = $('#physicalVariableCategoriesTable');

            if ($.fn.DataTable.isDataTable('#physicalVariableCategoriesTable')) {
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
                        targets: 2,
                        responsivePriority: 3
                    },
                    {
                        targets: 1,
                        responsivePriority: 4
                    }
                ],
                language: {
                    emptyTable: "No hay categorías registradas.",
                    zeroRecords: "No se encontraron resultados",
                    loadingRecords: "Cargando...",
                    processing: "Procesando...",
                    search: "Buscar:",
                    lengthMenu: "Mostrar _MENU_ registros",
                    info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    infoEmpty: "Mostrando 0 a 0 de 0 registros",
                    infoFiltered: "(filtrado de _MAX_ registros totales)",
                    paginate: {
                        first: "Primero",
                        last: "Último",
                        next: "Siguiente",
                        previous: "Anterior"
                    }
                }
            });
        });
    </script>
@endpush