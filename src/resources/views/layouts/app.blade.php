<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('img/favicon.ico') }}?v=3">
    <title>{{ config('app.name', 'Laravel') }}</title>

    @if($currentSchool)
        <style>
            :root {
                --school-primary: {{ $currentSchool->primary_color ?? '#c93a7b' }};
                --school-secondary: {{ $currentSchool->secondary_color ?? '#2f3b52' }};
                --school-accent: {{ $currentSchool->accent_color ?? '#6366f1' }};
            }
        </style>
    @else
        <style>
            :root {
                --school-primary: #c93a7b;
                --school-secondary: #2f3b52;
                --school-accent: #6366f1;
            }
        </style>
    @endif

    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.datatables.net/2.2.2/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.4/css/responsive.bootstrap5.min.css">
    <style>
        html,
        body {
            min-height: 100%;
            overflow-x: hidden;
        }

        .admin-shell {
            min-height: 100vh;
            overflow-x: hidden;
        }

        .admin-layout {
            min-height: 100vh;
            align-items: stretch;
        }

        .admin-sidebar-col {
            background: var(--school-secondary);
            min-width: 0;
            z-index: 20;
        }

        .admin-sidebar {
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, .35) transparent;
        }

        .admin-sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .admin-sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .admin-sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, .35);
            border-radius: 999px;
        }

        .admin-sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, .55);
        }

        .admin-content-col {
            min-width: 0;
            overflow-x: hidden;
        }

        .admin-main {
            min-width: 0;
            width: 100%;
        }
