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
                        Categorías de variables físicas
                    </h1>
                    <p class="mt-2 max-w-2xl text-sm text-slate-300">
                        Organiza el catálogo de variables físicas por categorías para facilitar la parametrización,
                        captura y consulta de datos.
                    </p>
                </div>

                <button
                    type="button"
                    wire:click="create"
                    class="inline-flex items-center justify-center rounded-2xl px-5 py-3 text-sm font-semibold text-white shadow-md transition hover:opacity-90"
                    style="background-color: var(--school-primary);"
                >
                    + Nueva categoría
                </button>
            </div>
        </section>
    </div>

    {{-- Flash messages --}}
    @if (session()->has('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- Toolbar --}}
    <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-4 lg:grid-cols-[1fr_auto]">
            <div>
                <label for="category-search" class="mb-2 block text-sm font-semibold text-slate-700">
                    Buscar categoría
                </label>

                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.5 3a5.5 5.5 0 104.22 9.03l3.62 3.62a.75.75 0 101.06-1.06l-3.62-3.62A5.5 5.5 0 008.5 3zm-4 5.5a4 4 0 118 0 4 4 0 01-8 0z" clip-rule="evenodd" />
                        </svg>
                    </span>

                    <input
                        id="category-search"
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Buscar por nombre, slug o descripción..."
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
                Mostrando {{ $categories->firstItem() ?? 0 }} - {{ $categories->lastItem() ?? 0 }} de {{ $categories->total() }} categorías
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
                            Categoría
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                            Slug
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                            Descripción
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                            Variables asociadas
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
                    @forelse ($categories as $category)
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl border border-slate-200 bg-slate-100">
                                        <span class="text-sm font-bold text-slate-600">
                                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($category->name, 0, 2)) }}
                                        </span>
                                    </div>

                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-900">
                                            {{ $category->name }}
                                        </p>
                                        <p class="text-xs text-slate-500">
                                            ID: {{ $category->id }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-xl bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                                    {{ $category->slug }}
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                @if ($category->description)
                                    <p class="max-w-sm text-sm text-slate-700">
                                        {{ \Illuminate\Support\Str::limit($category->description, 110) }}
                                    </p>
                                @else
                                    <span class="text-sm text-slate-400">Sin descripción</span>
                                @endif
                            </td>

                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                    {{ $category->variables_count }} variable{{ $category->variables_count === 1 ? '' : 's' }}
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                @if ($category->is_active)
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                        Activa
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-700">
                                        Inactiva
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        type="button"
                                        wire:click="edit({{ $category->id }})"
                                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                    >
                                        Editar
                                    </button>

                                    <button
                                        type="button"
                                        onclick="confirm('¿Seguro que deseas eliminar esta categoría?') || event.stopImmediatePropagation()"
                                        wire:click="delete({{ $category->id }})"
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
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M19 11H5m14-4H5m14 8H5m14 4H5" />
                                        </svg>
                                    </div>

                                    <h3 class="mt-4 text-base font-semibold text-slate-900">
                                        {{ $search !== '' ? 'No se encontraron resultados' : 'No hay categorías registradas' }}
                                    </h3>

                                    <p class="mt-1 text-sm text-slate-500">
                                        @if ($search !== '')
                                            Ajusta el término de búsqueda o limpia el filtro para ver todas las categorías.
                                        @else
                                            Crea la primera categoría para comenzar a organizar las variables físicas.
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($categories->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $categories->links() }}
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

                <div class="relative z-10 w-full max-w-4xl rounded-3xl bg-white shadow-2xl">
                    <div class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
                        <div>
                            <h2 class="text-xl font-bold text-slate-900">
                                {{ $isEditing ? 'Editar categoría' : 'Nueva categoría' }}
                            </h2>
                            <p class="mt-1 text-sm text-slate-500">
                                Define el nombre, slug y descripción de la categoría para organizar el catálogo.
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
                                    Nombre <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    wire:model.defer="name"
                                    placeholder="Ej: Clima"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
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
                                    placeholder="Ej: clima"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                                >
                                @error('slug')
                                    <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-2 text-xs text-slate-500">
                                    Si lo dejas vacío, se generará automáticamente a partir del nombre.
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Descripción
                                </label>
                                <textarea
                                    wire:model.defer="description"
                                    rows="6"
                                    placeholder="Describe el propósito de la categoría..."
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                                ></textarea>
                                @error('description')
                                    <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-6">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                    <h3 class="text-sm font-semibold text-slate-700">
                                        Estado
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
                                                Categoría activa
                                            </span>
                                        </label>

                                        @error('is_active')
                                            <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                    <h3 class="text-sm font-semibold text-slate-700">
                                        Vista rápida
                                    </h3>

                                    <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Resumen
                                        </p>

                                        <div class="mt-3 space-y-2 text-sm">
                                            <p class="text-slate-800">
                                                <span class="font-semibold">Nombre:</span>
                                                {{ $name ?: 'Sin definir' }}
                                            </p>
                                            <p class="text-slate-800">
                                                <span class="font-semibold">Slug:</span>
                                                {{ $slug ?: 'Se generará automáticamente' }}
                                            </p>
                                            <p class="text-slate-800">
                                                <span class="font-semibold">Estado:</span>
                                                {{ $is_active ? 'Activa' : 'Inactiva' }}
                                            </p>
                                        </div>
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
                                {{ $isEditing ? 'Guardar cambios' : 'Crear categoría' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>