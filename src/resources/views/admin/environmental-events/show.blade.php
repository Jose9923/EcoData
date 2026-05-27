@extends('layouts.app')

@section('title', 'Detalle de evento ambiental')

@section('content')
    <section class="admin-hero p-4 p-md-5">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-lg-8">
                <div class="text-uppercase small fw-semibold admin-hero-subtitle mb-2">
                    Administración
                </div>
                <h1 class="display-6 fw-bold mb-2">{{ $event->title }}</h1>
                <p class="mb-0 admin-hero-subtitle">
                    Consulta la información del evento ambiental programado.
                </p>
            </div>

            <div class="col-12 col-lg-4 text-lg-end">
                <a href="{{ route('admin.environmental-events.edit', $event) }}"
                   class="btn text-white rounded-4 px-4 py-3 fw-semibold me-2"
                   style="background-color: var(--school-primary);">
                    Editar evento
                </a>

                <a href="{{ route('admin.environmental-events.index') }}"
                   class="btn btn-light rounded-4 px-4 py-3 fw-semibold">
                    Volver
                </a>
            </div>
        </div>
    </section>

    <section class="admin-card bg-white p-4">
        <div class="row g-4">
            <div class="col-12 col-lg-5">
                @if($event->image_path)
                    <img src="{{ route('environmental-events.image', $event) }}"
                         alt="{{ $event->title }}"
                         class="img-fluid rounded-4 border w-100"
                         style="max-height: 360px; object-fit: cover;">
                @else
                    <div class="border rounded-4 bg-light d-flex align-items-center justify-content-center"
                         style="height: 320px;">
                        <div class="text-center">
                            <div class="display-4 mb-2">🌎</div>
                            <div class="text-muted">Sin imagen asociada</div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-12 col-lg-7">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @if($event->is_active)
                        <span class="badge rounded-pill text-bg-success px-3 py-2">Activo</span>
                    @else
                        <span class="badge rounded-pill text-bg-secondary px-3 py-2">Inactivo</span>
                    @endif
                </div>

                <h2 class="h4 fw-bold mb-3">{{ $event->title }}</h2>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6">
                        <div class="border rounded-4 p-3 h-100">
                            <div class="text-muted small fw-semibold mb-1">Colegio</div>
                            <div class="fs-5 fw-semibold">{{ $event->school?->name ?? 'Sin colegio' }}</div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="border rounded-4 p-3 h-100">
                            <div class="text-muted small fw-semibold mb-1">Fecha</div>
                            <div class="fs-5 fw-semibold">
                                {{ $event->starts_at?->format('d/m/Y') }}
                                @if($event->ends_at && !$event->starts_at->isSameDay($event->ends_at))
                                    - {{ $event->ends_at->format('d/m/Y') }}
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="border rounded-4 p-3 h-100">
                            <div class="text-muted small fw-semibold mb-1">Creado por</div>
                            <div class="fs-5 fw-semibold">{{ $event->creator?->name ?? 'No registrado' }}</div>
                        </div>
                    </div>
                </div>

                <div class="border rounded-4 p-4">
                    <div class="text-muted small fw-semibold mb-2">Descripción</div>
                    @if($event->description)
                        <p class="mb-0">{{ $event->description }}</p>
                    @else
                        <p class="text-muted mb-0">No se registró descripción para este evento.</p>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
