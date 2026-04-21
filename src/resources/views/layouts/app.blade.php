<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $school = $currentSchool ?? auth()->user()?->school ?? null;
    @endphp

    <title>{{ $school?->display_name ?? $school?->name ?? config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --school-primary: {{ $school?->primary_color ?? '#2563eb' }};
            --school-secondary: {{ $school?->secondary_color ?? '#0f172a' }};
            --school-accent: {{ $school?->accent_color ?? '#22c55e' }};
        }
    </style>
</head>
<body class="bg-slate-100 font-sans text-slate-800 antialiased">
    <div class="min-h-screen lg:grid lg:grid-cols-[272px_minmax(0,1fr)]">
        <aside class="hidden bg-slate-950 lg:block">
            <livewire:layout.navigation />
        </aside>

        <div class="min-w-0">
            <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
                <div class="flex h-20 items-center justify-between px-4 sm:px-6 lg:px-8">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                            Panel administrativo
                        </p>
                        <h1 class="truncate text-lg font-bold text-slate-900">
                            {{ $school?->display_name ?? $school?->name ?? config('app.name', 'Laravel') }}
                        </h1>
                    </div>

                    <div class="flex items-center gap-3">
                        @if($school?->shield_path)
                            <div class="h-10 w-10 overflow-hidden rounded-xl border border-slate-200 bg-slate-100">
                                <img
                                    src="{{ asset('storage/' . $school->shield_path) }}"
                                    alt="Escudo"
                                    class="h-full w-full object-cover"
                                >
                            </div>
                        @endif

                        <div class="hidden sm:block">
                            <p class="text-sm font-semibold text-slate-900">
                                {{ auth()->user()?->name }}
                            </p>
                            <p class="text-xs text-slate-500">
                                {{ auth()->user()?->email }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="border-t border-slate-200 bg-slate-50 px-4 py-3 lg:hidden">
                    <nav class="flex flex-wrap gap-2">
                        <a
                            href="{{ route('dashboard') }}"
                            class="rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-slate-900 text-white' : 'border border-slate-200 bg-white text-slate-700' }}"
                        >
                            Dashboard
                        </a>

                        @if (Route::has('admin.schools.index'))
                            <a
                                href="{{ route('admin.schools.index') }}"
                                class="rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.schools.*') ? 'bg-slate-900 text-white' : 'border border-slate-200 bg-white text-slate-700' }}"
                            >
                                Colegios
                            </a>
                        @endif

                        <a
                            href="{{ route('profile') }}"
                            class="rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('profile') ? 'bg-slate-900 text-white' : 'border border-slate-200 bg-white text-slate-700' }}"
                        >
                            Perfil
                        </a>
                    </nav>
                </div>
            </header>

            <main class="px-4 py-6 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-6xl">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
</body>
</html>