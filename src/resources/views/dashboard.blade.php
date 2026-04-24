@extends('layouts.app')

@section('content')
<div class="d-flex flex-column gap-4">

    <section class="admin-hero p-4 p-md-5">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-lg-8">
                <div class="text-uppercase small fw-semibold text-light-emphasis mb-2">
                    Panel principal
                </div>
                <h1 class="display-6 fw-bold mb-2">Dashboard EcoData</h1>
                <p class="mb-0 text-light-emphasis">
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
                    <p class="text-secondary small mb-2">Variables físicas activas</p>
                    <h3 class="fw-bold mb-0">{{ $stats['physical_variables'] }}</h3>
                    <small class="text-secondary">Parametrizadas en el sistema</small>
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
                    <p class="text-secondary small mb-2">Guías de laboratorio</p>
                    <h3 class="fw-bold mb-0">{{ $stats['laboratory_guides'] }}</h3>
                    <small class="text-secondary">Activas y disponibles</small>
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
                <div class="col-12 col-md-6 col-xl-3">
                    <a href="{{ route('admin.users.create') }}" class="btn btn-outline-dark rounded-4 w-100 py-3">
                        Nuevo usuario
                    </a>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <a href="{{ route('admin.physical-variable-records.create') }}" class="btn btn-outline-dark rounded-4 w-100 py-3">
                        Nuevo registro físico
                    </a>
                </div>

                @if (Route::has('admin.laboratory-guides.create'))
                    <div class="col-12 col-md-6 col-xl-3">
                        <a href="{{ route('admin.laboratory-guides.create') }}" class="btn btn-outline-dark rounded-4 w-100 py-3">
                            Nueva guía de laboratorio
                        </a>
                    </div>
                @endif

                <div class="col-12 col-md-6 col-xl-3">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-dark rounded-4 w-100 py-3">
                        Gestionar usuarios
                    </a>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <a href="{{ route('admin.users.import') }}" class="btn btn-outline-dark rounded-4 w-100 py-3">
                        Cargue masivo Excel
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section>
        <div class="row g-4">
            <div class="col-12 col-xl-4">
                <div class="admin-card bg-white p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Pendientes</h5>
                        <small class="text-secondary">Calidad de datos</small>
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
                                    <th>Contexto</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentRecords as $record)
                                    <tr>
                                        <td>{{ optional($record->recorded_at)->format('Y-m-d H:i') }}</td>
                                        <td>{{ $record->user?->name ?: '—' }}</td>
                                        <td class="small text-secondary">
                                            {{ $record->school?->name ?: '—' }} /
                                            {{ $record->grade?->label ?: $record->grade?->name ?: '—' }} /
                                            {{ $record->course?->label ?: $record->course?->name ?: '—' }}
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

</div>
@endsection