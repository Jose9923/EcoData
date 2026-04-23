@extends('layouts.app')

@section('content')
<div class="d-flex flex-column gap-4">

    <section class="admin-hero p-4 p-md-5">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-lg-8">
                <div class="text-uppercase small fw-semibold text-light-emphasis mb-2">
                    Administración
                </div>
                <h1 class="display-6 fw-bold mb-2">Grados</h1>
                <p class="mb-0 text-light-emphasis">
                    Gestiona los grados académicos por colegio y su disponibilidad dentro del sistema.
                </p>
            </div>

            <div class="col-12 col-lg-4 text-lg-end">
                <a href="{{ route('admin.grades.create') }}" class="btn text-white rounded-4 px-4 py-3 fw-semibold"
                   style="background-color: var(--school-primary);">
                    + Nuevo grado
                </a>
            </div>
        </div>
    </section>

    @if(session('success'))
        <div class="alert alert-success rounded-4 border-0 shadow-sm mb-0">{{ session('success') }}</div>
    @endif

    <section class="admin-card bg-white p-4">
        <form method="GET" action="{{ route('admin.grades.index') }}">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-8">
                    <label for="search" class="form-label fw-semibold">Buscar grado</label>
                    <input type="text" id="search" name="search" value="{{ $search }}"
                           class="form-control form-control-lg rounded-4"
                           placeholder="Buscar por nombre, etiqueta o colegio...">
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
                Mostrando {{ $grades->firstItem() ?? 0 }} - {{ $grades->lastItem() ?? 0 }} de {{ $grades->total() }} grados
            </div>

            @if($search !== '')
                <div class="small fw-semibold text-secondary">
                    Filtro aplicado: "{{ $search }}"
                </div>
            @endif
        </div>
    </section>

    <section class="admin-card bg-white overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Grado</th>
                        <th>Etiqueta</th>
                        <th>Colegio</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($grades as $grade)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $grade->name }}</div>
                            </td>
                            <td>
                                @if ($grade->label)
                                    <span class="badge text-bg-light rounded-pill px-3 py-2">
                                        {{ $grade->label }}
                                    </span>
                                @else
                                    <span class="text-secondary small">Sin etiqueta</span>
                                @endif
                            </td>
                            <td>
                                {{ $grade->school?->name ?? 'Sin asignar' }}
                            </td>
                            <td>
                                @if($grade->is_active)
                                    <span class="badge rounded-pill text-bg-success px-3 py-2">Activo</span>
                                @else
                                    <span class="badge rounded-pill text-bg-danger px-3 py-2">Inactivo</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('admin.grades.edit', $grade->id) }}" class="btn btn-outline-secondary rounded-4">
                                        Editar
                                    </a>

                                    <form method="POST" action="{{ route('admin.grades.destroy', $grade->id) }}"
                                          onsubmit="return confirm('¿Seguro que deseas eliminar este grado?')">
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
                            <td colspan="5" class="text-center py-5">
                                <h5 class="fw-semibold mb-2">
                                    {{ $search !== '' ? 'No se encontraron resultados' : 'No hay grados registrados' }}
                                </h5>
                                <p class="text-secondary mb-0">
                                    {{ $search !== '' ? 'Ajusta el término de búsqueda o limpia el filtro.' : 'Crea el primer grado para comenzar.' }}
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($grades->hasPages())
            <div class="p-4 border-top">
                {{ $grades->links() }}
            </div>
        @endif
    </section>
</div>
@endsection