<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

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
</head>
<body>
    <div class="container-fluid" style="padding: 0;">
        <div class="row g-0">
            <aside class="col-12 col-lg-3 col-xl-2">
                @include('components.layout.navigation')
            </aside>

            <div class="col-12 col-lg-9 col-xl-10">
                <main class="p-3 p-md-4 p-xl-5">
                    @yield('content')
                </main>
            </div>
        </div>
    </div>
    @if ($errors->any())
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Hay errores en el formulario',
            text: 'Revisa los campos marcados y corrige los valores fuera de rango.',
            confirmButtonText: 'Entendido'
        });
    </script>
    @endif
</body>
</html>