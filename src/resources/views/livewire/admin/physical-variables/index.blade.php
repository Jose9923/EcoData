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
                        Variables físicas
                    </h1>
                    <p class="mt-2 max-w-2xl text-sm text-slate-300">
                        Parametriza las variables físicas por colegio, categoría, tipo de dato y reglas de validación.
                    </p>
                </div>

                <button
                    type="button"
                    wire:click="create"
                    class="inline-flex items-center justify-center rounded-2xl px-5 py-3 text-sm font-semibold text-white shadow-md transition hover:opacity-90"
                    style="background-color: var(--school-primary);"
                >
                    + Nueva variable
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
                <label for="physical-variable-search" class="mb-2 block text-sm font-semibold text-slate-700">
                    Buscar variable
                </label>

                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.5 3a5.5 5.5 0 104.22 9.03l3.62 3.62a.75.75 0 101.06-1.06l-3.62-3.62A5.5 5.5 0 008.5 3zm-4 5.5a4 4 0 118 0 4 4 0 01-8 0z" clip-rule="evenodd" />
                        </svg>
                    </span>

                    <input
                        id="physical-variable-search"
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Buscar por nombre, slug, unidad, tipo, categoría o colegio..."
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
                Mostrando {{ $variables->firstItem() ?? 0 }} - {{ $variables->lastItem() ?? 0 }} de {{ $variables->total() }} variables
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
                            Variable
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                            Colegio
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                            Categoría
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                            Tipo / Unidad
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                            Reglas
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
                    @forelse ($variables as $variable)
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl border border-slate-200 bg-slate-100">
                                        <span class="text-xs font-bold uppercase text-slate-600">
                                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($variable->name, 0, 3)) }}
                                        </span>
                                    </div>

                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-900">
                                            {{ $variable->name }}
                                        </p>
                                        <div class="mt-1 flex flex-wrap gap-2">
                                            <span class="inline-flex rounded-xl bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-700">
                                                {{ $variable->slug }}
                                            </span>

                                            @if ($variable->description)
                                                <span class="truncate text-xs text-slate-500">
                                                    {{ \Illuminate\Support\Str::limit($variable->description, 60) }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                @if ($variable->school)
                                    <p class="max-w-[220px] truncate text-sm font-medium text-slate-800">
                                        {{ $variable->school->name }}
                                    </p>
                                @else
                                    <span class="text-sm text-slate-400">Sin colegio</span>
                                @endif
                            </td>

                            <td class="px-6 py-4">
                                @if ($variable->category)
                                    <span class="inline-flex rounded-xl bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                                        {{ $variable->category->name }}
                                    </span>
                                @else
                                    <span class="text-sm text-slate-400">Sin categoría</span>
                                @endif
                            </td>

                            <td class="px-6 py-4">
                                <div class="space-y-1">
                                    <p class="text-sm font-medium text-slate-800">
                                        {{ $variable->data_type }}
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        Unidad: {{ $variable->unit ?: 'No aplica' }}
                                    </p>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="space-y-1 text-sm text-slate-700">
                                    <p>
                                        <span class="font-semibold">Mín:</span>
                                        {{ $variable->min_value !== null ? $variable->min_value : '—' }}
                                    </p>
                                    <p>
                                        <span class="font-semibold">Máx:</span>
                                        {{ $variable->max_value !== null ? $variable->max_value : '—' }}
                                    </p>
                                    <p>
                                        <span class="font-semibold">Decimales:</span>
                                        {{ $variable->decimals }}
                                    </p>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                @if ($variable->is_active)
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
                                        wire:click="edit({{ $variable->id }})"
                                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                    >
                                        Editar
                                    </button>

                                    <button
                                        type="button"
                                        onclick="confirm('¿Seguro que deseas eliminar esta variable física?') || event.stopImmediatePropagation()"
                                        wire:click="delete({{ $variable->id }})"
                                        class="rounded-xl border border-rose-200 bg-white px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50"
                                    >
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-14 text-center">
                                <div class="mx-auto max-w-md">
                                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M13 2 4 14h7l-1 8 9-12h-7l1-8z" />
                                        </svg>
                                    </div>

                                    <h3 class="mt-4 text-base font-semibold text-slate-900">
                                        {{ $search !== '' ? 'No se encontraron resultados' : 'No hay variables físicas registradas' }}
                                    </h3>

                                    <p class="mt-1 text-sm text-slate-500">
                                        @if ($search !== '')
                                            Ajusta el término de búsqueda o limpia el filtro para ver todas las variables físicas.
                                        @else
                                            Crea la primera variable para comenzar la parametrización del módulo.
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($variables->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $variables->links() }}
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
                                {{ $isEditing ? 'Editar variable física' : 'Nueva variable física' }}
                            </h2>
                            <p class="mt-1 text-sm text-slate-500">
                                Define colegio, categoría, tipo de dato y reglas de validación para la variable.
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
                        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Colegio <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    wire:model.defer="school_id"
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
                                    Categoría <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    wire:model.defer="category_id"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                                >
                                    <option value="">Seleccionar...</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Tipo de dato <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    wire:model.live="data_type"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                                >
                                    @foreach ($dataTypes as $type)
                                        <option value="{{ $type }}">{{ $type }}</option>
                                    @endforeach
                                </select>
                                @error('data_type')
                                    <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Estado
                                </label>

                                <div class="flex h-[50px] items-center rounded-2xl border border-slate-200 bg-slate-50 px-4">
                                    <label class="inline-flex items-center gap-3">
                                        <input
                                            type="checkbox"
                                            wire:model.defer="is_active"
                                            class="h-5 w-5 rounded border-slate-300"
                                            style="accent-color: var(--school-primary);"
                                        >
                                        <span class="text-sm font-medium text-slate-700">
                                            Variable activa
                                        </span>
                                    </label>
                                </div>

                                @error('is_active')
                                    <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Nombre <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    wire:model.defer="name"
                                    placeholder="Ej: Temperatura"
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
                                    placeholder="Ej: temperatura"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                                >
                                @error('slug')
                                    <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Unidad
                                </label>
                                <input
                                    type="text"
                                    wire:model.defer="unit"
                                    placeholder="Ej: °C"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                                >
                                @error('unit')
                                    <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Decimales
                                </label>
                                <input
                                    type="number"
                                    min="0"
                                    max="6"
                                    wire:model.defer="decimals"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                                    @if(in_array($data_type, ['text', 'boolean', 'date', 'integer'], true)) disabled @endif
                                >
                                @error('decimals')
                                    <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Valor mínimo
                                </label>
                                <input
                                    type="number"
                                    step="any"
                                    wire:model.defer="min_value"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                                    @if(in_array($data_type, ['text', 'boolean', 'date'], true)) disabled @endif
                                >
                                @error('min_value')
                                    <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Valor máximo
                                </label>
                                <input
                                    type="number"
                                    step="any"
                                    wire:model.defer="max_value"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                                    @if(in_array($data_type, ['text', 'boolean', 'date'], true)) disabled @endif
                                >
                                @error('max_value')
                                    <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Descripción
                                </label>
                                <textarea
                                    wire:model.defer="description"
                                    rows="3"
                                    placeholder="Describe el propósito o alcance de la variable..."
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                                ></textarea>
                                @error('description')
                                    <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                <h3 class="text-sm font-semibold text-slate-700">
                                    Consideraciones de parametrización
                                </h3>

                                <div class="mt-4 space-y-3 text-sm text-slate-600">
                                    <p>
                                        El slug debe ser único por colegio para evitar conflictos en formularios y reportes.
                                    </p>
                                    <p>
                                        Los tipos <span class="font-semibold">text</span>, <span class="font-semibold">boolean</span> y <span class="font-semibold">date</span> no utilizan mínimo, máximo ni decimales.
                                    </p>
                                    <p>
                                        Para variables <span class="font-semibold">integer</span>, los decimales se fuerzan a cero.
                                    </p>
                                    <p>
                                        Usa límites mínimos y máximos solo cuando realmente aporten validación al dato registrado.
                                    </p>
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
                                            <span class="font-semibold">Variable:</span>
                                            {{ $name ?: 'Sin definir' }}
                                        </p>
                                        <p class="text-slate-800">
                                            <span class="font-semibold">Slug:</span>
                                            {{ $slug ?: 'Se generará automáticamente' }}
                                        </p>
                                        <p class="text-slate-800">
                                            <span class="font-semibold">Tipo:</span>
                                            {{ $data_type }}
                                        </p>
                                        <p class="text-slate-800">
                                            <span class="font-semibold">Unidad:</span>
                                            {{ $unit ?: 'No aplica' }}
                                        </p>
                                        <p class="text-slate-800">
                                            <span class="font-semibold">Colegio:</span>
                                            {{ optional($schools->firstWhere('id', $school_id))->name ?: 'Sin asignar' }}
                                        </p>
                                        <p class="text-slate-800">
                                            <span class="font-semibold">Categoría:</span>
                                            {{ optional($categories->firstWhere('id', $category_id))->name ?: 'Sin asignar' }}
                                        </p>
                                        <p class="text-slate-800">
                                            <span class="font-semibold">Estado:</span>
                                            {{ $is_active ? 'Activa' : 'Inactiva' }}
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
                                {{ $isEditing ? 'Guardar cambios' : 'Crear variable' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>