@extends('layouts.app')

@section('content')
<div class="d-flex flex-column gap-4">
    <section class="admin-hero p-4 p-md-5">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h2 fw-bold mb-2">Guías de laboratorio</h1>
                <p class="mb-0 text-light-emphasis">Gestiona las guías en PDF para estudiantes.</p>
            </div>
            <a href="{{ route('admin.laboratory-guides.create') }}" class="btn text-white rounded-4 px-4"
               style="background-color: var(--school-primary);">
                Nueva guía
            </a>
        </div>
    </section>

    <div class="admin-card bg-white p-4">
        <form method="GET">
            <div class="row g-3">
                <div class="col-12 col-md-10">
                    <input type="text" name="search" class="form-control rounded-4"
                           value="{{ $search }}" placeholder="Buscar por título o descripción...">
                </div>
                <div class="col-12 col-md-2 d-grid">
                    <button class="btn btn-dark rounded-4">Buscar</button>
                </div>
            </div>
        </form>
    </div>

    <div class="admin-card bg-white overflow-hidden">
        <div class="p-3 p-md-4">
            <table id="laboratoryGuidesTable" class="table table-striped table-hover align-middle nowrap w-100 mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Título</th>
                        <th>Destino</th>
                        <th>Publicado</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($guides as $guide)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $guide->title }}</div>
                                <small class="text-secondary">{{ $guide->description }}</small>
                            </td>
                            <td>
                                <div>{{ $guide->school?->name }}</div>
                                <small class="text-secondary">
                                    {{ $guide->grade?->label ?: $guide->grade?->name ?: 'Todos los grados' }}
                                    /
                                    {{ $guide->course?->label ?: $guide->course?->name ?: 'Todos los cursos' }}
                                </small>
                            </td>
                            <td>{{ optional($guide->published_at)->format('Y-m-d H:i') }}</td>
                            <td>
                                @if($guide->is_active)
                                    <span class="badge text-bg-success rounded-pill">Activa</span>
                                @else
                                    <span class="badge text-bg-danger rounded-pill">Inactiva</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('admin.laboratory-guides.download', $guide) }}" target="_blank" class="btn btn-outline-secondary rounded-4">
                                        Ver PDF
                                    </a>
                                    <a href="{{ route('admin.laboratory-guides.edit', $guide) }}" class="btn btn-outline-primary rounded-4">
                                        Editar
                                    </a>
                                    <form method="POST" action="{{ route('admin.laboratory-guides.destroy', $guide) }}" onsubmit="return confirm('¿Eliminar esta guía?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger rounded-4">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($guides->hasPages())
            <div class="p-4 border-top d-flex justify-content-center overflow-auto">
                {{ $guides->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
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

        #laboratoryGuidesTable td:last-child {
            white-space: nowrap;
        }

        @media (max-width: 768px) {
            #laboratoryGuidesTable td:last-child .d-flex {
                flex-wrap: wrap;
            }

            #laboratoryGuidesTable td:last-child .btn {
                padding: .35rem .65rem;
                font-size: .875rem;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const table = $('#laboratoryGuidesTable');

            if ($.fn.DataTable.isDataTable('#laboratoryGuidesTable')) {
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
                        targets: 3,
                        responsivePriority: 3
                    },
                    {
                        targets: 1,
                        responsivePriority: 4
                    },
                    {
                        targets: 2,
                        responsivePriority: 5
                    }
                ],
                language: {
                    emptyTable: "No hay guías registradas.",
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