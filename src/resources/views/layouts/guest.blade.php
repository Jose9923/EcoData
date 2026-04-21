Y si tienes:<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'EcoData') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#04130d] font-sans text-white antialiased">
    <div class="relative min-h-screen overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(16,185,129,0.18),transparent_22%),radial-gradient(circle_at_top_right,rgba(34,197,94,0.12),transparent_24%),radial-gradient(circle_at_bottom_left,rgba(13,148,136,0.12),transparent_22%),linear-gradient(180deg,#0a1f17_0%,#10281f_28%,#163327_52%,#1d3b2e_72%,#274838_100%)]"></div>

        <div class="absolute -left-28 top-10 h-80 w-80 rounded-full bg-emerald-700/20 blur-3xl"></div>
        <div class="absolute right-[-4rem] top-0 h-96 w-96 rounded-full bg-green-700/15 blur-3xl"></div>
        <div class="absolute bottom-[-3rem] left-1/4 h-80 w-80 rounded-full bg-teal-700/15 blur-3xl"></div>
        <div class="absolute bottom-0 right-1/4 h-72 w-72 rounded-full bg-lime-700/10 blur-3xl"></div>

        <div class="relative mx-auto flex min-h-screen max-w-7xl items-center px-4 py-10 sm:px-6 lg:px-8">
            <div class="grid w-full items-center gap-10 lg:grid-cols-[1.05fr_0.95fr]">
                <div class="hidden lg:block">
                    <div class="max-w-xl">
                        <p class="text-sm font-semibold uppercase tracking-[0.28em] text-emerald-300">
                            EcoData
                        </p>

                        <h1 class="mt-4 text-5xl font-extrabold leading-tight text-white">
                            Datos ambientales escolares con una interfaz clara y útil.
                        </h1>

                        <p class="mt-5 max-w-lg text-base leading-7 text-slate-200">
                            Gestiona colegios, usuarios, variables físicas, registros y reportes desde una plataforma
                            diseñada para integrar datos, territorio y educación ambiental.
                        </p>

                        <div class="mt-8 grid gap-4 sm:grid-cols-2">
                            <div class="rounded-3xl border border-white/70 bg-white/10 p-5 shadow-lg shadow-slate-200/50 backdrop-blur">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-200/80">
                                    Monitoreo
                                </p>
                                <p class="mt-2 text-lg font-bold text-white">
                                    Registros físicos dinámicos
                                </p>
                                <p class="mt-2 text-sm leading-6 text-slate-200">
                                    Captura datos de clima, agua, suelo y laboratorio sin depender de formularios rígidos.
                                </p>
                            </div>

                            <div class="rounded-3xl border border-white/70 bg-white/10 p-5 shadow-lg shadow-slate-200/50 backdrop-blur">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-200/80">
                                    Análisis
                                </p>
                                <p class="mt-2 text-lg font-bold text-white">
                                    Consulta y reportes
                                </p>
                                <p class="mt-2 text-sm leading-6 text-slate-200">
                                    Filtra y analiza información ambiental escolar con una experiencia sobria y consistente.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mx-auto w-full max-w-md lg:max-w-lg">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>