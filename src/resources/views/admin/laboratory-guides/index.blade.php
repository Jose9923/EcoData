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
        <div class="table-responsive">
            <table class="table align-middle mb-0">
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
                    @forelse($guides as $guide)
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
                                    <a href="{{ asset('storage/' . $guide->pdf_path) }}" target="_blank" class="btn btn-outline-secondary rounded-4">Ver PDF</a>
                                    <a href="{{ route('admin.laboratory-guides.edit', $guide) }}" class="btn btn-outline-primary rounded-4">Editar</a>
                                    <form method="POST" action="{{ route('admin.laboratory-guides.destroy', $guide) }}" onsubmit="return confirm('¿Eliminar esta guía?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger rounded-4">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">No hay guías registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($guides->hasPages())
            <div class="p-4 border-top">
                {{ $guides->links() }}
            </div>
        @endif
    </div>
</div>
@endsection