@extends('layouts.app')

@section('content')
<div class="d-flex flex-column gap-4">
    <section class="admin-hero p-4 p-md-5">
        <h1 class="h2 fw-bold mb-2">Guías de laboratorio</h1>
        <p class="mb-0 text-light-emphasis">Consulta, visualiza y descarga las guías disponibles para tu grupo.</p>
    </section>

    <div class="row g-4">
        @forelse($guides as $guide)
            <div class="col-12 col-md-6 col-xl-4">
                <div class="admin-card bg-white p-4 h-100 d-flex flex-column">
                    <h5 class="fw-bold">{{ $guide->title }}</h5>

                    <p class="text-secondary small flex-grow-1">
                        {{ $guide->description ?: 'Sin descripción.' }}
                    </p>

                    <div class="small text-secondary mb-3">
                        Publicado: {{ optional($guide->published_at)->format('Y-m-d') }}
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <button
                            type="button"
                            class="btn btn-outline-primary rounded-4"
                            data-bs-toggle="modal"
                            data-bs-target="#guideModal{{ $guide->id }}">
                            Ver PDF
                        </button>

                        <a href="{{ route('student.laboratory-guides.download', $guide) }}"
                           class="btn btn-dark rounded-4">
                            Descargar PDF
                        </a>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="guideModal{{ $guide->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content rounded-4 overflow-hidden">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ $guide->title }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>

                        <div class="modal-body p-0" style="height: 75vh;">
                            <iframe
                                src="{{ asset('storage/' . $guide->pdf_path) }}"
                                width="100%"
                                height="100%"
                                style="border: 0;"
                                title="PDF de {{ $guide->title }}">
                            </iframe>
                        </div>

                        <div class="modal-footer">
                            <a href="{{ asset('storage/' . $guide->pdf_path) }}"
                               target="_blank"
                               class="btn btn-outline-secondary rounded-4">
                                Abrir en otra pestaña
                            </a>

                            <a href="{{ route('student.laboratory-guides.download', $guide) }}"
                               class="btn btn-dark rounded-4">
                                Descargar PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="admin-card bg-white p-5 text-center">
                    No hay guías de laboratorio disponibles.
                </div>
            </div>
        @endforelse
    </div>

    @if($guides->hasPages())
        <div>
            {{ $guides->links() }}
        </div>
    @endif
</div>
@endsection