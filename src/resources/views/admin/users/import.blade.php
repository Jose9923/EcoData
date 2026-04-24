@extends('layouts.app')

@section('content')
<div class="d-flex flex-column gap-4">
    <section class="admin-hero p-4 p-md-5">
        <h1 class="h2 fw-bold mb-2">Cargue masivo de usuarios</h1>
        <p class="mb-0 text-light-emphasis">
            Descarga la plantilla, complétala y sube el archivo Excel para crear o actualizar usuarios.
        </p>
    </section>

    <div class="admin-card bg-white p-4">
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.users.import.template') }}" class="btn btn-outline-dark rounded-4">
                Descargar plantilla Excel
            </a>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary rounded-4">
                Volver a usuarios
            </a>
        </div>
    </div>

    <div class="admin-card bg-white p-4">
        <form method="POST" action="{{ route('admin.users.import.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Archivo Excel</label>
                    <input type="file" name="file" class="form-control rounded-4 @error('file') is-invalid @enderror" accept=".xlsx,.xls">
                    @error('file')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Modo de importación</label>
                    <select name="mode" class="form-select rounded-4 @error('mode') is-invalid @enderror">
                        <option value="create_only" @selected(old('mode') === 'create_only')>Solo crear nuevos</option>
                        <option value="update_or_create" @selected(old('mode') === 'update_or_create')>Crear o actualizar</option>
                    </select>
                    @error('mode')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn text-white rounded-4 px-4"
                        style="background-color: var(--school-primary);">
                    Procesar archivo
                </button>
            </div>
        </form>
    </div>

    @if(session('import_summary'))
        @php($summary = session('import_summary'))
        <div class="admin-card bg-white p-4">
            <h5 class="fw-bold mb-3">Resultado del cargue</h5>

            <div class="row g-3 mb-4">
                <div class="col-12 col-md-3">
                    <div class="border rounded-4 p-3">
                        <div class="small text-secondary">Creados</div>
                        <div class="fs-4 fw-bold">{{ $summary['created'] }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="border rounded-4 p-3">
                        <div class="small text-secondary">Actualizados</div>
                        <div class="fs-4 fw-bold">{{ $summary['updated'] }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="border rounded-4 p-3">
                        <div class="small text-secondary">Omitidos</div>
                        <div class="fs-4 fw-bold">{{ $summary['skipped'] }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="border rounded-4 p-3">
                        <div class="small text-secondary">Errores</div>
                        <div class="fs-4 fw-bold">{{ count($summary['errors']) }}</div>
                    </div>
                </div>
            </div>

            @if(!empty($summary['errors']))
                <div class="alert alert-warning rounded-4 mb-0">
                    <h6 class="fw-bold">Filas con error</h6>
                    <ul class="mb-0">
                        @foreach($summary['errors'] as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection