@extends('layouts.app')

@section('content')
<div class="d-flex flex-column gap-4">

    <section class="admin-hero p-4 p-md-5">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-lg-8">
                <div class="text-uppercase small fw-semibold admin-hero-subtitle mb-2">
                    Administración
                </div>
                <h1 class="display-6 fw-bold mb-2">Categorías de Variables Físicas</h1>
                <p class="mb-0 admin-hero-subtitle">
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
            <table id="physicalVariableCategoriesTable"        
                class="table table-striped table-hover align-middle nowrap w-100 mb-0 js-ecodata-datatable"
                data-paging="false"
                data-searching="false"
                data-info="false"
                data-empty="No hay categorías de variables físicas registradas.">
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

                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger rounded-3"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteCategoryModal{{ $category->id }}">
                                        Eliminar
                                    </button>

                                    <div class="modal fade"
                                        id="deleteCategoryModal{{ $category->id }}"
                                        tabindex="-1"
                                        aria-labelledby="deleteCategoryModalLabel{{ $category->id }}"
                                        aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content rounded-4 border-0 shadow">

                                                <div class="modal-header border-0 pb-0">
                                                    <div>
                                                        <h5 class="modal-title fw-bold mb-1" id="deleteCategoryModalLabel{{ $category->id }}">
                                                            Confirmar eliminación
                                                        </h5>
                                                        <p class="text-muted small mb-0">
                                                            Esta acción requiere confirmación.
                                                        </p>
                                                    </div>

                                                    <button type="button"
                                                            class="btn-close"
                                                            data-bs-dismiss="modal"
                                                            aria-label="Cerrar"></button>
                                                </div>

                                                <div class="modal-body pt-4">
                                                    <p class="mb-3">
                                                        ¿Seguro que deseas eliminar la categoría
                                                        <strong>{{ $category->name }}</strong>?
                                                    </p>

                                                    <div class="rounded-4 p-3 w-100"
                                                        style="background-color: #fff8e5; border: 1px solid #ffe4a3; overflow-wrap: break-word; word-break: normal; white-space: normal;">
                                                        <div class="fw-semibold mb-1" style="color: #7a5200;">
                                                            Importante
                                                        </div>

                                                        <p class="small mb-0" style="color: #7a5200; line-height: 1.45; white-space: normal;">
                                                            Si esta categoría tiene variables físicas asociadas, no se eliminará.
                                                            En su lugar, el sistema la desactivará para proteger los datos existentes.
                                                        </p>
                                                    </div>
                                                </div>

                                                <div class="modal-footer border-0 pt-0">
                                                    <button type="button"
                                                            class="btn btn-outline-secondary rounded-4 px-4"
                                                            data-bs-dismiss="modal">
                                                        Cancelar
                                                    </button>

                                                    <form action="{{ route('admin.physical-variable-categories.destroy', $category) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('DELETE')

                                                        <button type="submit"
                                                                class="btn text-white rounded-4 px-4 fw-semibold"
                                                                style="background-color: #c72f3b;">
                                                            Sí, eliminar
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
