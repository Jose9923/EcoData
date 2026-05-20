@php
    $authUser = auth()->user();

    $currentSchool = $currentSchool
        ?? $authUser?->loadMissing('school')->school
        ?? null;

    /*
    |--------------------------------------------------------------------------
    | Paleta institucional
    |--------------------------------------------------------------------------
    | Si el usuario tiene colegio asignado, toma los colores configurados.
    | Si no, usa la paleta base EcoData.
    */
    $schoolPrimary = $currentSchool?->primary_color ?: '#22c55e';
    $schoolSecondary = $currentSchool?->secondary_color ?: '#0f172a';
    $schoolAccent = $currentSchool?->accent_color ?: '#86efac';

    /*
    |--------------------------------------------------------------------------
    | Contraste automático
    |--------------------------------------------------------------------------
    | Calcula si sobre un fondo institucional debe ir texto claro u oscuro.
    */
    $contrastText = function (?string $hexColor): string {
        $hexColor = trim((string) $hexColor);

        if (! str_starts_with($hexColor, '#')) {
            $hexColor = '#' . $hexColor;
        }

        $hex = ltrim($hexColor, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return '#ffffff';
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $luminance = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

        return $luminance > 150 ? '#0f172a' : '#ffffff';
    };

    $schoolPrimaryText = $contrastText($schoolPrimary);
    $schoolSecondaryText = $contrastText($schoolSecondary);
    $schoolAccentText = $contrastText($schoolAccent);
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'EcoData') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/scss/app.scss', 'resources/js/app.js'])

    <link rel="stylesheet" href="https://cdn.datatables.net/2.2.2/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.4/css/responsive.bootstrap5.min.css">

    <style>
        :root {
            --school-primary: {{ $schoolPrimary }};
            --school-secondary: {{ $schoolSecondary }};
            --school-accent: {{ $schoolAccent }};

            --school-primary-text: {{ $schoolPrimaryText }};
            --school-secondary-text: {{ $schoolSecondaryText }};
            --school-accent-text: {{ $schoolAccentText }};

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
            overflow-x: hidden;
            background-color: var(--ecodata-bg);
            color: var(--ecodata-text);
        }

        body {
            font-family: "Figtree", "Inter", "Segoe UI", system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
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
            overflow-x: hidden;
            background:
                radial-gradient(circle at top left, rgba(255, 255, 255, 0.95), transparent 28rem),
                linear-gradient(135deg, rgba(244, 246, 251, 0.98), rgba(236, 240, 248, 0.98));
        }

        .admin-layout {
            min-height: 100vh;
            align-items: stretch;
        }

        .admin-sidebar-col {
            background:
                linear-gradient(
                    180deg,
                    rgba(15, 23, 42, 0.28),
                    rgba(15, 23, 42, 0.48)
                ),
                linear-gradient(
                    180deg,
                    var(--school-primary) 0%,
                    var(--school-secondary) 100%
                ) !important;
            min-width: 0;
            z-index: 20;
            box-shadow: 12px 0 35px rgba(15, 23, 42, 0.12);
        }

        .admin-content-col {
            min-width: 0;
            overflow-x: hidden;
            background-color: var(--ecodata-bg);
        }

        .admin-main {
            min-width: 0;
            width: 100%;
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
            transition: background-color 0.18s ease, color 0.18s ease, transform 0.18s ease;
        }

        .admin-sidebar .nav-link:hover {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.12) !important;
            transform: translateX(2px);
        }

        .admin-sidebar .nav-link.active,
        .admin-sidebar .nav-pills .nav-link.active {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.20) !important;
            box-shadow: inset 4px 0 0 var(--school-accent) !important;
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
                linear-gradient(
                    135deg,
                    rgba(15, 23, 42, 0.22),
                    rgba(15, 23, 42, 0.48)
                ),
                radial-gradient(circle at top right, rgba(255, 255, 255, 0.22), transparent 22rem),
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

        .admin-hero > * {
            position: relative;
            z-index: 1;
        }

        .admin-hero h1,
        .admin-hero h2,
        .admin-hero h3,
        .admin-hero h4,
        .admin-hero h5,
        .admin-hero h6 {
            color: #ffffff !important;
        }

        .admin-hero p,
        .admin-hero small,
        .admin-hero .admin-hero-subtitle {
            color: rgba(255, 255, 255, 0.86) !important;
        }

        .admin-hero .text-muted,
        .admin-hero .text-secondary,
        .admin-hero .text-light-emphasis,
        .admin-hero .text-dark,
        .admin-hero .text-body,
        .admin-hero .text-body-secondary {
            color: rgba(255, 255, 255, 0.86) !important;
        }

        /*
        |--------------------------------------------------------------------------
        | Cards
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
            transition: transform 0.18s ease, box-shadow 0.18s ease;
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
            color: var(--school-primary-text) !important;
        }

        .btn-school-primary:hover,
        .btn-school-primary:focus,
        .btn-ecodata-primary:hover,
        .btn-ecodata-primary:focus {
            background-color: var(--school-secondary) !important;
            border-color: var(--school-secondary) !important;
            color: var(--school-secondary-text) !important;
        }

        .btn-school-secondary,
        .btn-ecodata-secondary {
            background-color: var(--school-secondary) !important;
            border-color: var(--school-secondary) !important;
            color: var(--school-secondary-text) !important;
        }

        .btn-school-secondary:hover,
        .btn-school-secondary:focus,
        .btn-ecodata-secondary:hover,
        .btn-ecodata-secondary:focus {
            background-color: var(--school-primary) !important;
            border-color: var(--school-primary) !important;
            color: var(--school-primary-text) !important;
        }

        .btn-school-accent,
        .btn-ecodata-accent {
            background-color: var(--school-accent) !important;
            border-color: var(--school-accent) !important;
            color: var(--school-accent-text) !important;
        }

        .btn-outline-school-primary {
            border-color: var(--school-primary) !important;
            color: var(--school-primary) !important;
        }

        .btn-outline-school-primary:hover,
        .btn-outline-school-primary:focus {
            background-color: var(--school-primary) !important;
            color: var(--school-primary-text) !important;
        }

        .btn-outline-school-secondary {
            border-color: var(--school-secondary) !important;
            color: var(--school-secondary) !important;
        }

        .btn-outline-school-secondary:hover,
        .btn-outline-school-secondary:focus {
            background-color: var(--school-secondary) !important;
            color: var(--school-secondary-text) !important;
        }

        .btn-outline-school-accent {
            border-color: var(--school-accent) !important;
            color: var(--school-accent) !important;
        }

        .btn-outline-school-accent:hover,
        .btn-outline-school-accent:focus {
            background-color: var(--school-accent) !important;
            color: var(--school-accent-text) !important;
        }

        /*
        |--------------------------------------------------------------------------
        | Utilidades institucionales
        |--------------------------------------------------------------------------
        */

        .bg-school-primary {
            background-color: var(--school-primary) !important;
            color: var(--school-primary-text) !important;
        }

        .bg-school-secondary {
            background-color: var(--school-secondary) !important;
            color: var(--school-secondary-text) !important;
        }

        .bg-school-accent {
            background-color: var(--school-accent) !important;
            color: var(--school-accent-text) !important;
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
            background-color: color-mix(in srgb, var(--school-accent) 18%, white);
            color: var(--school-accent);
            border: 1px solid color-mix(in srgb, var(--school-accent) 25%, white);
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

        .form-section-title {
            font-size: .875rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: .75rem;
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
            color: var(--school-primary-text);
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
        | Paginación Laravel
        |--------------------------------------------------------------------------
        */

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
            background: var(--school-primary);
            border-color: var(--school-primary);
            color: var(--school-primary-text);
        }

        .page-link:hover {
            color: var(--school-primary-text);
            background: var(--school-primary);
            border-color: var(--school-primary);
        }

        /*
        |--------------------------------------------------------------------------
        | SweetAlert / modales
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
        | Dropdowns / varios
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
            color: var(--school-primary-text);
        }

        .color-dot {
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 50%;
            display: inline-block;
            border: 2px solid #fff;
            box-shadow: 0 .125rem .375rem rgba(0, 0, 0, .15);
        }

        .school-preview {
            border-radius: 1.25rem;
            color: white;
        }

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

        @media (min-width: 992px) {
            .admin-sidebar {
                min-height: 100vh;
                height: 100%;
                max-height: none;
                overflow-y: visible;
            }
        }

        @media (max-width: 991.98px) {
            .admin-shell,
            .admin-layout {
                min-height: auto;
            }

            .admin-sidebar-col {
                position: sticky;
                top: 0;
                z-index: 1030;
                box-shadow: none;
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

        /*
        |--------------------------------------------------------------------------
        | Fallback color-mix
        |--------------------------------------------------------------------------
        */

        @supports not (background-color: color-mix(in srgb, red 10%, white)) {
            .stat-icon,
            .admin-icon {
                background-color: rgba(34, 197, 94, 0.12);
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
</head>

<body>
    <div class="admin-shell">
        <div class="container-fluid px-0">
            <div class="row g-0 admin-layout">
                @auth
                    <aside class="col-12 col-lg-3 col-xl-2 admin-sidebar-col">
                        @include('components.layout.navigation', ['currentSchool' => $currentSchool])
                    </aside>

                    <main class="col-12 col-lg-9 col-xl-10 admin-content-col">
                        <div class="admin-main p-3 p-md-4 p-xl-5">
                            <div class="admin-content">
                                @yield('content')
                            </div>
                        </div>
                    </main>
                @else
                    <main class="col-12">
                        @yield('content')
                    </main>
                @endauth
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script src="https://cdn.datatables.net/2.2.2/js/dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/2.2.2/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.4/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.4/js/responsive.bootstrap5.min.js"></script>

    @if (session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    icon: 'success',
                    title: 'Correcto',
                    text: @json(session('success')),
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: getComputedStyle(document.documentElement)
                        .getPropertyValue('--school-primary')
                        .trim() || '#22c55e',
                    customClass: {
                        popup: 'rounded-4',
                        confirmButton: 'rounded-4 px-4 fw-semibold'
                    }
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
                        .trim() || '#22c55e',
                    customClass: {
                        popup: 'rounded-4',
                        confirmButton: 'rounded-4 px-4 fw-semibold'
                    }
                });
            });
        </script>
    @endif

    @if (session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: @json(session('error')),
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#dc3545',
                    customClass: {
                        popup: 'rounded-4',
                        confirmButton: 'rounded-4 px-4 fw-semibold'
                    }
                });
            });
        </script>
    @endif

    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Revisa la información',
                    html: `{!! implode('<br>', $errors->all()) !!}`,
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#dc3545',
                    customClass: {
                        popup: 'rounded-4',
                        confirmButton: 'rounded-4 px-4 fw-semibold'
                    }
                });
            });
        </script>
    @endif

    <script>
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

                    ecodataDisableSubmitForm(form, form.dataset.loadingText || 'Procesando...');
                });
            });

            document.querySelectorAll('.js-confirm-delete').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    event.preventDefault();

                    if (form.dataset.submitted === 'true') {
                        return false;
                    }

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
                            .trim() || '#22c55e',
                        reverseButtons: true,
                        customClass: {
                            popup: 'rounded-4',
                            confirmButton: 'rounded-4 px-4 fw-semibold',
                            cancelButton: 'rounded-4 px-4 fw-semibold'
                        }
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            if (ecodataDisableSubmitForm(form, 'Eliminando...')) {
                                form.submit();
                            }
                        }
                    });
                });
            });
        });

        window.ecodataDataTableLanguage = {
            decimal: ',',
            thousands: '.',
            processing: 'Procesando...',
            search: 'Buscar:',
            lengthMenu: 'Mostrar _MENU_ registros',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Mostrando 0 a 0 de 0 registros',
            infoFiltered: '(filtrado de _MAX_ registros totales)',
            loadingRecords: 'Cargando...',
            zeroRecords: 'No se encontraron resultados',
            emptyTable: 'No hay datos disponibles en la tabla',
            paginate: {
                first: 'Primero',
                previous: 'Anterior',
                next: 'Siguiente',
                last: 'Último'
            },
            aria: {
                sortAscending: ': activar para ordenar ascendente',
                sortDescending: ': activar para ordenar descendente'
            }
        };

        document.addEventListener('DOMContentLoaded', function () {
            if (!window.jQuery || !$.fn.DataTable) {
                return;
            }

            $('.js-ecodata-datatable').each(function () {
                const table = $(this);

                if ($.fn.DataTable.isDataTable(this)) {
                    table.DataTable().destroy();
                }

                const emptyText = table.data('empty') || 'No hay datos disponibles en la tabla';

                const paging = table.data('paging') !== false && table.data('paging') !== 'false';
                const searching = table.data('searching') !== false && table.data('searching') !== 'false';
                const info = table.data('info') !== false && table.data('info') !== 'false';
                const pageLength = parseInt(table.data('pageLength') || table.data('page-length') || 10, 10);

                const columnDefs = [
                    {
                        targets: -1,
                        orderable: false,
                        searchable: false,
                        responsivePriority: 1
                    },
                    {
                        targets: 0,
                        responsivePriority: 2
                    }
                ];

                table.DataTable({
                    responsive: false,
                    autoWidth: false,
                    scrollX: true,
                    paging: paging,
                    searching: searching,
                    info: info,
                    ordering: true,
                    pageLength: pageLength,
                    lengthMenu: [5, 10, 15, 25, 50],
                    columnDefs: columnDefs,
                    language: {
                        ...window.ecodataDataTableLanguage,
                        emptyTable: emptyText
                    }
                });
            });
        });
    </script>

    @stack('scripts')
</body>
</html>