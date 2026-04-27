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
        }

        .admin-sidebar {
            min-height: 100vh;
            height: 100%;
            background: var(--school-secondary);
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
                position: sticky;
                top: 0;
                max-height: 100vh;
                overflow-y: auto;
            }
        }

        @media (max-width: 991.98px) {
            .admin-layout {
                min-height: auto;
            }

            .admin-sidebar {
                min-height: auto;
                height: auto;
            }

            .admin-main {
                padding-top: 1rem !important;
            }
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
</body>
</html>