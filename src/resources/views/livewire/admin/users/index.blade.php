<div class="space-y-6">
    {{-- Header --}}
    <div class="overflow-hidden rounded-3xl shadow-lg">
        <section class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-700 p-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-sm font-medium uppercase tracking-[0.2em] text-slate-300">
                        Administración
                    </p>
                    <h1 class="mt-1 text-3xl font-bold text-white">
                        Usuarios
                    </h1>
                    <p class="mt-2 max-w-2xl text-sm text-slate-300">
                        Gestiona usuarios, roles, colegio y asignación académica desde un solo módulo.
                    </p>
                </div>

                <button
                    type="button"
                    wire:click="create"
                    class="inline-flex items-center justify-center rounded-2xl px-5 py-3 text-sm font-semibold text-white shadow-md transition hover:opacity-90"
                    style="background-color: var(--school-primary);"
                >
                    + Nuevo usuario
                </button>
            </div>
        </section>
    </div>

    {{-- Flash message --}}
    @if (session()->has('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Toolbar --}}
    <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-4 lg:grid-cols-[1fr_auto]">
            <div>
                <label for="user-search" class="mb-2 block text-sm font-semibold text-slate-700">
                    Buscar usuario
                </label>

                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.5 3a5.5 5.5 0 104.22 9.03l3.62 3.62a.75.75 0 101.06-1.06l-3.62-3.62A5.5 5.5 0 008.5 3zm-4 5.5a4 4 0 118 0 4 4 0 01-8 0z" clip-rule="evenodd" />
                        </svg>
                    </span>

                    <input
                        id="user-search"
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Buscar por nombre, correo, colegio, rol, grado o curso..."
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-11 pr-4 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                    >
                </div>
            </div>

            <div class="w-full lg:w-52">
                <label for="per-page" class="mb-2 block text-sm font-semibold text-slate-700">
                    Registros por página
                </label>

                <select
                    id="per-page"
                    wire:model.live="perPage"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                >
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
        </div>

        <div class="mt-4 flex flex-col gap-2 border-t border-slate-100 pt-4 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
            <p>
                Mostrando {{ $users->firstItem() ?? 0 }} - {{ $users->lastItem() ?? 0 }} de {{ $users->total() }} usuarios
            </p>

            @if ($search !== '')
                <p class="font-medium text-slate-600">
                    Filtro aplicado: “{{ $search }}”
                </p>
            @endif
        </div>
    </section>

    {{-- Table --}}
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-100/80">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                            Usuario
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                            Rol
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                            Colegio
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                            Asignación académica
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                            Estado
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider text-slate-500">
                            Acciones
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        @php
                            $roleName = $user->roles->first()?->name;
                            $roleLabel = $roleName ? str_replace('_', ' ', $roleName) : 'Sin rol';
                            $gradeLabel = $user->grade?->label ?: $user->grade?->name;
                            $courseLabel = $user->course?->label ?: $user->course?->name;
                        @endphp

                        <tr class="transition hover:bg-slate-50/70">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
                                        <span class="text-lg font-bold text-slate-500">
                                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user->name, 0, 1)) }}
                                        </span>
                                    </div>

                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <p class="truncate text-sm font-semibold text-slate-900">
                                                {{ $user->name }}
                                            </p>

                                            @if ((int) auth()->id() === (int) $user->id)
                                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">
                                                    Actual
                                                </span>
                                            @endif
                                        </div>

                                        <p class="truncate text-xs text-slate-500">
                                            {{ $user->email }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-xl bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                                    {{ \Illuminate\Support\Str::title($roleLabel) }}
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                <div class="max-w-[220px]">
                                    @if ($user->school)
                                        <p class="truncate text-sm font-medium text-slate-800">
                                            {{ $user->school->name }}
                                        </p>
                                    @else
                                        <span class="text-sm text-slate-400">Sin asignar</span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="space-y-1">
                                    <p class="text-sm text-slate-700">
                                        <span class="font-semibold">Grado:</span>
                                        {{ $gradeLabel ?: 'Sin asignar' }}
                                    </p>
                                    <p class="text-sm text-slate-700">
                                        <span class="font-semibold">Curso:</span>
                                        {{ $courseLabel ?: 'Sin asignar' }}
                                    </p>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                @if ($user->is_active)
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                        Activo
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-700">
                                        Inactivo
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        type="button"
                                        wire:click="edit({{ $user->id }})"
                                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                    >
                                        Editar
                                    </button>

                                    <button
                                        type="button"
                                        onclick="confirm('¿Seguro que deseas eliminar este usuario?') || event.stopImmediatePropagation()"
                                        wire:click="delete({{ $user->id }})"
                                        class="rounded-xl border border-rose-200 bg-white px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50"
                                    >
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-14 text-center">
                                <div class="mx-auto max-w-md">
                                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M17 20h5V4H2v16h5m10 0v-5a3 3 0 00-3-3H10a3 3 0 00-3 3v5m10 0H7m8-10a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                    </div>

                                    <h3 class="mt-4 text-base font-semibold text-slate-900">
                                        {{ $search !== '' ? 'No se encontraron resultados' : 'No hay usuarios registrados' }}
                                    </h3>

                                    <p class="mt-1 text-sm text-slate-500">
                                        @if ($search !== '')
                                            Ajusta el término de búsqueda o limpia el filtro para ver todos los usuarios.
                                        @else
                                            Crea el primer usuario para comenzar a operar el panel administrativo.
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $users->links() }}
            </div>
        @endif
    </section>

    {{-- Modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center px-4 py-8">
                <div
                    class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                    wire:click="closeModal"
                ></div>

                <div class="relative z-10 w-full max-w-5xl rounded-3xl bg-white shadow-2xl">
                    <div class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
                        <div>
                            <h2 class="text-xl font-bold text-slate-900">
                                {{ $isEditing ? 'Editar usuario' : 'Nuevo usuario' }}
                            </h2>
                            <p class="mt-1 text-sm text-slate-500">
                                Configura identidad, acceso y asignación académica del usuario.
                            </p>
                        </div>

                        <button
                            type="button"
                            wire:click="closeModal"
                            class="rounded-xl p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form wire:submit.prevent="save" class="space-y-6 px-6 py-6">
                        <div class="grid gap-6 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Nombre completo <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    wire:model.defer="name"
                                    placeholder="Ej: José Luis Vargas"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                                >
                                @error('name')
                                    <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Correo electrónico <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    type="email"
                                    wire:model.defer="email"
                                    placeholder="correo@institucion.edu"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                                >
                                @error('email')
                                    <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid gap-6 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Contraseña {{ $isEditing ? '' : '*' }}
                                </label>
                                <input
                                    type="password"
                                    wire:model.defer="password"
                                    placeholder="{{ $isEditing ? 'Déjala vacía para conservar la actual' : 'Mínimo 8 caracteres' }}"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                                >
                                @error('password')
                                    <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Confirmar contraseña {{ $isEditing ? '' : '*' }}
                                </label>
                                <input
                                    type="password"
                                    wire:model.defer="password_confirmation"
                                    placeholder="Repite la contraseña"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                                >
                            </div>
                        </div>

                        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Colegio
                                </label>
                                <select
                                    wire:model.live="school_id"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                                >
                                    <option value="">Seleccionar...</option>
                                    @foreach ($schools as $school)
                                        <option value="{{ $school->id }}">{{ $school->name }}</option>
                                    @endforeach
                                </select>
                                @error('school_id')
                                    <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Rol <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    wire:model.defer="role"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                                >
                                    <option value="">Seleccionar...</option>
                                    @foreach ($roles as $roleOption)
                                        <option value="{{ $roleOption->name }}">
                                            {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $roleOption->name)) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('role')
                                    <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Grado
                                </label>
                                <select
                                    wire:model.live="grade_id"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                                >
                                    <option value="">Seleccionar...</option>
                                    @foreach ($grades as $grade)
                                        <option value="{{ $grade->id }}">
                                            {{ $grade->label ?: $grade->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('grade_id')
                                    <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Curso
                                </label>
                                <select
                                    wire:model.defer="course_id"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                                >
                                    <option value="">Seleccionar...</option>
                                    @foreach ($courses as $course)
                                        <option value="{{ $course->id }}">
                                            {{ $course->label ?: $course->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('course_id')
                                    <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                <h3 class="text-sm font-semibold text-slate-700">
                                    Consideraciones de acceso
                                </h3>

                                <div class="mt-4 space-y-3 text-sm text-slate-600">
                                    <p>
                                        El correo electrónico debe ser único dentro del sistema.
                                    </p>
                                    <p>
                                        @if ($isEditing)
                                            Si no deseas cambiar la contraseña, déjala vacía.
                                        @else
                                            La contraseña es obligatoria al crear un nuevo usuario.
                                        @endif
                                    </p>
                                    <p>
                                        Grado y curso son opcionales, pero si los usas deben corresponder al colegio seleccionado.
                                    </p>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                <h3 class="text-sm font-semibold text-slate-700">
                                    Estado del usuario
                                </h3>

                                <div class="mt-4">
                                    <label class="inline-flex items-center gap-3">
                                        <input
                                            type="checkbox"
                                            wire:model.defer="is_active"
                                            class="h-5 w-5 rounded border-slate-300"
                                            style="accent-color: var(--school-primary);"
                                        >
                                        <span class="text-sm font-medium text-slate-700">
                                            Usuario activo
                                        </span>
                                    </label>

                                    @error('is_active')
                                        <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Vista rápida
                                    </p>

                                    <div class="mt-3 space-y-2 text-sm">
                                        <p class="text-slate-800">
                                            <span class="font-semibold">Nombre:</span>
                                            {{ $name ?: 'Sin definir' }}
                                        </p>
                                        <p class="text-slate-800">
                                            <span class="font-semibold">Rol:</span>
                                            {{ $role ? \Illuminate\Support\Str::title(str_replace('_', ' ', $role)) : 'Sin definir' }}
                                        </p>
                                        <p class="text-slate-800">
                                            <span class="font-semibold">Colegio:</span>
                                            {{ optional($schools->firstWhere('id', $school_id))->name ?: 'Sin asignar' }}
                                        </p>
                                        <p class="text-slate-800">
                                            <span class="font-semibold">Estado:</span>
                                            {{ $is_active ? 'Activo' : 'Inactivo' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                            <button
                                type="button"
                                wire:click="closeModal"
                                class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                            >
                                Cancelar
                            </button>

                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-2xl px-5 py-3 text-sm font-semibold text-white shadow transition hover:opacity-90"
                                style="background-color: var(--school-primary);"
                            >
                                {{ $isEditing ? 'Guardar cambios' : 'Crear usuario' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>