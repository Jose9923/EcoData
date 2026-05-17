@extends('layouts.app')

@section('content')
@php
    $sourceTypes = [
        'manual' => 'Manual',
        'station' => 'Estación meteorológica',
        'csv' => 'Cargue CSV',
    ];

    $hasQuickLinks =
        (($isSuperAdmin || $isSchoolAdmin) && Route::has('admin.users.create')) ||
        (($isSuperAdmin || $isSchoolAdmin || $isdocente) && Route::has('admin.physical-variable-records.create')) ||
        (($isSuperAdmin || $isSchoolAdmin || $isdocente) && Route::has('admin.laboratory-guides.create')) ||
        (($isSuperAdmin || $isSchoolAdmin || $isdocente) && Route::has('admin.weather-stations.create')) ||
        (($isSuperAdmin || $isSchoolAdmin || $isdocente) && Route::has('admin.sensors.create')) ||
        (($isSuperAdmin || $isSchoolAdmin || $isdocente) && Route::has('admin.environmental-events.create')) ||
        (($isSuperAdmin || $isSchoolAdmin || $isdocente) && Route::has('admin.field-diary-activities.create')) ||
        (($isSuperAdmin || $isSchoolAdmin || $isdocente) && Route::has('admin.field-diary-submissions.index')) ||
        (($isSuperAdmin || $isSchoolAdmin || $isdocente) && Route::has('admin.physical-variable-record-imports.create')) ||
        (($isSuperAdmin || $isSchoolAdmin) && Route::has('admin.users.import')) ||
        ($isestudiante && Route::has('estudiante.laboratory-guides.index')) ||
        ($isestudiante && Route::has('estudiante.field-diaries.index')) ||
        Route::has('environmental-events.index');
@endphp

