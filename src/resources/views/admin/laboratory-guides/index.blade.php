@extends('layouts.app')

@section('content')
<div class="d-flex flex-column gap-4">
    <section class="admin-hero p-4 p-md-5">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h2 fw-bold mb-2">Guías de laboratorio</h1>
                <p class="mb-0 admin-hero-subtitle">Gestiona las guías en PDF para estudiantes.</p>
            </div>
            <div class="col-12 col-lg-4 text-lg-end">
                <a href="{{ route('admin.laboratory-guides.create') }}" class="btn text-white rounded-4 px-4 py-3 fw-semibold"
                   style="background-color: var(--school-primary);">
                    + Nueva guía
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
                    <button class="btn btn-dark rounded-4">Filtrar</button>
                </div>
            </div>
        </form>
    </div>

    <div class="admin-card bg-white overflow-hidden">
        <div class="p-3 p-md-4">
            <table id="laboratoryGuidesTable"        
                class="table table-striped table-hover align-middle nowrap w-100 mb-0 js-ecodata-datatable"
                data-paging="false"
                data-searching="false"
                data-info="false"
                data-empty="No hay grados registrados.">
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
                                    <a href="{{ route('admin.laboratory-guides.download', $guide) }}"
                                    target="_blank"
                                    class="btn btn-outline-primary rounded-4">
                                        Ver PDF
                                    </a>

                                    <a href="{{ route('admin.laboratory-guides.edit', $guide) }}"
                                    class="btn btn-outline-secondary rounded-4">
                                        Editar
                                    </a>

                                    <form method="POST"
                                        action="{{ route('admin.laboratory-guides.destroy', $guide) }}"
                                        class="js-confirm-delete"
                                        data-title="¿Eliminar guía de laboratorio?"
                                        data-text="Esta acción eliminará la guía de laboratorio {{ $guide->title }}. Si tiene información asociada, el sistema podría impedir la eliminación."
                                        data-confirm-button="Sí, eliminar">
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

        @if($guides->hasPages())
            <div class="p-4 border-top d-flex justify-content-center overflow-auto">
                {{ $guides->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
