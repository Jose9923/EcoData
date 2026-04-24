@extends('layouts.app')

@section('content')
@php
    $gradeLabel = $record->grade?->label ?: $record->grade?->name;
    $courseLabel = $record->course?->label ?: $record->course?->name;
@endphp

<div class="d-flex flex-column gap-4">
    <section class="admin-hero p-4 p-md-5">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <div class="text-uppercase small fw-semibold text-light-emphasis mb-2">
                    Monitoreo
                </div>
                <h1 class="display-6 fw-bold mb-2">Detalle del registro físico</h1>
                <p class="mb-0 text-light-emphasis">
                    Revisa el contexto, los valores capturados y las observaciones del registro.
                </p>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('admin.physical-variable-records.index') }}"
                class="btn btn-outline-light rounded-4 px-4">
                    Volver
                </a>
                <a href="{{ route('admin.physical-variable-records.edit', $record->id) }}"
                class="btn text-white rounded-4 px-4"
                style="background-color: var(--school-primary);">
                    Editar
                </a>
            </div>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <div class="admin-card bg-white p-4 h-100">
                <h5 class="fw-bold mb-3">Contexto del registro</h5>

                <div class="small text-secondary d-flex flex-column gap-2">
                    <div><strong>Colegio:</strong> {{ $record->school?->name ?? '—' }}</div>
                    <div><strong>Grado:</strong> {{ $gradeLabel ?: '—' }}</div>
                    <div><strong>Curso:</strong> {{ $courseLabel ?: '—' }}</div>
                    <div><strong>Usuario:</strong> {{ $record->user?->name ?? '—' }}</div>
                    <div><strong>Correo:</strong> {{ $record->user?->email ?? '—' }}</div>
                    <div><strong>Fecha:</strong> {{ optional($record->recorded_at)->format('d/m/Y H:i') }}</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="admin-card bg-white p-4 h-100">
                <h5 class="fw-bold mb-3">Observaciones</h5>

                @if($record->observations)
                    <div class="text-secondary" style="white-space: pre-line;">
                        {{ $record->observations }}
                    </div>
                @else
                    <div class="text-secondary">Sin observaciones registradas.</div>
                @endif
            </div>
        </div>
    </div>

    <section class="admin-card bg-white p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Valores registrados</h5>
            <small class="text-secondary">{{ $record->values->count() }} variables capturadas</small>
        </div>

        <div class="row g-4">
            @forelse($record->values as $value)
                @php
                    $variable = $value->variable;
                    $resolved = $value->resolved_value;

                    if ($variable?->data_type === 'boolean') {
                        $resolved = $resolved === true ? 'Sí' : ($resolved === false ? 'No' : '—');
                    }

                    if ($variable?->data_type === 'date' && $resolved) {
                        $resolved = \Illuminate\Support\Carbon::parse($resolved)->format('Y-m-d');
                    }

                    if ($resolved !== null && $resolved !== '—' && $variable?->unit) {
                        $resolved .= ' ' . $variable->unit;
                    }
                @endphp

                <div class="col-12 col-md-6 col-xl-4">
                    <div class="border rounded-4 p-3 h-100">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="fw-semibold">{{ $variable?->name ?? 'Variable' }}</div>
                                <div class="small text-secondary">{{ $variable?->category?->name ?? 'Sin categoría' }}</div>
                            </div>
                            <span class="badge text-bg-light rounded-pill">
                                {{ ucfirst($variable?->data_type ?? '—') }}
                            </span>
                        </div>

                        <div class="small text-secondary mb-2">
                            Unidad: {{ $variable?->unit ?: '—' }}
                        </div>

                        <div class="fs-5 fw-bold">
                            {{ $resolved ?? '—' }}
                        </div>

                        @if($variable?->description)
                            <div class="small text-secondary mt-2">
                                {{ $variable->description }}
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="text-secondary">Este registro no tiene valores asociados.</div>
                </div>
            @endforelse
        </div>
    </section>
</div>
@endsection