<div class="d-flex flex-column gap-4">

    <section class="admin-hero p-4 p-md-5">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-lg-8">
                <div class="text-uppercase small fw-semibold admin-hero-subtitle mb-2">
                    Panel principal
                </div>
                <h1 class="display-6 fw-bold mb-2">Dashboard EcoData</h1>
                <p class="mb-0 admin-hero-subtitle">
                    Consulta métricas rápidas, actividad reciente y accesos directos a los módulos principales.
                </p>
            </div>
        </div>
    </section>

    <section>
        <div class="row g-4">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="admin-card bg-white p-4 h-100">
                    <p class="text-secondary small mb-2">Usuarios registrados</p>
                    <h3 class="fw-bold mb-0">{{ $stats['users'] }}</h3>
                    <small class="text-secondary">Activos: {{ $stats['active_users'] }}</small>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="admin-card bg-white p-4 h-100">
                    <p class="text-secondary small mb-2">Estaciones meteorológicas</p>
                    <h3 class="fw-bold mb-0">{{ $stats['weather_stations'] ?? 0 }}</h3>
                    <small class="text-secondary">Activas en EcoData</small>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="admin-card bg-white p-4 h-100">
                    <p class="text-secondary small mb-2">Sensores registrados</p>
                    <h3 class="fw-bold mb-0">{{ $stats['sensors'] ?? 0 }}</h3>
                    <small class="text-secondary">Operativos: {{ $stats['active_sensors'] ?? 0 }}</small>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="admin-card bg-white p-4 h-100">
                    <p class="text-secondary small mb-2">Registros físicos</p>
                    <h3 class="fw-bold mb-0">{{ $stats['physical_records'] }}</h3>
                    <small class="text-secondary">Capturas acumuladas</small>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="admin-card bg-white p-4 h-100">
                    <p class="text-secondary small mb-2">Variables físicas activas</p>
                    <h3 class="fw-bold mb-0">{{ $stats['physical_variables'] }}</h3>
                    <small class="text-secondary">Parametrizadas en el sistema</small>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="admin-card bg-white p-4 h-100">
                    <p class="text-secondary small mb-2">Guías de laboratorio</p>
                    <h3 class="fw-bold mb-0">{{ $stats['laboratory_guides'] }}</h3>
                    <small class="text-secondary">Activas y disponibles</small>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="admin-card bg-white p-4 h-100">
                    <p class="text-secondary small mb-2">Eventos ambientales</p>
                    <h3 class="fw-bold mb-0">{{ $stats['environmental_events'] ?? 0 }}</h3>
                    <small class="text-secondary">Activos o programados</small>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="admin-card bg-white p-4 h-100">
                    <p class="text-secondary small mb-2">Diario de Campo</p>
                    <h3 class="fw-bold mb-0">{{ $stats['field_diary_activities'] ?? 0 }}</h3>
                    <small class="text-secondary">
                        Entregas: {{ $stats['field_diary_submissions'] ?? 0 }}
                    </small>
                </div>
            </div>
        </div>
    </section>

    <section>
        <div class="admin-card bg-white p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">Accesos rápidos</h5>
                <small class="text-secondary">Operaciones frecuentes</small>
            </div>

            <div class="row g-3">
                @if(($isSuperAdmin || $isSchoolAdmin) && Route::has('admin.users.create'))
                    <div class="col-12 col-md-6 col-xl-3">
                        <a href="{{ route('admin.users.create') }}" class="btn btn-outline-dark rounded-4 w-100 py-3">
                            Nuevo usuario
                        </a>
                    </div>
                @endif

                @if(($isSuperAdmin || $isSchoolAdmin || $isdocente) && Route::has('admin.physical-variable-records.create'))
                    <div class="col-12 col-md-6 col-xl-3">
                        <a href="{{ route('admin.physical-variable-records.create') }}" class="btn btn-outline-dark rounded-4 w-100 py-3">
                            Nuevo registro físico
                        </a>
                    </div>
                @endif

                @if(($isSuperAdmin || $isSchoolAdmin || $isdocente) && Route::has('admin.laboratory-guides.create'))
                    <div class="col-12 col-md-6 col-xl-3">
                        <a href="{{ route('admin.laboratory-guides.create') }}" class="btn btn-outline-dark rounded-4 w-100 py-3">
                            Nueva guía de laboratorio
                        </a>
                    </div>
                @endif

                @if(($isSuperAdmin || $isSchoolAdmin || $isdocente) && Route::has('admin.weather-stations.create'))
                    <div class="col-12 col-md-6 col-xl-3">
                        <a href="{{ route('admin.weather-stations.create') }}" class="btn btn-outline-dark rounded-4 w-100 py-3">
                            Nueva estación
                        </a>
                    </div>
                @endif

                @if(($isSuperAdmin || $isSchoolAdmin || $isdocente) && Route::has('admin.sensors.create'))
                    <div class="col-12 col-md-6 col-xl-3">
                        <a href="{{ route('admin.sensors.create') }}" class="btn btn-outline-dark rounded-4 w-100 py-3">
                            Nuevo sensor
                        </a>
                    </div>
                @endif

                @if(($isSuperAdmin || $isSchoolAdmin || $isdocente) && Route::has('admin.environmental-events.create'))
                    <div class="col-12 col-md-6 col-xl-3">
                        <a href="{{ route('admin.environmental-events.create') }}" class="btn btn-outline-dark rounded-4 w-100 py-3">
                            Nuevo evento ambiental
                        </a>
                    </div>
                @endif

                @if(($isSuperAdmin || $isSchoolAdmin || $isdocente) && Route::has('admin.field-diary-activities.create'))
                    <div class="col-12 col-md-6 col-xl-3">
                        <a href="{{ route('admin.field-diary-activities.create') }}" class="btn btn-outline-dark rounded-4 w-100 py-3">
                            Nueva actividad diario
                        </a>
                    </div>
                @endif

                @if(($isSuperAdmin || $isSchoolAdmin || $isdocente) && Route::has('admin.field-diary-submissions.index'))
                    <div class="col-12 col-md-6 col-xl-3">
                        <a href="{{ route('admin.field-diary-submissions.index') }}" class="btn btn-outline-dark rounded-4 w-100 py-3">
                            Revisar diarios
                        </a>
                    </div>
                @endif

                @if(($isSuperAdmin || $isSchoolAdmin || $isdocente) && Route::has('admin.physical-variable-record-imports.create'))
                    <div class="col-12 col-md-6 col-xl-3">
                        <a href="{{ route('admin.physical-variable-record-imports.create') }}" class="btn btn-outline-dark rounded-4 w-100 py-3">
                            Cargue CSV
                        </a>
                    </div>
                @endif

                @if(($isSuperAdmin || $isSchoolAdmin) && Route::has('admin.users.index'))
                    <div class="col-12 col-md-6 col-xl-3">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-dark rounded-4 w-100 py-3">
                            Gestionar usuarios
                        </a>
                    </div>
                @endif

                @if(($isSuperAdmin || $isSchoolAdmin) && Route::has('admin.users.import'))
                    <div class="col-12 col-md-6 col-xl-3">
                        <a href="{{ route('admin.users.import') }}" class="btn btn-outline-dark rounded-4 w-100 py-3">
                            Cargue masivo Excel
                        </a>
                    </div>
                @endif

                @if($isestudiante && Route::has('estudiante.laboratory-guides.index'))
                    <div class="col-12 col-md-6 col-xl-3">
                        <a href="{{ route('estudiante.laboratory-guides.index') }}" class="btn btn-outline-dark rounded-4 w-100 py-3">
                            Ver mis guías
                        </a>
                    </div>
                @endif

                @if($isestudiante && Route::has('estudiante.field-diaries.index'))
                    <div class="col-12 col-md-6 col-xl-3">
                        <a href="{{ route('estudiante.field-diaries.index') }}" class="btn btn-outline-dark rounded-4 w-100 py-3">
                            Mi diario de campo
                        </a>
                    </div>
                @endif

                @if(Route::has('environmental-events.index'))
                    <div class="col-12 col-md-6 col-xl-3">
                        <a href="{{ route('environmental-events.index') }}" class="btn btn-outline-dark rounded-4 w-100 py-3">
                            Eventos ambientales
                        </a>
                    </div>
                @endif

                @unless($hasQuickLinks)
                    <div class="col-12">
                        <div class="text-secondary small">
                            No hay accesos rápidos disponibles para tu rol.
                        </div>
                    </div>
                @endunless
            </div>
        </div>
    </section>

    @if(! $isestudiante)
        <section>
            <div class="row g-4">
                <div class="col-12 col-xl-4">
                    <div class="admin-card bg-white p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0">Pendientes</h5>
                            <small class="text-secondary">Seguimiento general</small>
                        </div>

                        <div class="d-flex flex-column gap-3">
                            <div class="border rounded-4 p-3">
                                <div class="small text-secondary">Usuarios sin identificación</div>
                                <div class="fs-4 fw-bold">{{ $pending['users_without_document'] }}</div>
                            </div>

                            <div class="border rounded-4 p-3">
                                <div class="small text-secondary">Usuarios sin asignación académica</div>
                                <div class="fs-4 fw-bold">{{ $pending['users_without_assignment'] }}</div>
                            </div>

                            <div class="border rounded-4 p-3">
                                <div class="small text-secondary">Usuarios inactivos</div>
                                <div class="fs-4 fw-bold">{{ $pending['inactive_users'] }}</div>
                            </div>

                            <div class="border rounded-4 p-3">
                                <div class="small text-secondary">Sensores con novedad</div>
                                <div class="fs-4 fw-bold">{{ $pending['sensors_with_issues'] ?? 0 }}</div>
                            </div>

                            <div class="border rounded-4 p-3">
                                <div class="small text-secondary">Diarios pendientes por revisar</div>
                                <div class="fs-4 fw-bold">{{ $pending['field_diary_pending_review'] ?? 0 }}</div>
                            </div>

                            <div class="border rounded-4 p-3">
                                <div class="small text-secondary">Eventos ambientales de hoy</div>
                                <div class="fs-4 fw-bold">{{ $pending['today_environmental_events'] ?? 0 }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-8">
                    <div class="admin-card bg-white p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0">Usuarios recientes</h5>
                            <small class="text-secondary">Últimos 5 registros</small>
                        </div>

                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Documento</th>
                                        <th>Correo</th>
                                        <th>Asignación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentUsers as $user)
                                        <tr>
                                            <td>{{ $user->name }}</td>
                                            <td>{{ $user->document_type ?: '—' }} {{ $user->document_number ?: '' }}</td>
                                            <td>{{ $user->email }}</td>
                                            <td class="small text-secondary">
                                                {{ $user->school?->name ?: '—' }} /
                                                {{ $user->grade?->label ?: $user->grade?->name ?: '—' }} /
                                                {{ $user->course?->label ?: $user->course?->name ?: '—' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-4">No hay usuarios recientes.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <section>
        <div class="row g-4">
            <div class="col-12 col-xl-6">
                <div class="admin-card bg-white p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Registros físicos recientes</h5>
                        <small class="text-secondary">Últimas capturas</small>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Usuario</th>
                                    <th>Estación / Origen</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentRecords as $record)
                                    <tr>
                                        <td>{{ optional($record->recorded_at)->format('Y-m-d H:i') }}</td>
                                        <td>{{ $record->user?->name ?: '—' }}</td>
                                        <td class="small text-secondary">
                                            <div>
                                                {{ $record->weatherStation?->name ?: 'Sin estación' }}
                                            </div>
                                            <div>
                                                Origen: {{ $sourceTypes[$record->source_type ?? 'manual'] ?? 'Manual' }}
                                            </div>
                                            <div>
                                                {{ $record->school?->name ?: '—' }} /
                                                {{ $record->grade?->label ?: $record->grade?->name ?: '—' }} /
                                                {{ $record->course?->label ?: $record->course?->name ?: '—' }}
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4">No hay registros físicos recientes.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="admin-card bg-white p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Guías recientes</h5>
                        <small class="text-secondary">Últimos PDFs cargados</small>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Título</th>
                                    <th>Destino</th>
                                    <th>Autor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentGuides as $guide)
                                    <tr>
                                        <td>{{ $guide->title }}</td>
                                        <td class="small text-secondary">
                                            {{ $guide->school?->name ?: '—' }} /
                                            {{ $guide->grade?->label ?: $guide->grade?->name ?: 'Todos' }} /
                                            {{ $guide->course?->label ?: $guide->course?->name ?: 'Todos' }}
                                        </td>
                                        <td>{{ $guide->creator?->name ?: '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4">No hay guías recientes.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section>
        <div class="row g-4">
            <div class="col-12 col-xl-6">
                <div class="admin-card bg-white p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">
                            @if($isestudiante)
                                Mis entregas de Diario de Campo
                            @else
                                Entregas recientes de Diario de Campo
                            @endif
                        </h5>
                        <small class="text-secondary">Últimas respuestas</small>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Actividad</th>
                                    <th>Estudiante</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentFieldDiarySubmissions as $submission)
                                    @php
                                        $statusClass = match($submission->status) {
                                            'borrador' => 'text-bg-warning',
                                            'enviado' => 'text-bg-primary',
                                            'revisado' => 'text-bg-success',
                                            'devuelto' => 'text-bg-danger',
                                            default => 'text-bg-secondary',
                                        };
                                    @endphp

                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $submission->activity?->title ?? '—' }}</div>
                                            <small class="text-secondary">
                                                {{ $submission->submitted_at?->format('Y-m-d H:i') ?? 'Sin envío' }}
                                            </small>
                                        </td>
                                        <td>{{ $submission->student?->name ?? '—' }}</td>
                                        <td>
                                            <span class="badge rounded-pill {{ $statusClass }}">
                                                {{ $submission->status_label }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4">No hay entregas recientes.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="admin-card bg-white p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Eventos ambientales próximos</h5>
                        <small class="text-secondary">Calendario institucional</small>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Evento</th>
                                    <th>Fecha</th>
                                    <th>Colegio</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($upcomingEnvironmentalEvents as $event)
                                    <tr>
                                        <td>{{ $event->title }}</td>
                                        <td>
                                            {{ $event->starts_at?->format('d/m/Y') }}
                                            @if($event->ends_at && !$event->starts_at->isSameDay($event->ends_at))
                                                - {{ $event->ends_at->format('d/m/Y') }}
                                            @endif
                                        </td>
                                        <td class="small text-secondary">
                                            {{ $event->school?->name ?? '—' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4">No hay eventos próximos.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

</div>
@endsection