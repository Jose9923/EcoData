@extends('layouts.app')

@section('content')
<div class="d-flex flex-column gap-4">

    <section class="admin-hero p-4 p-md-5">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-lg-8">
                <div class="text-uppercase small fw-semibold text-light-emphasis mb-2">
                    Administración
                </div>
                <h1 class="display-6 fw-bold mb-2">Variables físicas</h1>
                <p class="mb-0 text-light-emphasis">
                    Parametriza las variables físicas por colegio, categoría, tipo de dato y reglas de validación.
                </p>
            </div>

            <div class="col-12 col-lg-4 text-lg-end">
                <a href="{{ route('admin.physical-variables.create') }}"
                   class="btn text-white rounded-4 px-4 py-3 fw-semibold"
                   style="background-color: var(--school-primary);">
                    + Nueva variable
                </a>
            </div>
        </div>
    </section>

    @if(session('success'))
        <div class="alert alert-success rounded-4 border-0 shadow-sm mb-0">{{ session('success') }}</div>
    @endif

    <section class="admin-card bg-white p-4">
        <form method="GET" action="{{ route('admin.physical-variables.index') }}">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-8">
                    <label for="search" class="form-label fw-semibold">Buscar variable</label>
                    <input type="text" id="search" name="search" value="{{ $search }}"
                           class="form-control form-control-lg rounded-4"
                           placeholder="Buscar por nombre, slug, unidad, tipo, categoría o colegio...">
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
            <table id="physicalVariablesTable" class="table table-striped table-hover align-middle nowrap w-100 mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Variable</th>
                        <th>Colegio</th>
                        <th>Categoría</th>
                        <th>Tipo / Unidad</th>
                        <th>Reglas</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($variables as $variable)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $variable->name }}</div>
                                <small class="text-secondary">{{ $variable->slug }}</small>
                            </td>
                            <td>{{ $variable->school?->name ?? 'Sin asignar' }}</td>
                            <td>{{ $variable->category?->name ?? 'Sin asignar' }}</td>
                            <td>
                                <div><strong>{{ ucfirst($variable->data_type) }}</strong></div>
                                <small class="text-secondary">{{ $variable->unit ?: 'Sin unidad' }}</small>
                            </td>
                            <td class="small text-secondary">
                                <div>
                                    <strong>Mín:</strong>
                                    {{ $variable->min_value !== null ? number_format((float) $variable->min_value, $variable->decimals ?? 0, '.', '') : '—' }}
                                    {{ $variable->unit ? ' ' . $variable->unit : '' }}
                                </div>
                                <div>
                                    <strong>Máx:</strong>
                                    {{ $variable->max_value !== null ? number_format((float) $variable->max_value, $variable->decimals ?? 0, '.', '') : '—' }}
                                    {{ $variable->unit ? ' ' . $variable->unit : '' }}
                                </div>
                                <div><strong>Decimales:</strong> {{ $variable->decimals }}</div>
                            </td>
                            <td>
                                @if($variable->is_active)
                                    <span class="badge rounded-pill text-bg-success px-3 py-2">Activo</span>
                                @else
                                    <span class="badge rounded-pill text-bg-danger px-3 py-2">Inactivo</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('admin.physical-variables.edit', $variable->id) }}"
                                       class="btn btn-outline-secondary rounded-4">
                                        Editar
                                    </a>

                                    <form method="POST"
                                          action="{{ route('admin.physical-variables.destroy', $variable->id) }}"
                                          onsubmit="return confirm('¿Seguro que deseas eliminar esta variable?')">
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
                            <td colspan="7" class="text-center py-5">
                                <h5 class="fw-semibold mb-2">
                                    {{ $search !== '' ? 'No se encontraron resultados' : 'No hay variables registradas' }}
                                </h5>
                                <p class="text-secondary mb-0">
                                    {{ $search !== '' ? 'Ajusta el término de búsqueda o limpia el filtro.' : 'Crea la primera variable para comenzar.' }}
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($variables->hasPages())
            <div class="p-4 border-top d-flex justify-content-center overflow-auto">
                {{ $variables->onEachSide(1)->links() }}
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

        #physicalVariablesTable td:nth-child(1),
        #physicalVariablesTable td:nth-child(5) {
            min-width: 220px;
            white-space: normal;
        }

        #physicalVariablesTable td:last-child {
            white-space: nowrap;
        }

        @media (max-width: 768px) {
            #physicalVariablesTable td:last-child .d-flex {
                flex-wrap: wrap;
                justify-content: flex-start !important;
            }

            #physicalVariablesTable td:last-child .btn {
                padding: .35rem .65rem;
                font-size: .875rem;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const table = $('#physicalVariablesTable');

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
                        targets: 5,
                        responsivePriority: 3
                    },
                    {
                        targets: 3,
                        responsivePriority: 4
                    },
                    {
                        targets: 2,
                        responsivePriority: 5
                    },
                    {
                        targets: 1,
                        responsivePriority: 6
                    },
                    {
                        targets: 4,
                        responsivePriority: 7
                    }
                ],
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/2.2.2/i18n/es-ES.json'
                }
            });
        });
    </script>
@endpush