@media (min-width: 992px) {
    .admin-sidebar {
        min-height: 100vh;
        height: 100%;
        max-height: none;
        overflow-y: visible;
    }
}
        @media (max-width: 992px) {
            .admin-shell,
            .admin-layout {
                min-height: auto;
            }

            .admin-sidebar-col {
                position: sticky;
                top: 0;
                z-index: 1030;
            }

            .admin-sidebar {
                min-height: auto;
                height: auto;
                max-height: 100vh;
                overflow-y: auto;
            }

            .admin-main {
                padding-top: 1rem !important;
            }

            #adminSidebarMenu.show {
                max-height: calc(100vh - 5rem);
                overflow-y: auto;
                padding-bottom: 1rem;
            }
        }
        .pagination {
            margin-bottom: 0;
            gap: .25rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .page-item .page-link {
            border-radius: .75rem;
            border: 1px solid rgba(15, 23, 42, .12);
            color: var(--school-secondary);
            min-width: 2.35rem;
            min-height: 2.35rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .page-item.active .page-link {
            background: var(--school-secondary);
            border-color: var(--school-secondary);
            color: #fff;
        }

        .page-item.disabled .page-link {
            color: #94a3b8;
            background: #f8fafc;
        }

        .page-link:hover {
            color: #fff;
            background: var(--school-primary);
            border-color: var(--school-primary);
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="container-fluid admin-shell px-0">
        <div class="row g-0 admin-layout">
            <aside class="col-12 col-lg-3 col-xl-2 admin-sidebar-col">
                @include('components.layout.navigation')
            </aside>

            <div class="col-12 col-lg-9 col-xl-10 admin-content-col">
                <main class="admin-main p-3 p-md-4 p-xl-5">
                    @yield('content')
                </main>
            </div>
        </div>
    </div>
    @if(isset($todayEnvironmentalEvent) && $todayEnvironmentalEvent)
        <div class="modal fade"
            id="environmentalEventModal"
            tabindex="-1"
            aria-labelledby="environmentalEventModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content rounded-4 border-0 overflow-hidden">
                    <div class="row g-0">
                        <div class="col-12 col-lg-5">
                            @if($todayEnvironmentalEvent->image_path)
                                <img src="{{ asset('storage/' . $todayEnvironmentalEvent->image_path) }}"
                                    alt="{{ $todayEnvironmentalEvent->title }}"
                                    class="w-100 h-100"
                                    style="object-fit: cover; min-height: 360px;">
                            @else
                                <div class="bg-light h-100 d-flex align-items-center justify-content-center"
                                    style="min-height: 360px;">
                                    <div class="text-center p-4">
                                        <div class="display-3 mb-3">🌎</div>
                                        <div class="fw-bold text-muted">Calendario ambiental</div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="col-12 col-lg-7">
                            <div class="modal-body p-4 p-md-5">
                                <div class="text-uppercase small fw-semibold text-muted mb-2">
                                    Calendario ambiental
                                </div>

                                <h2 class="fw-bold mb-3" id="environmentalEventModalLabel">
                                    {{ $todayEnvironmentalEvent->title }}
                                </h2>

                                <div class="text-muted fw-semibold mb-3">
                                    {{ $todayEnvironmentalEvent->starts_at?->format('d/m/Y') }}
                                    @if($todayEnvironmentalEvent->ends_at && !$todayEnvironmentalEvent->starts_at->isSameDay($todayEnvironmentalEvent->ends_at))
                                        - {{ $todayEnvironmentalEvent->ends_at->format('d/m/Y') }}
                                    @endif
                                </div>

                                @if($todayEnvironmentalEvent->description)
                                    <p class="text-muted mb-4" style="font-size: 1.05rem;">
                                        {{ $todayEnvironmentalEvent->description }}
                                    </p>
                                @else
                                    <p class="text-muted mb-4">
                                        Hoy hay un evento ambiental programado por tu institución.
                                    </p>
                                @endif

                                <div class="d-flex flex-column flex-md-row gap-2 justify-content-end">
                                    <a href="{{ route('environmental-events.show', $todayEnvironmentalEvent) }}"
                                    class="btn btn-outline-dark rounded-4 px-4 py-2">
                                        Ver detalles
                                    </a>

                                    <form method="POST"
                                        action="{{ route('environmental-events.acknowledge', $todayEnvironmentalEvent) }}">
                                        @csrf

                                        <button type="submit"
                                                class="btn text-white rounded-4 px-4 py-2 fw-semibold"
                                                style="background-color: var(--school-primary);">
                                            Aceptar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const modalElement = document.getElementById('environmentalEventModal');

                    if (!modalElement) {
                        return;
                    }

                    const modal = new bootstrap.Modal(modalElement, {
                        backdrop: 'static',
                        keyboard: false
                    });

                    modal.show();
                });
            </script>
        @endpush
    @endif
</body>
</html>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@if (session('success'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
        icon: 'success',
        title: 'Proceso completado',
        text: @json(session('success')),
        confirmButtonText: 'Aceptar'
    });
});
</script>
@endif

@if ($errors->any())
<script>
document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
        icon: 'error',
        title: 'Hay errores en el formulario',
        html: `{!! collect($errors->all())->map(fn($e) => '<div class="text-start mb-1">• '.e($e).'</div>')->implode('') !!}`,
        confirmButtonText: 'Revisar'
    });
});
</script>
@endif
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="https://cdn.datatables.net/2.2.2/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.2.2/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.4/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.4/js/responsive.bootstrap5.min.js"></script>

@stack('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    function recalculateResponsiveTables() {
        if (!window.jQuery || !$.fn.dataTable) {
            return;
        }

        setTimeout(function () {
            $.fn.dataTable
                .tables({ visible: true, api: true })
                .columns.adjust()
                .responsive.recalc();
        }, 300);
    }

    window.addEventListener('resize', recalculateResponsiveTables);

    document.addEventListener('shown.bs.collapse', recalculateResponsiveTables);
    document.addEventListener('hidden.bs.collapse', recalculateResponsiveTables);

    document.addEventListener('shown.bs.offcanvas', recalculateResponsiveTables);
    document.addEventListener('hidden.bs.offcanvas', recalculateResponsiveTables);

    recalculateResponsiveTables();
});
</script>