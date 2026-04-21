<div class="min-h-screen bg-slate-50">
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
                            Colegios
                        </h1>
                        <p class="mt-2 max-w-2xl text-sm text-slate-300">
                            Gestiona instituciones, branding y estado general del sistema desde un solo módulo.
                        </p>
                    </div>

                    <button
                        type="button"
                        wire:click="create"
                        class="inline-flex items-center justify-center rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-slate-900 shadow-md transition hover:-translate-y-0.5 hover:shadow-lg"
                    >
                        + Nuevo colegio
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
                    <label for="school-search" class="mb-2 block text-sm font-semibold text-slate-700">
                        Buscar colegio
                    </label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.5 3a5.5 5.5 0 104.22 9.03l3.62 3.62a.75.75 0 101.06-1.06l-3.62-3.62A5.5 5.5 0 008.5 3zm-4 5.5a4 4 0 118 0 4 4 0 01-8 0z" clip-rule="evenodd" />
                            </svg>
                        </span>

                        <input
                            id="school-search"
                            type="text"
                            wire:model.live.debounce.400ms="search"
                            placeholder="Buscar por nombre o slug..."
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-11 pr-4 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
                        >
                    </div>
                </div>

                <div class="w-full lg:w-44">
                    <label for="per-page" class="mb-2 block text-sm font-semibold text-slate-700">
                        Registros por página
                    </label>
                    <select
                        id="per-page"
                        wire:model.live="perPage"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
                    >
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>
        </section>

        {{-- Table --}}
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-100/80">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Colegio</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Slug</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Branding</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Estado</th>
                            <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($schools as $school)
                            <tr class="transition hover:bg-slate-50/70">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-4">
                                        <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
                                            @if ($school->shield_path)
                                                <img
                                                    src="{{ asset('storage/' . $school->shield_path) }}"
                                                    alt="Escudo {{ $school->name }}"
                                                    class="h-full w-full object-cover"
                                                >
                                            @else
                                                <span class="text-lg font-bold text-slate-500">
                                                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($school->name, 0, 1)) }}
                                                </span>
                                            @endif
                                        </div>

                                        <div>
                                            <p class="text-sm font-semibold text-slate-900">
                                                {{ $school->name }}
                                            </p>
                                            <p class="text-xs text-slate-500">
                                                ID: {{ $school->id }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="inline-flex rounded-xl bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                                        {{ $school->slug ?: 'Sin slug' }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="h-7 w-7 rounded-full border border-white shadow" style="background-color: {{ $school->primary_color }}"></span>
                                        <span class="h-7 w-7 rounded-full border border-white shadow" style="background-color: {{ $school->secondary_color }}"></span>
                                        <span class="h-7 w-7 rounded-full border border-white shadow" style="background-color: {{ $school->accent_color }}"></span>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    @if ($school->is_active)
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
                                            wire:click="edit({{ $school->id }})"
                                            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700"
                                        >
                                            Editar
                                        </button>

                                        <button
                                            type="button"
                                            onclick="confirm('¿Seguro que deseas eliminar este colegio?') || event.stopImmediatePropagation()"
                                            wire:click="delete({{ $school->id }})"
                                            class="rounded-xl border border-rose-200 bg-white px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50"
                                        >
                                            Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-14 text-center">
                                    <div class="mx-auto max-w-md">
                                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 17v-6m3 6V7m3 10v-4m5 7H4a2 2 0 01-2-2V6a2 2 0 012-2h5.172a2 2 0 011.414.586l1.828 1.828A2 2 0 0013.828 7H20a2 2 0 012 2v9a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <h3 class="mt-4 text-base font-semibold text-slate-900">No hay colegios registrados</h3>
                                        <p class="mt-1 text-sm text-slate-500">
                                            Crea el primer colegio para comenzar a parametrizar el sistema.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($schools->hasPages())
                <div class="border-t border-slate-200 px-6 py-4">
                    {{ $schools->links() }}
                </div>
            @endif
        </section>
    </div>

    {{-- Modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center px-4 py-8">
                <div
                    class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                    wire:click="closeModal"
                ></div>

                <div class="relative z-10 w-full max-w-4xl rounded-3xl bg-white shadow-2xl">
                    <div class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
                        <div>
                            <h2 class="text-xl font-bold text-slate-900">
                                {{ $isEditing ? 'Editar colegio' : 'Nuevo colegio' }}
                            </h2>
                            <p class="mt-1 text-sm text-slate-500">
                                Configura datos básicos y colores institucionales.
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
                                    Nombre del colegio <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    wire:model.defer="name"
                                    placeholder="Ej: Institución Educativa Luis Carlos Galán"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
                                >
                                @error('name')
                                    <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Slug
                                </label>
                                <input
                                    type="text"
                                    wire:model.defer="slug"
                                    placeholder="Ej: luis-carlos-galan"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
                                >
                                @error('slug')
                                    <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid gap-6 md:grid-cols-3">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Color primario <span class="text-rose-500">*</span>
                                </label>
                                <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2">
                                    <input type="color" wire:model.defer="primary_color" class="h-10 w-12 cursor-pointer rounded-lg border-0 bg-transparent p-0">
                                    <input
                                        type="text"
                                        wire:model.defer="primary_color"
                                        class="w-full border-0 bg-transparent text-sm text-slate-800 outline-none focus:ring-0"
                                    >
                                </div>
                                @error('primary_color')
                                    <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Color secundario <span class="text-rose-500">*</span>
                                </label>
                                <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2">
                                    <input type="color" wire:model.defer="secondary_color" class="h-10 w-12 cursor-pointer rounded-lg border-0 bg-transparent p-0">
                                    <input
                                        type="text"
                                        wire:model.defer="secondary_color"
                                        class="w-full border-0 bg-transparent text-sm text-slate-800 outline-none focus:ring-0"
                                    >
                                </div>
                                @error('secondary_color')
                                    <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Color acento <span class="text-rose-500">*</span>
                                </label>
                                <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2">
                                    <input type="color" wire:model.defer="accent_color" class="h-10 w-12 cursor-pointer rounded-lg border-0 bg-transparent p-0">
                                    <input
                                        type="text"
                                        wire:model.defer="accent_color"
                                        class="w-full border-0 bg-transparent text-sm text-slate-800 outline-none focus:ring-0"
                                    >
                                </div>
                                @error('accent_color')
                                    <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid gap-6 md:grid-cols-[1.2fr_0.8fr]">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Escudo institucional
                                </label>
                                <label class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 px-6 py-8 text-center transition hover:border-blue-400 hover:bg-blue-50/40">
                                    <input type="file" wire:model="shield" class="hidden" accept="image/*">

                                    <div class="space-y-2">
                                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-white text-slate-400 shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999A5.002 5.002 0 006 9a4 4 0 00-3 6z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 12v6m0-6l-3 3m3-3l3 3" />
                                            </svg>
                                        </div>

                                        <div>
                                            <p class="text-sm font-semibold text-slate-700">
                                                Haz clic para subir una imagen
                                            </p>
                                            <p class="text-xs text-slate-500">
                                                PNG, JPG o JPEG · Máximo 2 MB
                                            </p>
                                        </div>
                                    </div>
                                </label>

                                <div wire:loading wire:target="shield" class="mt-2 text-xs font-medium text-blue-600">
                                    Cargando imagen...
                                </div>

                                @error('shield')
                                    <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="mb-3 text-sm font-semibold text-slate-700">Vista previa</p>

                                <div
                                    class="rounded-2xl p-5 text-white shadow-sm"
                                    style="background: linear-gradient(135deg, {{ $primary_color }}, {{ $secondary_color }});"
                                >
                                    <div class="flex items-center gap-4">
                                        <div class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-2xl bg-white/15 ring-1 ring-white/20">
                                            @if ($shield)
                                                <img src="{{ $shield->temporaryUrl() }}" alt="Preview escudo" class="h-full w-full object-cover">
                                            @else
                                                <span class="text-lg font-bold">ESC</span>
                                            @endif
                                        </div>

                                        <div class="min-w-0">
                                            <p class="truncate text-base font-bold">
                                                {{ $name ?: 'Nombre del colegio' }}
                                            </p>
                                            <p class="mt-1 text-xs text-white/80">
                                                {{ $slug ?: 'slug-del-colegio' }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-4 flex gap-2">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" style="background-color: {{ $accent_color }}; color: #ffffff;">
                                            Acento
                                        </span>

                                        <span class="inline-flex rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white">
                                            {{ $is_active ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <label class="inline-flex items-center gap-3">
                                        <input
                                            type="checkbox"
                                            wire:model.defer="is_active"
                                            class="h-5 w-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                        >
                                        <span class="text-sm font-medium text-slate-700">Colegio activo</span>
                                    </label>

                                    @error('is_active')
                                        <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                                    @enderror
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
                                class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow transition hover:bg-slate-800"
                            >
                                {{ $isEditing ? 'Guardar cambios' : 'Crear colegio' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>