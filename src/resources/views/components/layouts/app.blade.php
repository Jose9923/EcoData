<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

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
    </head>
    <body class="font-sans antialiased bg-[#f5f6fa] text-slate-800">
        <div class="min-h-screen flex">
            <livewire:layout.navigation />

            <div class="flex-1 min-w-0">
                <div class="h-[230px]" style="background-color: var(--school-primary);"></div>

                <main class="-mt-[185px] px-6 pb-10 lg:px-10">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>