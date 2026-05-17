@php
    $authUser = auth()->user();

    $currentSchool = $currentSchool
        ?? $authUser?->loadMissing('school')->school
        ?? null;

    $schoolPrimary = $currentSchool?->primary_color ?: '#22c55e';
    $schoolSecondary = $currentSchool?->secondary_color ?: '#0f172a';
    $schoolAccent = $currentSchool?->accent_color ?: '#86efac';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('img/favicon.ico') }}?v=3">
    <title>{{ config('app.name', 'Laravel') }}</title>

    <style>
        :root {
            --school-primary: {{ $schoolPrimary }};
            --school-secondary: {{ $schoolSecondary }};
            --school-accent: {{ $schoolAccent }};

            --ecodata-primary: var(--school-primary);
            --ecodata-secondary: var(--school-secondary);
            --ecodata-accent: var(--school-accent);

            --ecodata-bg: #f4f6fb;
            --ecodata-card: #ffffff;
            --ecodata-text: #1f2937;
            --ecodata-muted: #6b7280;
            --ecodata-border: rgba(15, 23, 42, 0.10);
            --ecodata-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        }

        html,
        body {
            min-height: 100%;
            background-color: var(--ecodata-bg);
            color: var(--ecodata-text);
        }

        body {
            font-family: "Inter", "Segoe UI", system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
        }

        a {
            color: var(--school-primary);
        }

        a:hover {
            color: var(--school-secondary);
        }

        /*
        |--------------------------------------------------------------------------
        | Layout general
        |--------------------------------------------------------------------------
        */

        .admin-shell {
            min-height: 100vh;
            background:
                radial-gradient(circle at top left, rgba(255, 255, 255, 0.95), transparent 28rem),
                linear-gradient(135deg, rgba(244, 246, 251, 0.98), rgba(236, 240, 248, 0.98));
        }

        .admin-sidebar-col {
            background: linear-gradient(
                180deg,
                var(--school-secondary) 0%,
                var(--school-primary) 100%
            );
            min-width: 0;
            z-index: 20;
            box-shadow: 12px 0 35px rgba(15, 23, 42, 0.12);
        }

        .admin-main-col {
            min-width: 0;
            background-color: var(--ecodata-bg);
        }

        .admin-content {
            width: 100%;
            max-width: 1480px;
            margin: 0 auto;
        }

        /*
        |--------------------------------------------------------------------------
        | Sidebar / navegación
        |--------------------------------------------------------------------------
        */

        .admin-sidebar-col {
            background: linear-gradient(
                180deg,
                var(--school-primary) 0%,
                var(--school-secondary) 100%
            ) !important;
            min-width: 0;
            z-index: 20;
            box-shadow: 12px 0 35px rgba(15, 23, 42, 0.12);
        }

        .admin-sidebar {
            min-height: 100vh;
            max-height: 100vh;
            overflow-y: auto;
            background: transparent !important;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.35) transparent;
        }

        .admin-sidebar::-webkit-scrollbar {
            width: 8px;
        }

        .admin-sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .admin-sidebar::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.25);
            border-radius: 999px;
        }

        .admin-sidebar .nav-link {
            color: rgba(255, 255, 255, 0.88) !important;
            border-radius: 1rem;
            padding: 0.78rem 1rem;
            font-weight: 600;
            background-color: transparent !important;
            border: 0 !important;
            transition:
                background-color 0.18s ease,
                color 0.18s ease,
                transform 0.18s ease;
        }

        .admin-sidebar .nav-link:hover {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.12) !important;
            transform: translateX(2px);
        }

        .admin-sidebar .nav-link.active {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.20) !important;
            box-shadow: inset 4px 0 0 var(--school-accent);
        }

        .admin-sidebar .nav-pills .nav-link.active {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.20) !important;
        }

        .admin-sidebar button.nav-link {
            width: 100%;
            text-align: left;
        }

        .admin-sidebar .collapse .nav-link {
            padding-left: 1rem;
            font-size: 0.95rem;
        }

        .admin-sidebar hr {
            border-color: rgba(255, 255, 255, 0.18);
            opacity: 1;
        }

        .admin-sidebar .badge {
            background-color: rgba(255, 255, 255, 0.18) !important;
            color: #ffffff !important;
            border: 1px solid rgba(255, 255, 255, 0.20);
        }

        .school-avatar {
            width: 48px;
            height: 48px;
            min-width: 48px;
            border-radius: 1.1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background-color: rgba(255, 255, 255, 0.16) !important;
            color: #ffffff;
            font-weight: 800;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.18);
        }

        .school-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /*
        |--------------------------------------------------------------------------
        | Hero / banners
        |--------------------------------------------------------------------------
        */

        .admin-hero {
            background:
                radial-gradient(circle at top right, rgba(255, 255, 255, 0.28), transparent 22rem),
                linear-gradient(
                    135deg,
                    var(--school-primary) 0%,
                    var(--school-secondary) 100%
                ) !important;
            color: #ffffff;
            border-radius: 1.75rem;
            box-shadow: var(--ecodata-shadow);
            position: relative;
            overflow: hidden;
        }

        .admin-hero::after {
            content: "";
            position: absolute;
            right: -80px;
            bottom: -80px;
            width: 220px;
            height: 220px;
            border-radius: 999px;
            background-color: rgba(255, 255, 255, 0.10);
            pointer-events: none;
        }

        .admin-hero h1,
        .admin-hero h2,
        .admin-hero h3,
        .admin-hero p,
        .admin-hero small {
            color: inherit;
        }

        .admin-hero .text-muted,
        .admin-hero .text-secondary {
            color: rgba(255, 255, 255, 0.78) !important;
        }

        /*
        |--------------------------------------------------------------------------
        | Cards / contenedores
        |--------------------------------------------------------------------------
        */

        .admin-card {
            background-color: var(--ecodata-card);
            border: 1px solid var(--ecodata-border);
            border-radius: 1.5rem;
            box-shadow: var(--ecodata-shadow);
        }

        .admin-card-soft {
            background-color: rgba(255, 255, 255, 0.74);
            border: 1px solid var(--ecodata-border);
            border-radius: 1.5rem;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.05);
        }

        .stat-card {
            background-color: var(--ecodata-card);
            border: 1px solid var(--ecodata-border);
            border-radius: 1.35rem;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
            transition:
                transform 0.18s ease,
                box-shadow 0.18s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 38px rgba(15, 23, 42, 0.10);
        }

        .stat-icon,
        .admin-icon {
            width: 44px;
            height: 44px;
            border-radius: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: color-mix(in srgb, var(--school-primary) 13%, white);
            color: var(--school-primary);
            font-weight: 800;
        }

        /*
        |--------------------------------------------------------------------------
        | Botones institucionales
        |--------------------------------------------------------------------------
        */

        .btn-school-primary,
        .btn-ecodata-primary {
            background-color: var(--school-primary) !important;
            border-color: var(--school-primary) !important;
            color: #ffffff !important;
        }

        .btn-school-primary:hover,
        .btn-school-primary:focus,
        .btn-ecodata-primary:hover,
        .btn-ecodata-primary:focus {
            background-color: var(--school-secondary) !important;
            border-color: var(--school-secondary) !important;
            color: #ffffff !important;
        }

        .btn-school-secondary,
        .btn-ecodata-secondary {
            background-color: var(--school-secondary) !important;
            border-color: var(--school-secondary) !important;
            color: #ffffff !important;
        }

        .btn-school-secondary:hover,
        .btn-school-secondary:focus,
        .btn-ecodata-secondary:hover,
        .btn-ecodata-secondary:focus {
            background-color: var(--school-primary) !important;
            border-color: var(--school-primary) !important;
            color: #ffffff !important;
        }

        .btn-school-accent,
        .btn-ecodata-accent {
            background-color: var(--school-accent) !important;
            border-color: var(--school-accent) !important;
            color: #ffffff !important;
        }

        .btn-outline-school-primary {
            border-color: var(--school-primary) !important;
            color: var(--school-primary) !important;
        }

        .btn-outline-school-primary:hover,
        .btn-outline-school-primary:focus {
            background-color: var(--school-primary) !important;
            color: #ffffff !important;
        }

        .btn-outline-school-secondary {
            border-color: var(--school-secondary) !important;
            color: var(--school-secondary) !important;
        }

        .btn-outline-school-secondary:hover,
        .btn-outline-school-secondary:focus {
            background-color: var(--school-secondary) !important;
            color: #ffffff !important;
        }

        /*
        |--------------------------------------------------------------------------
        | Utilidades de color
        |--------------------------------------------------------------------------
        */

        .bg-school-primary {
            background-color: var(--school-primary) !important;
            color: #ffffff !important;
        }

        .bg-school-secondary {
            background-color: var(--school-secondary) !important;
            color: #ffffff !important;
        }

        .bg-school-accent {
            background-color: var(--school-accent) !important;
            color: #ffffff !important;
        }

        .text-school-primary {
            color: var(--school-primary) !important;
        }

        .text-school-secondary {
            color: var(--school-secondary) !important;
        }

        .text-school-accent {
            color: var(--school-accent) !important;
        }

        .border-school-primary {
            border-color: var(--school-primary) !important;
        }

        .border-school-secondary {
            border-color: var(--school-secondary) !important;
        }

        .border-school-accent {
            border-color: var(--school-accent) !important;
        }

        .badge-school-primary {
            background-color: color-mix(in srgb, var(--school-primary) 14%, white);
            color: var(--school-primary);
            border: 1px solid color-mix(in srgb, var(--school-primary) 22%, white);
        }

        .badge-school-secondary {
            background-color: color-mix(in srgb, var(--school-secondary) 14%, white);
            color: var(--school-secondary);
            border: 1px solid color-mix(in srgb, var(--school-secondary) 22%, white);
        }

        .badge-school-accent {
            background-color: color-mix(in srgb, var(--school-accent) 14%, white);
            color: var(--school-accent);
            border: 1px solid color-mix(in srgb, var(--school-accent) 22%, white);
        }

        /*
        |--------------------------------------------------------------------------
        | Formularios
        |--------------------------------------------------------------------------
        */

        .form-control:focus,
        .form-select:focus,
        .form-check-input:focus {
            border-color: var(--school-primary);
            box-shadow: 0 0 0 0.25rem color-mix(in srgb, var(--school-primary) 22%, transparent);
        }

        .form-check-input:checked {
            background-color: var(--school-primary);
            border-color: var(--school-primary);
        }

        .form-control-lg,
        .form-select-lg {
            border-radius: 1rem;
        }

        /*
        |--------------------------------------------------------------------------
        | Tablas / DataTables
        |--------------------------------------------------------------------------
        */

        .table {
            --bs-table-hover-bg: color-mix(in srgb, var(--school-primary) 5%, white);
        }

        .table thead th {
            color: #334155;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            background-color: #f8fafc;
            border-bottom: 1px solid var(--ecodata-border);
        }

        .table td,
        .table th {
            vertical-align: middle;
        }

        .dataTables_wrapper .page-link,
        .dt-container .page-link {
            color: var(--school-primary);
            border-radius: 0.7rem;
            margin: 0 0.12rem;
        }

        .dataTables_wrapper .page-item.active .page-link,
        .dt-container .page-item.active .page-link {
            background-color: var(--school-primary);
            border-color: var(--school-primary);
            color: #ffffff;
        }

        .dataTables_wrapper .form-control:focus,
        .dataTables_wrapper .form-select:focus,
        .dt-container .form-control:focus,
        .dt-container .form-select:focus {
            border-color: var(--school-primary);
            box-shadow: 0 0 0 0.25rem color-mix(in srgb, var(--school-primary) 20%, transparent);
        }

        /*
        |--------------------------------------------------------------------------
        | Alertas / SweetAlert
        |--------------------------------------------------------------------------
        */

        .swal2-popup {
            border-radius: 1.5rem !important;
        }

        .swal2-confirm,
        .swal2-cancel {
            border-radius: 1rem !important;
            font-weight: 700 !important;
            padding-left: 1.35rem !important;
            padding-right: 1.35rem !important;
        }

        /*
        |--------------------------------------------------------------------------
        | Modales
        |--------------------------------------------------------------------------
        */

        .modal-content {
            border-radius: 1.5rem;
            border: 0;
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.20);
        }

        .modal-header,
        .modal-footer {
            border-color: var(--ecodata-border);
        }

        /*
        |--------------------------------------------------------------------------
        | Dropdowns
        |--------------------------------------------------------------------------
        */

        .dropdown-menu {
            border-radius: 1rem;
            border: 1px solid var(--ecodata-border);
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12);
        }

        .dropdown-item.active,
        .dropdown-item:active {
            background-color: var(--school-primary);
            color: #ffffff;
        }

        /*
        |--------------------------------------------------------------------------
        | Links especiales
        |--------------------------------------------------------------------------
        */

        .link-school {
            color: var(--school-primary);
            font-weight: 700;
            text-decoration: none;
        }

        .link-school:hover {
            color: var(--school-secondary);
            text-decoration: underline;
        }

        /*
        |--------------------------------------------------------------------------
        | Responsive
        |--------------------------------------------------------------------------
        */

        @media (max-width: 991.98px) {
            .admin-sidebar {
                min-height: auto;
                max-height: none;
            }

            .admin-sidebar-col {
                box-shadow: none;
            }

            .admin-content {
                max-width: 100%;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback para navegadores sin color-mix
        |--------------------------------------------------------------------------
        */

        @supports not (background-color: color-mix(in srgb, red 10%, white)) {
            .stat-icon,
            .admin-icon {
                background-color: rgba(29, 78, 216, 0.10);
            }

            .badge-school-primary,
            .badge-school-secondary,
            .badge-school-accent {
                background-color: #eef2ff;
            }

            .table {
                --bs-table-hover-bg: #f8fafc;
            }
        }
    </style>

    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.datatables.net/2.2.2/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.4/css/responsive.bootstrap5.min.css">
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
                                    <form method="POST"
                                        action="{{ route('environmental-events.acknowledge', $todayEnvironmentalEvent) }}">
                                        @csrf

                                        <input type="hidden" name="redirect_to" value="show">

                                        <button type="submit"
                                                class="btn btn-outline-dark rounded-4 px-4 py-2">
                                            Ver detalles
                                        </button>
                                    </form>

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

@if (session('warning'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'warning',
                title: 'Advertencia',
                text: @json(session('warning')),
                confirmButtonText: 'Entendido',
                confirmButtonColor: getComputedStyle(document.documentElement)
                    .getPropertyValue('--school-primary')
                    .trim(),
                customClass: {
                    popup: 'rounded-4',
                    confirmButton: 'rounded-4 px-4 fw-semibold'
                }
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

    document.querySelectorAll('.js-confirm-delete').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            const title = form.dataset.title || '¿Confirmar eliminación?';
            const text = form.dataset.text || 'Esta acción no se puede deshacer.';
            const confirmButtonText = form.dataset.confirmButton || 'Sí, eliminar';

            Swal.fire({
                icon: 'warning',
                title: title,
                text: text,
                showCancelButton: true,
                confirmButtonText: confirmButtonText,
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: getComputedStyle(document.documentElement)
                    .getPropertyValue('--school-primary')
                    .trim() || '#6c757d',
                reverseButtons: true,
                customClass: {
                    popup: 'rounded-4',
                    confirmButton: 'rounded-4 px-4 fw-semibold',
                    cancelButton: 'rounded-4 px-4 fw-semibold'
                }
            }).then(function (result) {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
});

 function ecodataDisableSubmitForm(form, loadingText = 'Procesando...') {
        if (!form || form.dataset.submitted === 'true') {
            return false;
        }

        form.dataset.submitted = 'true';

        form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (button) {
            button.disabled = true;

            if (button.tagName === 'BUTTON') {
                button.dataset.originalHtml = button.innerHTML;
                button.innerHTML = `
                    <span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
                    ${loadingText}
                `;
            } else {
                button.dataset.originalValue = button.value;
                button.value = loadingText;
            }
        });

        return true;
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form').forEach(function (form) {
            if (form.classList.contains('js-confirm-delete')) {
                return;
            }

            form.addEventListener('submit', function (event) {
                if (form.dataset.submitted === 'true') {
                    event.preventDefault();
                    return false;
                }

                ecodataDisableSubmitForm(form);
            });
        });
    });
</script>