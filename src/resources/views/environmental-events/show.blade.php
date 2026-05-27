@extends('layouts.app')

@section('title', $event->title)

@section('content')
    <section class="admin-hero p-4 p-md-5">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-lg-8">
                <div class="text-uppercase small fw-semibold admin-hero-subtitle mb-2">
                    Calendario ambiental
                </div>
                <h1 class="display-6 fw-bold mb-2">{{ $event->title }}</h1>
                <p class="mb-0 admin-hero-subtitle">
                    Evento ambiental programado por tu institución.
                </p>
            </div>

            <div class="col-12 col-lg-4 text-lg-end">
                <a href="{{ route('environmental-events.index') }}"
                   class="btn text-white rounded-4 px-4 py-3 fw-semibold"
                   style="background-color: var(--school-primary);">
                    Volver al calendario
                </a>
            </div>
        </div>
    </section>

    <section class="admin-card bg-white p-4">
        <div class="row g-4 align-items-start">
            <div class="col-12 col-lg-5">
                @if($event->image_path)
                    <img src="{{ route('environmental-events.image', $event) }}"
                         alt="{{ $event->title }}"
                         class="img-fluid rounded-4 border w-100"
                         style="max-height: 420px; object-fit: cover;">
                @else
                    <div class="border rounded-4 bg-light d-flex align-items-center justify-content-center"
                         style="height: 320px;">
                        <div class="text-center">
                            <div class="display-4 mb-2">🌎</div>
                            <div class="text-muted">Calendario ambiental</div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-12 col-lg-7">
                <div class="text-muted small fw-semibold mb-2">
                    {{ $event->school?->name }}
                </div>

                <h2 class="h3 fw-bold mb-3">{{ $event->title }}</h2>

                <div class="border rounded-4 p-3 mb-4">
                    <div class="text-muted small fw-semibold mb-1">Fecha</div>
                    <div class="fs-5 fw-semibold">
                        {{ $event->starts_at?->format('d/m/Y') }}
                        @if($event->ends_at && !$event->starts_at->isSameDay($event->ends_at))
                            - {{ $event->ends_at->format('d/m/Y') }}
                        @endif
                    </div>
                </div>

                <div class="border rounded-4 p-4">
                    <div class="text-muted small fw-semibold mb-2">Descripción</div>
                    @if($event->description)
                        <p class="mb-0">{{ $event->description }}</p>
                    @else
                        <p class="text-muted mb-0">No hay descripción registrada para este evento.</p>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
