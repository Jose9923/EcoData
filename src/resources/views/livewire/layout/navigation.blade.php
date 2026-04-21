@php
    $school = $currentSchool ?? auth()->user()?->school ?? null;
@endphp

<div class="flex h-screen flex-col border-r border-slate-800 bg-slate-950 text-slate-200">
    <div class="border-b border-slate-800 px-6 py-6">
        <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-2xl bg-slate-900 ring-1 ring-white/10">
                @if($school?->shield_path)
                    <img
                        src="{{ asset('storage/' . $school->shield_path) }}"
                        alt="Escudo"
                        class="h-full w-full object-cover"
                    >
                @else
                    <span class="text-base font-bold text-white">
                        {{ strtoupper(substr($school?->name ?? 'E', 0, 1)) }}
                    </span>
                @endif
            </div>

            <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-white">
                    {{ $school?->display_name ?? $school?->name ?? config('app.name', 'Laravel') }}
                </p>
                <p class="mt-1 text-xs text-slate-400">
                    Panel administrativo
                </p>
            </div>
        </div>
    </div>

    <div class="border-b border-slate-800 px-6 py-4">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
            Usuario
        </p>
        <p class="mt-3 text-sm font-semibold text-white">
            {{ auth()->user()?->name }}
        </p>
        <p class="mt-1 text-xs text-slate-400">
            {{ auth()->user()?->email }}
        </p>
    </div>

    <nav class="flex-1 space-y-1 px-4 py-6">
        <a
            href="{{ route('dashboard') }}"
            class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"
        >
            <svg
                class="h-5 w-5 {{ request()->routeIs('dashboard') ? '' : 'text-slate-500 group-hover:text-slate-300' }}"
                style="{{ request()->routeIs('dashboard') ? 'color: var(--school-primary);' : '' }}"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5 12 4l9 9.5" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 10.75V20h13.5v-9.25" />
            </svg>
            <span>Dashboard</span>
        </a>

        @if (Route::has('admin.schools.index'))
            <a
                href="{{ route('admin.schools.index') }}"
                class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('admin.schools.*') ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"
            >
                <svg
                    class="h-5 w-5 {{ request()->routeIs('admin.schools.*') ? '' : 'text-slate-500 group-hover:text-slate-300' }}"
                    style="{{ request()->routeIs('admin.schools.*') ? 'color: var(--school-primary);' : '' }}"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 20h16" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 20V10l6-4 6 4v10" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 20v-4h6v4" />
                </svg>
                <span>Colegios</span>
            </a>
        @endif

        @if (Route::has('admin.users.index'))
            <a
                href="{{ route('admin.users.index') }}"
                class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('admin.users.*') ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"
            >
                <svg
                    class="h-5 w-5 {{ request()->routeIs('admin.users.*') ? '' : 'text-slate-500 group-hover:text-slate-300' }}"
                    style="{{ request()->routeIs('admin.users.*') ? 'color: var(--school-primary);' : '' }}"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5V4H2v16h5" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 20v-2a3 3 0 0 0-3-3H9a3 3 0 0 0-3 3v2" />
                    <circle cx="10.5" cy="9" r="3.5" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 8h3" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 6.5v3" />
                </svg>
                <span>Usuarios</span>
            </a>
        @endif

        @if (Route::has('admin.grades.index'))
            <a
                href="{{ route('admin.grades.index') }}"
                class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('admin.grades.*') ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"
            >
                <svg
                    class="h-5 w-5 {{ request()->routeIs('admin.grades.*') ? '' : 'text-slate-500 group-hover:text-slate-300' }}"
                    style="{{ request()->routeIs('admin.grades.*') ? 'color: var(--school-primary);' : '' }}"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h16" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 18h16" />
                </svg>
                <span>Grados</span>
            </a>
        @endif

        @if (Route::has('admin.courses.index'))
            <a
                href="{{ route('admin.courses.index') }}"
                class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('admin.courses.*') ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"
            >
                <svg
                    class="h-5 w-5 {{ request()->routeIs('admin.courses.*') ? '' : 'text-slate-500 group-hover:text-slate-300' }}"
                    style="{{ request()->routeIs('admin.courses.*') ? 'color: var(--school-primary);' : '' }}"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 4h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 9h6" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17h4" />
                </svg>
                <span>Cursos</span>
            </a>
        @endif
        <a
            href="{{ route('profile') }}"
            class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('profile') ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"
        >
            <svg
                class="h-5 w-5 {{ request()->routeIs('profile') ? '' : 'text-slate-500 group-hover:text-slate-300' }}"
                style="{{ request()->routeIs('profile') ? 'color: var(--school-primary);' : '' }}"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 21a8 8 0 1 0-16 0" />
                <circle cx="12" cy="8" r="4" />
            </svg>
            <span>Perfil</span>
        </a>
    </nav>

    <div class="border-t border-slate-800 p-4">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                class="flex w-full items-center justify-center rounded-2xl border border-slate-700 bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
            >
                Cerrar sesión
            </button>
        </form>
    </div>
</div>