@extends('layouts.app')

@section('title', 'Detalle de sensor')

@section('content')
    <section class="admin-hero p-4 p-md-5">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-lg-8">
                <div class="text-uppercase small fw-semibold admin-hero-subtitle mb-2">
                    Administración
                </div>
                <h1 class="display-6 fw-bold mb-2">{{ $sensor->name }}</h1>
                <p class="mb-0 admin-hero-subtitle">
                    Consulta la información técnica, variable asociada y mantenimiento del sensor.
                </p>
            </div>

            <div class="col-12 col-lg-4 text-lg-end">
                <a href="{{ route('admin.sensors.edit', $sensor) }}"
                   class="btn text-white rounded-4 px-4 py-3 fw-semibold me-2"
                   style="background-color: var(--school-primary);">
                    Editar sensor
                </a>

                <a href="{{ route('admin.sensors.index') }}"
                   class="btn btn-light rounded-4 px-4 py-3 fw-semibold">
                    Volver
                </a>
            </div>
        </div>
    </section>

    <section class="admin-card bg-white p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
            <div>
                <h2 class="h5 fw-bold mb-1">Información general</h2>
                <p class="text-muted mb-0">
                    Datos de identificación y asociación del sensor.
                </p>
            </div>

            <div class="d-flex flex-wrap gap-2">
                @php
                    $statusClass = match($sensor->status) {
                        'operativo' => 'text-bg-success',
                        'mantenimiento' => 'text-bg-warning',
                        'fallando' => 'text-bg-danger',
                        'inactivo' => 'text-bg-secondary',
                        'retirado' => 'text-bg-dark',
                        default => 'text-bg-light',
                    };
                @endphp

                <span class="badge rounded-pill {{ $statusClass }} px-3 py-2">
                    {{ $sensor->status_label }}
                </span>

                @if($sensor->is_active)
                    <span class="badge rounded-pill text-bg-success px-3 py-2">Activo</span>
                @else
                    <span class="badge rounded-pill text-bg-secondary px-3 py-2">Inactivo</span>
                @endif
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-lg-6">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Nombre</div>
                    <div class="fs-5 fw-semibold">{{ $sensor->name }}</div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Código</div>
                    <div class="fs-5 fw-semibold">{{ $sensor->code }}</div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Estación meteorológica</div>
                    <div class="fs-5 fw-semibold">
                        {{ $sensor->weatherStation?->name ?? 'Sin estación' }}
                    </div>
                    <div class="text-muted small mt-1">
                        {{ $sensor->weatherStation?->school?->name ?? 'Sin colegio' }}
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Variable física</div>
                    <div class="fs-5 fw-semibold">
                        {{ $sensor->variable?->name ?? 'Sin variable asociada' }}
                    </div>

                    @if($sensor->variable)
                        <div class="text-muted small mt-1">
                            {{ $sensor->variable->category?->name ?? 'Sin categoría' }}
                            @if($sensor->variable->unit)
                                · {{ $sensor->variable->unit }}
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="admin-card bg-white p-4">
        <div class="mb-4">
            <h2 class="h5 fw-bold mb-1">Información técnica</h2>
            <p class="text-muted mb-0">
                Datos del fabricante, medición y precisión del sensor.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-12 col-md-4">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Marca</div>
                    <div class="fs-5 fw-semibold">{{ $sensor->brand ?? 'No registrada' }}</div>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Modelo</div>
                    <div class="fs-5 fw-semibold">{{ $sensor->model ?? 'No registrado' }}</div>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Número de serie</div>
                    <div class="fs-5 fw-semibold">{{ $sensor->serial_number ?? 'No registrado' }}</div>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Unidad de medida</div>
                    <div class="fs-5 fw-semibold">{{ $sensor->measurement_unit ?? 'No registrada' }}</div>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Rango de medición</div>
                    <div class="fs-5 fw-semibold">{{ $sensor->measurement_range ?? 'No registrado' }}</div>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Precisión</div>
                    <div class="fs-5 fw-semibold">{{ $sensor->accuracy ?? 'No registrada' }}</div>
                </div>
            </div>
        </div>
    </section>

    <section class="admin-card bg-white p-4">
        <div class="mb-4">
            <h2 class="h5 fw-bold mb-1">Mantenimiento</h2>
            <p class="text-muted mb-0">
                Fechas de instalación y seguimiento técnico del sensor.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-12 col-md-4">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Fecha de instalación</div>
                    <div class="fs-5 fw-semibold">
                        {{ $sensor->installation_date?->format('d/m/Y') ?? 'No registrada' }}
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Último mantenimiento</div>
                    <div class="fs-5 fw-semibold">
                        {{ $sensor->last_maintenance_date?->format('d/m/Y') ?? 'No registrado' }}
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="border rounded-4 p-3 h-100">
                    <div class="text-muted small fw-semibold mb-1">Próximo mantenimiento</div>
                    <div class="fs-5 fw-semibold">
                        {{ $sensor->next_maintenance_date?->format('d/m/Y') ?? 'No programado' }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="admin-card bg-white p-4">
        <div class="mb-3">
            <h2 class="h5 fw-bold mb-1">Observaciones</h2>
            <p class="text-muted mb-0">
                Información complementaria del estado, instalación o funcionamiento del sensor.
            </p>
        </div>

        <div class="border rounded-4 p-4">
            @if($sensor->observations)
                <p class="mb-0">{{ $sensor->observations }}</p>
            @else
                <p class="text-muted mb-0">No se han registrado observaciones para este sensor.</p>
            @endif
        </div>
    </section>
@endsection