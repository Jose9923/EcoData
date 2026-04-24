@php
    $school = $currentSchool ?? auth()->user()?->school ?? null;
@endphp

<div class="admin-sidebar d-flex flex-column p-3 p-md-4">
    <div class="border-bottom border-secondary-subtle pb-4 mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="school-avatar bg-dark text-white">
                @if($school?->shield_path)
                    <img
                        src="{{ asset('storage/' . $school->shield_path) }}"
                        alt="Escudo"
                        class="w-100 h-100 object-fit-cover"
                    >
                @else
                    <span>{{ strtoupper(substr($school?->name ?? 'E', 0, 1)) }}</span>
                @endif
            </div>

            <div class="text-truncate">
                <div class="fw-semibold text-white text-truncate">
                    {{ $school?->display_name ?? $school?->name ?? config('app.name', 'Laravel') }}
                </div>
                <small class="text-secondary">Panel administrativo</small>
            </div>
        </div>
    </div>

    <div class="border-bottom border-secondary-subtle pb-4 mb-4">
        <div class="text-uppercase small fw-bold text-secondary">Usuario</div>
        <div class="mt-3 fw-semibold text-white">{{ auth()->user()?->name }}</div>
        <small class="text-secondary">{{ auth()->user()?->email }}</small>
    </div>

    <ul class="nav nav-pills flex-column gap-2">
        <li class="nav-item">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                Dashboard
            </a>
        </li>
        <hr class="my-3">

        <li class="nav-item">
            <a href="{{ route('profile.edit') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                Mi perfil
            </a>
        </li>
        @if (Route::has('admin.schools.index'))
            <li class="nav-item">
                <a href="{{ route('admin.schools.index') }}" class="nav-link {{ request()->routeIs('admin.schools.*') ? 'active' : '' }}">
                    Colegios
                </a>
            </li>
        @endif

        @if (Route::has('admin.users.index'))
            <li class="nav-item">
                <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    Usuarios
                </a>
            </li>
        @endif

        @if (Route::has('admin.grades.index'))
            <li class="nav-item">
                <a href="{{ route('admin.grades.index') }}" class="nav-link {{ request()->routeIs('admin.grades.*') ? 'active' : '' }}">
                    Grados
                </a>
            </li>
        @endif

        @if (Route::has('admin.courses.index'))
            <li class="nav-item">
                <a href="{{ route('admin.courses.index') }}" class="nav-link {{ request()->routeIs('admin.courses.*') ? 'active' : '' }}">
                    Cursos
                </a>
            </li>
        @endif

        @if (Route::has('admin.physical-variable-categories.index'))
            <li class="nav-item">
                <a href="{{ route('admin.physical-variable-categories.index') }}" class="nav-link {{ request()->routeIs('admin.physical-variable-categories.*') ? 'active' : '' }}">
                    Categorías de Variables
                </a>
            </li>
        @endif

        @if (Route::has('admin.physical-variables.index'))
            <li class="nav-item">
                <a href="{{ route('admin.physical-variables.index') }}" class="nav-link {{ request()->routeIs('admin.physical-variables.*') ? 'active' : '' }}">
                    Variables Físicas
                </a>
            </li>
        @endif

        @if (Route::has('admin.physical-variable-records.index'))
            <li class="nav-item">
                <a href="{{ route('admin.physical-variable-records.index') }}" class="nav-link {{ request()->routeIs('admin.physical-variable-records.*') ? 'active' : '' }}">
                    Registros Físicos
                </a>
            </li>
        @endif

        @if (Route::has('admin.laboratory-guides.index'))
            <li class="nav-item">
                <a href="{{ route('admin.laboratory-guides.index') }}"
                class="nav-link {{ request()->routeIs('admin.laboratory-guides.*') ? 'active' : '' }}">
                    Guías de laboratorio
                </a>
            </li>
        @endif

        @if (Route::has('student.laboratory-guides.index'))
            <li class="nav-item">
                <a href="{{ route('student.laboratory-guides.index') }}"
                class="nav-link {{ request()->routeIs('student.laboratory-guides.*') ? 'active' : '' }}">
                    Mis guías de laboratorio
                </a>
            </li>
        @endif

        @if (Route::has('admin.users.import'))
            <li class="nav-item">
                <a href="{{ route('admin.users.import') }}" class="nav-link {{ request()->routeIs('admin.users.import*') ? 'active' : '' }}">
                    Cargue masivo de usuarios
                </a>
            </li>
        @endif
    </ul>

    <div class="mt-auto pt-4">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline-light w-100 rounded-4">
                Cerrar sesión
            </button>
        </form>
    </div>
</div>