@php
    $authUser = auth()->user();
    $school = $currentSchool ?? $authUser?->school ?? null;

    $isSuperAdmin = $authUser?->hasRole('super_admin') ?? false;
    $isSchoolAdmin = $authUser?->hasRole('admin_colegio') ?? false;
    $isDocente = $authUser?->hasRole('docente') ?? false;
    $isEstudiante = $authUser?->hasRole('estudiante') ?? false;

    /*
    |--------------------------------------------------------------------------
    | Capacidades por rol
    |--------------------------------------------------------------------------
    */
    $canManageSchools = $isSuperAdmin;

    $canManageAdmin = $isSuperAdmin || $isSchoolAdmin;

    $canManageSchoolCatalogs = $isSuperAdmin || $isSchoolAdmin;

    $canManagePhysicalVariables = $isSuperAdmin || $isSchoolAdmin;

    $canAccessPhysicalRecords = $isSuperAdmin || $isSchoolAdmin || $isDocente || $isEstudiante;

    $canAdminPhysicalRecords = $isSuperAdmin || $isSchoolAdmin || $isDocente;

    $canManageStationsAndSensors = $isSuperAdmin || $isSchoolAdmin;

    $canManageLaboratoryGuides = $isSuperAdmin || $isSchoolAdmin || $isDocente;

    $canManageEnvironmentalEvents = $isSuperAdmin || $isSchoolAdmin || $isDocente;

    $canManageFieldDiaries = $isSuperAdmin || $isSchoolAdmin || $isDocente;

    $canUseStudentModules = $isEstudiante;

    $canViewInternalEnvironmentalCalendar = $isSuperAdmin || $isSchoolAdmin || $isDocente || $isEstudiante;

    $canShowEcoDataGroup =
        $canManagePhysicalVariables ||
        $canAccessPhysicalRecords ||
        $canAdminPhysicalRecords ||
        $canManageStationsAndSensors;

    $canShowPedagogyGroup =
        $canManageLaboratoryGuides ||
        $canManageEnvironmentalEvents ||
        $canManageFieldDiaries;

    /*
    |--------------------------------------------------------------------------
    | Estados de menús desplegables
    |--------------------------------------------------------------------------
    */
    $adminOpen = request()->routeIs(
        'admin.schools.*',
        'admin.users.*',
        'admin.grades.*',
        'admin.courses.*'
    );

    $ecoDataOpen = request()->routeIs(
        'admin.physical-variable-categories.*',
        'admin.physical-variables.*',
        'admin.physical-variable-records.*',
        'admin.physical-variable-record-imports.*',
        'admin.weather-stations.*',
        'admin.sensors.*'
    );

    $pedagogyOpen = request()->routeIs(
        'admin.laboratory-guides.*',
        'admin.environmental-events.*',
        'admin.field-diary-activities.*',
        'admin.field-diary-submissions.*'
    );

    $studentOpen = request()->routeIs(
        'estudiante.laboratory-guides.*',
        'estudiante.field-diaries.*'
    );

    $calendarOpen = request()->routeIs('environmental-events.*');
@endphp

