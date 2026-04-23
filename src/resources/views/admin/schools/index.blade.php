@extends('layouts.app')

@section('content')
<div class="d-flex flex-column gap-4">

    <section class="admin-hero p-4 p-md-5">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-lg-8">
                <div class="text-uppercase small fw-semibold text-light-emphasis mb-2">
                    Administración
                </div>
                <h1 class="display-6 fw-bold mb-2">Colegios</h1>
                <p class="mb-0 text-light-emphasis">
                    Gestiona instituciones, branding y estado general del sistema desde un solo módulo.
                </p>
            </div>

            <div class="col-12 col-lg-4 text-lg-end">
                <a href="{{ route('admin.schools.create') }}"
                   class="btn text-white rounded-4 px-4 py-3 fw-semibold"
                   style="background-color: var(--school-primary);">
                    + Nuevo colegio
                </a>
            </div>
        </div>
    </section>

    @if(session('success'))
        <div class="alert alert-success rounded-4 border-0 shadow-sm mb-0">
            {{ session('success') }}
        </div>
    @endif

    <section class="admin-card bg-white p-4">
        <form method="GET" action="{{ route('admin.schools.index') }}">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-8">
                    <label for="search" class="form-label fw-semibold">Buscar colegio</label>
                    <input
                        type="text"
                        id="search"
                        name="search"
                        value="{{ $search }}"
                        class="form-control form-control-lg rounded-4"
                        placeholder="Buscar por nombre o slug..."
                    >
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
                Mostrando {{ $schools->firstItem() ?? 0 }} - {{ $schools->lastItem() ?? 0 }} de {{ $schools->total() }} colegios
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
                        <th>Colegio</th>
                        <th>Slug</th>
                        <th>Branding</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schools as $school)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="school-avatar">
                                        @if ($school->shield_path)
                                            <img src="{{ asset('storage/' . $school->shield_path) }}"
                                                 alt="Escudo {{ $school->name }}"
                                                 class="w-100 h-100 object-fit-cover">
                                        @else
                                            <span>{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($school->name, 0, 1)) }}</span>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="fw-semibold">{{ $school->name }}</div>
                                        <small class="text-secondary">ID: {{ $school->id }}</small>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span class="badge text-bg-light rounded-pill px-3 py-2">
                                    {{ $school->slug ?: 'Sin slug' }}
                                </span>
                            </td>

                            <td>
                                <div class="d-flex gap-2">
                                    <span class="color-dot" style="background-color: {{ $school->primary_color }}"></span>
                                    <span class="color-dot" style="background-color: {{ $school->secondary_color }}"></span>
                                    <span class="color-dot" style="background-color: {{ $school->accent_color }}"></span>
                                </div>
                            </td>

                            <td>
                                @if($school->is_active)
                                    <span class="badge rounded-pill text-bg-success px-3 py-2">Activo</span>
                                @else
                                    <span class="badge rounded-pill text-bg-danger px-3 py-2">Inactivo</span>
                                @endif
                            </td>

                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('admin.schools.edit', $school->id) }}" class="btn btn-outline-secondary rounded-4">
                                        Editar
                                    </a>

                                    <form method="POST"
                                          action="{{ route('admin.schools.destroy', $school->id) }}"
                                          onsubmit="return confirm('¿Seguro que deseas eliminar este colegio?')">
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
                                    {{ $search !== '' ? 'No se encontraron resultados' : 'No hay colegios registrados' }}
                                </h5>
                                <p class="text-secondary mb-0">
                                    {{ $search !== '' ? 'Ajusta el término de búsqueda o limpia el filtro.' : 'Crea el primer colegio para comenzar.' }}
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($schools->hasPages())
            <div class="p-4 border-top">
                {{ $schools->links() }}
            </div>
        @endif
    </section>
</div>
@endsection