@extends('layouts.app')

@section('title', 'Eventos ambientales')

@section('content')
    <section class="admin-hero p-4 p-md-5">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-lg-8">
                <div class="text-uppercase small fw-semibold admin-hero-subtitle mb-2">
                    Calendario ambiental
                </div>
                <h1 class="display-6 fw-bold mb-2">Eventos ambientales</h1>
                <p class="mb-0 admin-hero-subtitle">
                    Consulta las fechas ambientales, jornadas institucionales y conmemoraciones ecológicas de tu colegio.
                </p>
            </div>
        </div>
    </section>

    @if($todayEvents->isNotEmpty())
        <section class="admin-card bg-white p-4">
            <div class="mb-4">
                <h2 class="h5 fw-bold mb-1">Eventos de hoy</h2>
                <p class="text-muted mb-0">
                    Actividades o conmemoraciones ambientales activas para la fecha actual.
                </p>
            </div>

            <div class="row g-4">
                @foreach($todayEvents as $event)
                    @include('environmental-events.partials.card', ['event' => $event])
                @endforeach
            </div>
        </section>
    @endif

    <section class="admin-card bg-white p-4">
        <div class="mb-4">
            <h2 class="h5 fw-bold mb-1">Próximos eventos</h2>
            <p class="text-muted mb-0">
                Fechas ambientales programadas para los próximos días.
            </p>
        </div>

        <div class="row g-4">
            @forelse($upcomingEvents as $event)
                @include('environmental-events.partials.card', ['event' => $event])
            @empty
                <div class="col-12">
                    <div class="text-center text-muted py-5">
                        No hay próximos eventos ambientales programados.
                    </div>
                </div>
            @endforelse
        </div>
    </section>

    @if($pastEvents->isNotEmpty())
        <section class="admin-card bg-white p-4">
            <div class="mb-4">
                <h2 class="h5 fw-bold mb-1">Eventos anteriores</h2>
                <p class="text-muted mb-0">
                    Historial reciente de eventos ambientales institucionales.
                </p>
            </div>

            <div class="row g-4">
                @foreach($pastEvents->take(8) as $event)
                    @include('environmental-events.partials.card', ['event' => $event])
                @endforeach
            </div>
        </section>
    @endif
    @include('environmental-events.partials.daily-modal')
@endsection