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
    <link rel="icon" href="{{ asset('img/favicon.ico') }}">

    <title>{{ config('app.name', 'EcoData') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/scss/app.scss', 'resources/js/app.js'])


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

    <script id="ecodata-messages" type="application/json">{!! json_encode([
        'success' => session('success'),
        'warning' => session('warning'),
        'error' => session('error'),
        'errors' => $errors->all(),
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>



    @stack('scripts')
</body>
</html>