<div class="admin-sidebar d-flex flex-column p-3 p-md-4">
    <div class="d-lg-none">
        <button
            class="btn btn-outline-light w-100 rounded-4 d-flex align-items-center justify-content-between"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#adminSidebarMenu"
            aria-expanded="false"
            aria-controls="adminSidebarMenu"
        >
            <span>Menú de navegación</span>
            <span class="fs-5 lh-1">☰</span>
        </button>
    </div>

    <div id="adminSidebarMenu" class="collapse d-lg-flex flex-column flex-grow-1 mt-3 mt-lg-0">
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

            {{-- ADMINISTRACIÓN --}}
            @if($canManageAdmin)
                <li class="nav-item">
                    <button
                        class="nav-link w-100 text-start d-flex justify-content-between align-items-center {{ $adminOpen ? 'active' : '' }}"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#adminGroup"
                        aria-expanded="{{ $adminOpen ? 'true' : 'false' }}"
                        aria-controls="adminGroup"
                    >
                        <span>Administración</span>
                        <span class="small">▾</span>
                    </button>
                </li>

                <div id="adminGroup" class="collapse {{ $adminOpen ? 'show' : '' }}">
                    <ul class="nav nav-pills flex-column gap-2 ms-3 ps-2">
                        @if($canManageSchools && Route::has('admin.schools.index'))
                            <li class="nav-item">
                                <a href="{{ route('admin.schools.index') }}"
                                   class="nav-link {{ request()->routeIs('admin.schools.*') ? 'active' : '' }}">
                                    Colegios
                                </a>
                            </li>
                        @endif

                        @if(Route::has('admin.users.index'))
                            <li class="nav-item">
                                <a href="{{ route('admin.users.index') }}"
                                   class="nav-link {{ request()->routeIs('admin.users.*') && ! request()->routeIs('admin.users.import*') ? 'active' : '' }}">
                                    Usuarios
                                </a>
                            </li>
                        @endif

                        @if(Route::has('admin.users.import'))
                            <li class="nav-item">
                                <a href="{{ route('admin.users.import') }}"
                                   class="nav-link {{ request()->routeIs('admin.users.import*') ? 'active' : '' }}">
                                    Cargue masivo de usuarios
                                </a>
                            </li>
                        @endif

                        @if($canManageSchoolCatalogs && Route::has('admin.grades.index'))
                            <li class="nav-item">
                                <a href="{{ route('admin.grades.index') }}"
                                   class="nav-link {{ request()->routeIs('admin.grades.*') ? 'active' : '' }}">
                                    Grados
                                </a>
                            </li>
                        @endif

                        @if($canManageSchoolCatalogs && Route::has('admin.courses.index'))
                            <li class="nav-item">
                                <a href="{{ route('admin.courses.index') }}"
                                   class="nav-link {{ request()->routeIs('admin.courses.*') ? 'active' : '' }}">
                                    Cursos
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>

                <hr class="my-3">
            @endif

            {{-- ECODATA --}}
            @if($canShowEcoDataGroup)
                <li class="nav-item">
                    <button
                        class="nav-link w-100 text-start d-flex justify-content-between align-items-center {{ $ecoDataOpen ? 'active' : '' }}"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#ecoDataGroup"
                        aria-expanded="{{ $ecoDataOpen ? 'true' : 'false' }}"
                        aria-controls="ecoDataGroup"
                    >
                        <span>EcoData</span>
                        <span class="small">▾</span>
                    </button>
                </li>

                <div id="ecoDataGroup" class="collapse {{ $ecoDataOpen ? 'show' : '' }}">
                    <ul class="nav nav-pills flex-column gap-2 ms-3 ps-2">
                        @if($isSuperAdmin && Route::has('admin.physical-variable-categories.index'))
                            <li class="nav-item">
                                <a href="{{ route('admin.physical-variable-categories.index') }}"
                                   class="nav-link {{ request()->routeIs('admin.physical-variable-categories.*') ? 'active' : '' }}">
                                    Categorías de variables
                                </a>
                            </li>
                        @endif

                        @if($canManagePhysicalVariables && Route::has('admin.physical-variables.index'))
                            <li class="nav-item">
                                <a href="{{ route('admin.physical-variables.index') }}"
                                   class="nav-link {{ request()->routeIs('admin.physical-variables.*') ? 'active' : '' }}">
                                    Variables físicas
                                </a>
                            </li>
                        @endif

                        @if($canAccessPhysicalRecords && Route::has('admin.physical-variable-records.index'))
                            <li class="nav-item">
                                <a href="{{ route('admin.physical-variable-records.index') }}"
                                   class="nav-link {{ request()->routeIs('admin.physical-variable-records.*') ? 'active' : '' }}">
                                    Registros físicos
                                </a>
                            </li>
                        @endif

                        @if($canAdminPhysicalRecords && Route::has('admin.physical-variable-record-imports.create'))
                            <li class="nav-item">
                                <a href="{{ route('admin.physical-variable-record-imports.create') }}"
                                   class="nav-link {{ request()->routeIs('admin.physical-variable-record-imports.*') ? 'active' : '' }}">
                                    Cargue CSV
                                </a>
                            </li>
                        @endif

                        @if($canManageStationsAndSensors && Route::has('admin.weather-stations.index'))
                            <li class="nav-item">
                                <a href="{{ route('admin.weather-stations.index') }}"
                                   class="nav-link {{ request()->routeIs('admin.weather-stations.*') ? 'active' : '' }}">
                                    Estaciones meteorológicas
                                </a>
                            </li>
                        @endif

                        @if($canManageStationsAndSensors && Route::has('admin.sensors.index'))
                            <li class="nav-item">
                                <a href="{{ route('admin.sensors.index') }}"
                                   class="nav-link {{ request()->routeIs('admin.sensors.*') ? 'active' : '' }}">
                                    Sensores
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>

                <hr class="my-3">
            @endif

            {{-- PEDAGOGÍA --}}
            @if($canShowPedagogyGroup)
                <li class="nav-item">
                    <button
                        class="nav-link w-100 text-start d-flex justify-content-between align-items-center {{ $pedagogyOpen ? 'active' : '' }}"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#pedagogyGroup"
                        aria-expanded="{{ $pedagogyOpen ? 'true' : 'false' }}"
                        aria-controls="pedagogyGroup"
                    >
                        <span>Pedagogía</span>
                        <span class="small">▾</span>
                    </button>
                </li>

                <div id="pedagogyGroup" class="collapse {{ $pedagogyOpen ? 'show' : '' }}">
                    <ul class="nav nav-pills flex-column gap-2 ms-3 ps-2">
                        @if($canManageLaboratoryGuides && Route::has('admin.laboratory-guides.index'))
                            <li class="nav-item">
                                <a href="{{ route('admin.laboratory-guides.index') }}"
                                   class="nav-link {{ request()->routeIs('admin.laboratory-guides.*') ? 'active' : '' }}">
                                    Guías de laboratorio
                                </a>
                            </li>
                        @endif

                        @if($canManageEnvironmentalEvents && Route::has('admin.environmental-events.index'))
                            <li class="nav-item">
                                <a href="{{ route('admin.environmental-events.index') }}"
                                   class="nav-link {{ request()->routeIs('admin.environmental-events.*') ? 'active' : '' }}">
                                    Calendario ambiental
                                </a>
                            </li>
                        @endif

                        @if($canManageFieldDiaries && Route::has('admin.field-diary-activities.index'))
                            <li class="nav-item">
                                <a href="{{ route('admin.field-diary-activities.index') }}"
                                   class="nav-link {{ request()->routeIs('admin.field-diary-activities.*') ? 'active' : '' }}">
                                    Diario de Campo
                                </a>
                            </li>
                        @endif

                        @if($canManageFieldDiaries && Route::has('admin.field-diary-submissions.index'))
                            <li class="nav-item">
                                <a href="{{ route('admin.field-diary-submissions.index') }}"
                                   class="nav-link {{ request()->routeIs('admin.field-diary-submissions.*') ? 'active' : '' }}">
                                    Revisión Diario
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>

                <hr class="my-3">
            @endif

            {{-- ESTUDIANTE --}}
            @if($canUseStudentModules)
                <li class="nav-item">
                    <button
                        class="nav-link w-100 text-start d-flex justify-content-between align-items-center {{ $studentOpen ? 'active' : '' }}"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#studentGroup"
                        aria-expanded="{{ $studentOpen ? 'true' : 'false' }}"
                        aria-controls="studentGroup"
                    >
                        <span>Estudiante</span>
                        <span class="small">▾</span>
                    </button>
                </li>

                <div id="studentGroup" class="collapse {{ $studentOpen ? 'show' : '' }}">
                    <ul class="nav nav-pills flex-column gap-2 ms-3 ps-2">
                        @if(Route::has('estudiante.laboratory-guides.index'))
                            <li class="nav-item">
                                <a href="{{ route('estudiante.laboratory-guides.index') }}"
                                   class="nav-link {{ request()->routeIs('estudiante.laboratory-guides.*') ? 'active' : '' }}">
                                    Mis guías de laboratorio
                                </a>
                            </li>
                        @endif

                        @if(Route::has('estudiante.field-diaries.index'))
                            <li class="nav-item">
                                <a href="{{ route('estudiante.field-diaries.index') }}"
                                   class="nav-link {{ request()->routeIs('estudiante.field-diaries.*') ? 'active' : '' }}">
                                    Mi Diario de Campo
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>

                <hr class="my-3">
            @endif

            {{-- CALENDARIO AMBIENTAL PÚBLICO INTERNO --}}
            @if($canViewInternalEnvironmentalCalendar && Route::has('environmental-events.index'))
                <li class="nav-item">
                    <a href="{{ route('environmental-events.index') }}"
                       class="nav-link {{ $calendarOpen ? 'active' : '' }}">
                        Eventos ambientales
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