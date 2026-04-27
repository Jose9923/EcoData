@php
    $authUser = auth()->user();
    $school = $currentSchool ?? $authUser?->school ?? null;

    $isSuperAdmin = $authUser?->hasRole('super_admin') ?? false;
    $isSchoolAdmin = $authUser?->hasRole('admin_colegio') ?? false;
    $isdocente = $authUser?->hasRole('docente') ?? false;
    $isestudiante = $authUser?->hasRole('estudiante') ?? false;

    $canManageSchoolCatalogs = $isSuperAdmin || $isSchoolAdmin;
    $canManagePhysicalRecords = $isSuperAdmin || $isSchoolAdmin || $isdocente;
    $canManageLaboratoryGuides = $isSuperAdmin || $isSchoolAdmin || $isdocente;
@endphp

<div class="admin-sidebar d-flex flex-column p-3 p-md-4">
    <div class="d-lg-none mb-3">
        <button
            class="btn btn-outline-light w-100 rounded-4 d-flex align-items-center justify-content-between"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#adminSidebarMenu"
            aria-expanded="false"
            aria-controls="adminSidebarMenu"
        >
            <span>Menú de navegación</span>
            <span>☰</span>
        </button>
    </div>

    <div id="adminSidebarMenu" class="collapse d-lg-flex flex-column flex-grow-1">
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
                    {{ $school?->display_name ?? $school?->name ?? config('app.name', 'EcoData') }}
                </div>
                <small class="text-secondary">Panel EcoData</small>
            </div>
        </div>
    </div>

    <div class="border-bottom border-secondary-subtle pb-4 mb-4">
        <div class="text-uppercase small fw-bold text-secondary">Usuario</div>
        <div class="mt-3 fw-semibold text-white">{{ $authUser?->name }}</div>
        <small class="text-secondary">{{ $authUser?->email }}</small>

        @if($authUser?->roles?->isNotEmpty())
            <div class="mt-2">
                @foreach($authUser->roles as $role)
                    <span class="badge rounded-pill text-bg-secondary">
                        {{ str_replace('_', ' ', \Illuminate\Support\Str::title($role->name)) }}
                    </span>
                @endforeach
            </div>
        @endif
    </div>

    <ul class="nav nav-pills flex-column gap-2">
        @if (Route::has('dashboard'))
            <li class="nav-item">
                <a href="{{ route('dashboard') }}"
                   class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    Dashboard
                </a>
            </li>
        @endif

        @if (Route::has('profile.edit'))
            <li class="nav-item">
                <a href="{{ route('profile.edit') }}"
                   class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                    Mi perfil
                </a>
            </li>
        @endif

        <hr class="my-3">

        @if ($isSuperAdmin && Route::has('admin.schools.index'))
            <li class="nav-item">
                <a href="{{ route('admin.schools.index') }}"
                   class="nav-link {{ request()->routeIs('admin.schools.*') ? 'active' : '' }}">
                    Colegios
                </a>
            </li>
        @endif

        @if (($isSuperAdmin || $isSchoolAdmin) && Route::has('admin.users.index'))
            <li class="nav-item">
                <a href="{{ route('admin.users.index') }}"
                   class="nav-link {{ request()->routeIs('admin.users.*') && ! request()->routeIs('admin.users.import*') ? 'active' : '' }}">
                    Usuarios
                </a>
            </li>
        @endif

        @if (($isSuperAdmin || $isSchoolAdmin) && Route::has('admin.users.import'))
            <li class="nav-item">
                <a href="{{ route('admin.users.import') }}"
                   class="nav-link {{ request()->routeIs('admin.users.import*') ? 'active' : '' }}">
                    Cargue masivo de usuarios
                </a>
            </li>
        @endif

        @if ($canManageSchoolCatalogs && Route::has('admin.grades.index'))
            <li class="nav-item">
                <a href="{{ route('admin.grades.index') }}"
                   class="nav-link {{ request()->routeIs('admin.grades.*') ? 'active' : '' }}">
                    Grados
                </a>
            </li>
        @endif

        @if ($canManageSchoolCatalogs && Route::has('admin.courses.index'))
            <li class="nav-item">
                <a href="{{ route('admin.courses.index') }}"
                   class="nav-link {{ request()->routeIs('admin.courses.*') ? 'active' : '' }}">
                    Cursos
                </a>
            </li>
        @endif

        @if ($isSuperAdmin && Route::has('admin.physical-variable-categories.index'))
            <li class="nav-item">
                <a href="{{ route('admin.physical-variable-categories.index') }}"
                   class="nav-link {{ request()->routeIs('admin.physical-variable-categories.*') ? 'active' : '' }}">
                    Categorías de variables
                </a>
            </li>
        @endif

        @if ($canManageSchoolCatalogs && Route::has('admin.physical-variables.index'))
            <li class="nav-item">
                <a href="{{ route('admin.physical-variables.index') }}"
                   class="nav-link {{ request()->routeIs('admin.physical-variables.*') ? 'active' : '' }}">
                    Variables físicas
                </a>
            </li>
        @endif

        @if ($canManagePhysicalRecords && Route::has('admin.physical-variable-records.index'))
            <li class="nav-item">
                <a href="{{ route('admin.physical-variable-records.index') }}"
                   class="nav-link {{ request()->routeIs('admin.physical-variable-records.*') ? 'active' : '' }}">
                    Registros físicos
                </a>
            </li>
        @endif

        @if ($canManageLaboratoryGuides && Route::has('admin.laboratory-guides.index'))
            <li class="nav-item">
                <a href="{{ route('admin.laboratory-guides.index') }}"
                   class="nav-link {{ request()->routeIs('admin.laboratory-guides.*') ? 'active' : '' }}">
                    Guías de laboratorio
                </a>
            </li>
        @endif

        @if ($isestudiante && Route::has('estudiante.laboratory-guides.index'))
            <li class="nav-item">
                <a href="{{ route('estudiante.laboratory-guides.index') }}"
                   class="nav-link {{ request()->routeIs('estudiante.laboratory-guides.*') ? 'active' : '' }}">
                    Mis guías de laboratorio
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
</div>