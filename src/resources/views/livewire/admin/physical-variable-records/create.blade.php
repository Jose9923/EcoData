<div class="space-y-6">
    {{-- Header --}}
    <div class="overflow-hidden rounded-3xl shadow-lg">
        <section class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-700 p-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-sm font-medium uppercase tracking-[0.2em] text-slate-300">
                        Registro
                    </p>
                    <h1 class="mt-1 text-3xl font-bold text-white">
                        Captura de variables físicas
                    </h1>
                    <p class="mt-2 max-w-2xl text-sm text-slate-300">
                        Registra mediciones y observaciones usando las variables físicas activas parametrizadas por colegio.
                    </p>
                </div>

                <div class="rounded-2xl bg-white/10 px-4 py-3 text-sm text-white/90 ring-1 ring-white/10">
                    {{ now()->format('d/m/Y') }}
                </div>
            </div>
        </section>
    </div>

    {{-- Flash messages --}}
    @if (session()->has('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->has('values'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 shadow-sm">
            {{ $errors->first('values') }}
        </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-6">
        {{-- Header form --}}
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-6 flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Datos del registro</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Define el contexto académico y la fecha de captura antes de diligenciar las variables.
                    </p>
                </div>

                <div class="hidden rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600 sm:block">
                    Variables cargadas:
                    <span class="font-semibold text-slate-900">{{ $variables->count() }}</span>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Colegio <span class="text-rose-500">*</span>
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
                        Grado
                    </label>
                    <select
                        wire:model.live="grade_id"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                        @if (! $school_id) disabled @endif
                    >
                        <option value="">Seleccionar...</option>
                        @foreach ($grades as $grade)
                            <option value="{{ $grade->id }}">{{ $grade->label ?: $grade->name }}</option>
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
                        @if (! $school_id) disabled @endif
                    >
                        <option value="">Seleccionar...</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}">{{ $course->label ?: $course->name }}</option>
                        @endforeach
                    </select>
                    @error('course_id')
                        <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Categoría
                    </label>
                    <select
                        wire:model.live="category_id"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                        @if (! $school_id) disabled @endif
                    >
                        <option value="">Todas</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Fecha y hora del registro <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="datetime-local"
                        wire:model.defer="recorded_at"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                    >
                    @error('recorded_at')
                        <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <h3 class="text-sm font-semibold text-slate-700">Resumen</h3>

                    <div class="mt-4 space-y-2 text-sm">
                        <p class="text-slate-800">
                            <span class="font-semibold">Colegio:</span>
                            {{ optional($schools->firstWhere('id', $school_id))->name ?: 'Sin asignar' }}
                        </p>
                        <p class="text-slate-800">
                            <span class="font-semibold">Grado:</span>
                            {{ optional($grades->firstWhere('id', $grade_id))->label ?: optional($grades->firstWhere('id', $grade_id))->name ?: 'Sin asignar' }}
                        </p>
                        <p class="text-slate-800">
                            <span class="font-semibold">Curso:</span>
                            {{ optional($courses->firstWhere('id', $course_id))->label ?: optional($courses->firstWhere('id', $course_id))->name ?: 'Sin asignar' }}
                        </p>
                        <p class="text-slate-800">
                            <span class="font-semibold">Categoría:</span>
                            {{ optional($categories->firstWhere('id', $category_id))->name ?: 'Todas' }}
                        </p>
                        <p class="text-slate-800">
                            <span class="font-semibold">Variables visibles:</span>
                            {{ $variables->count() }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <label class="mb-2 block text-sm font-semibold text-slate-700">
                    Observaciones generales
                </label>
                <textarea
                    wire:model.defer="observations"
                    rows="4"
                    placeholder="Añade observaciones del contexto, condiciones de la medición o incidencias del registro..."
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                ></textarea>
                @error('observations')
                    <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </section>

        {{-- Variables --}}
        @if (! $school_id)
            <section class="rounded-3xl border border-slate-200 bg-white px-6 py-14 text-center shadow-sm">
                <div class="mx-auto max-w-md">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>

                    <h3 class="mt-4 text-base font-semibold text-slate-900">
                        Selecciona un colegio para continuar
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Las variables físicas se cargan según el colegio seleccionado y, opcionalmente, por categoría.
                    </p>
                </div>
            </section>
        @elseif ($variables->isEmpty())
            <section class="rounded-3xl border border-slate-200 bg-white px-6 py-14 text-center shadow-sm">
                <div class="mx-auto max-w-md">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 12h6m-6 4h6M7 4h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z" />
                        </svg>
                    </div>

                    <h3 class="mt-4 text-base font-semibold text-slate-900">
                        No hay variables disponibles
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">
                        No se encontraron variables físicas activas para el colegio y categoría seleccionados.
                    </p>
                </div>
            </section>
        @else
            @php
                $groupedVariables = $variables->groupBy(fn ($variable) => $variable->category?->name ?? 'Sin categoría');
            @endphp

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-6">
                    <h2 class="text-lg font-bold text-slate-900">Variables del registro</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Diligencia solo las variables que correspondan al registro. Debes completar al menos una.
                    </p>
                </div>

                <div class="space-y-6">
                    @foreach ($groupedVariables as $categoryName => $categoryVariables)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <div class="mb-4 flex items-center justify-between gap-4">
                                <div>
                                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">
                                        {{ $categoryName }}
                                    </h3>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $categoryVariables->count() }} variable{{ $categoryVariables->count() === 1 ? '' : 's' }}
                                    </p>
                                </div>
                            </div>

                            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                                @foreach ($categoryVariables as $variable)
                                    @php
                                        $fieldKey = 'values.' . $variable->id;
                                        $step = $variable->data_type === 'integer'
                                            ? '1'
                                            : ($variable->decimals > 0 ? '0.' . str_repeat('0', max($variable->decimals - 1, 0)) . '1' : '1');
                                    @endphp

                                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                        <div class="mb-3">
                                            <label class="block text-sm font-semibold text-slate-800">
                                                {{ $variable->name }}
                                            </label>
                                            <div class="mt-1 flex flex-wrap gap-2 text-xs text-slate-500">
                                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 font-medium text-slate-600">
                                                    {{ $variable->data_type }}
                                                </span>

                                                @if ($variable->unit)
                                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 font-medium text-slate-600">
                                                        {{ $variable->unit }}
                                                    </span>
                                                @endif

                                                @if ($variable->min_value !== null || $variable->max_value !== null)
                                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 font-medium text-slate-600">
                                                        Rango:
                                                        {{ $variable->min_value !== null ? $variable->min_value : '—' }}
                                                        -
                                                        {{ $variable->max_value !== null ? $variable->max_value : '—' }}
                                                    </span>
                                                @endif
                                            </div>

                                            @if ($variable->description)
                                                <p class="mt-2 text-xs text-slate-500">
                                                    {{ $variable->description }}
                                                </p>
                                            @endif
                                        </div>

                                        @if ($variable->data_type === 'integer')
                                            <input
                                                type="number"
                                                step="1"
                                                wire:model.defer="values.{{ $variable->id }}"
                                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                                                placeholder="Ingresa un valor entero"
                                            >
                                        @elseif ($variable->data_type === 'decimal')
                                            <input
                                                type="number"
                                                step="{{ $step }}"
                                                wire:model.defer="values.{{ $variable->id }}"
                                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                                                placeholder="Ingresa un valor decimal"
                                            >
                                        @elseif ($variable->data_type === 'text')
                                            <textarea
                                                wire:model.defer="values.{{ $variable->id }}"
                                                rows="3"
                                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                                                placeholder="Ingresa una observación o texto"
                                            ></textarea>
                                        @elseif ($variable->data_type === 'boolean')
                                            <select
                                                wire:model.defer="values.{{ $variable->id }}"
                                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                                            >
                                                <option value="">Seleccionar...</option>
                                                <option value="1">Sí</option>
                                                <option value="0">No</option>
                                            </select>
                                        @elseif ($variable->data_type === 'date')
                                            <input
                                                type="date"
                                                wire:model.defer="values.{{ $variable->id }}"
                                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                                            >
                                        @endif

                                        @error($fieldKey)
                                            <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-2xl px-5 py-3 text-sm font-semibold text-white shadow transition hover:opacity-90"
                style="background-color: var(--school-primary);"
            >
                Guardar registro
            </button>
        </div>
    </form>
</div>