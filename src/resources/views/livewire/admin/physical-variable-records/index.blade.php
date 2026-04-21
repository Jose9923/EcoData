<div class="space-y-6">
    {{-- Header --}}
    <div class="overflow-hidden rounded-3xl shadow-lg">
        <section class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-700 p-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-sm font-medium uppercase tracking-[0.2em] text-slate-300">
                        Consulta
                    </p>
                    <h1 class="mt-1 text-3xl font-bold text-white">
                        Registros de variables físicas
                    </h1>
                    <p class="mt-2 max-w-2xl text-sm text-slate-300">
                        Consulta mediciones registradas por colegio, grado, curso, categoría, variable y rango de fechas.
                    </p>
                </div>

                @if (Route::has('admin.physical-variable-records.create'))
                    <a
                        href="{{ route('admin.physical-variable-records.create') }}"
                        class="inline-flex items-center justify-center rounded-2xl px-5 py-3 text-sm font-semibold text-white shadow-md transition hover:opacity-90"
                        style="background-color: var(--school-primary);"
                    >
                        + Nuevo registro
                    </a>
                @endif
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

    {{-- Filters --}}
    <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-4 xl:grid-cols-4">
            <div class="xl:col-span-2">
                <label for="record-search" class="mb-2 block text-sm font-semibold text-slate-700">
                    Buscar registro
                </label>

                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.5 3a5.5 5.5 0 104.22 9.03l3.62 3.62a.75.75 0 101.06-1.06l-3.62-3.62A5.5 5.5 0 008.5 3zm-4 5.5a4 4 0 118 0 4 4 0 01-8 0z" clip-rule="evenodd" />
                        </svg>
                    </span>

                    <input
                        id="record-search"
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Buscar por observaciones, usuario, colegio, grado, curso o variable..."
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-11 pr-4 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                    >
                </div>
            </div>

            <div>
                <label for="date-from" class="mb-2 block text-sm font-semibold text-slate-700">
                    Fecha desde
                </label>
                <input
                    id="date-from"
                    type="date"
                    wire:model.live="date_from"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                >
            </div>

            <div>
                <label for="date-to" class="mb-2 block text-sm font-semibold text-slate-700">
                    Fecha hasta
                </label>
                <input
                    id="date-to"
                    type="date"
                    wire:model.live="date_to"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                >
            </div>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">
                    Colegio
                </label>
                <select
                    wire:model.live="school_id"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                >
                    <option value="">Todos</option>
                    @foreach ($schools as $school)
                        <option value="{{ $school->id }}">{{ $school->name }}</option>
                    @endforeach
                </select>
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
                    <option value="">Todos</option>
                    @foreach ($grades as $grade)
                        <option value="{{ $grade->id }}">{{ $grade->label ?: $grade->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">
                    Curso
                </label>
                <select
                    wire:model.live="course_id"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                    @if (! $school_id) disabled @endif
                >
                    <option value="">Todos</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}">{{ $course->label ?: $course->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">
                    Categoría
                </label>
                <select
                    wire:model.live="category_id"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                >
                    <option value="">Todas</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">
                    Variable
                </label>
                <select
                    wire:model.live="variable_id"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200"
                >
                    <option value="">Todas</option>
                    @foreach ($variables as $variable)
                        <option value="{{ $variable->id }}">{{ $variable->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">
                    Registros por página
                </label>
                <select
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

        <div class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-500">
                Mostrando {{ $records->firstItem() ?? 0 }} - {{ $records->lastItem() ?? 0 }} de {{ $records->total() }} registros
            </p>

            <div class="flex flex-col gap-3 sm:flex-row">
                <button
                    type="button"
                    wire:click="resetFilters"
                    class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    Limpiar filtros
                </button>

                <button
                    type="button"
                    wire:click="export"
                    wire:loading.attr="disabled"
                    wire:target="export"
                    class="inline-flex items-center justify-center rounded-2xl px-4 py-2.5 text-sm font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                    style="background-color: var(--school-primary);"
                >
                    <span wire:loading.remove wire:target="export">Exportar Excel</span>
                    <span wire:loading wire:target="export">Exportando...</span>
                </button>
            </div>
        </div>
    </section>

    {{-- Table --}}
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-100/80">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                            Fecha
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                            Contexto
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                            Registrado por
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                            Variables capturadas
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                            Observaciones
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($records as $record)
                        @php
                            $gradeLabel = $record->grade?->label ?: $record->grade?->name;
                            $courseLabel = $record->course?->label ?: $record->course?->name;
                            $previewValues = $record->values->take(3);
                            $remainingValues = max($record->values_count - $previewValues->count(), 0);
                        @endphp

                        <tr class="align-top transition hover:bg-slate-50/70">
                            <td class="px-6 py-4">
                                <div class="space-y-1">
                                    <p class="text-sm font-semibold text-slate-900">
                                        {{ optional($record->recorded_at)->format('d/m/Y') }}
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        {{ optional($record->recorded_at)->format('H:i') }}
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        ID: {{ $record->id }}
                                    </p>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="space-y-1 text-sm">
                                    <p class="font-semibold text-slate-800">
                                        {{ $record->school?->name ?: 'Sin colegio' }}
                                    </p>
                                    <p class="text-slate-600">
                                        <span class="font-medium">Grado:</span>
                                        {{ $gradeLabel ?: 'Sin asignar' }}
                                    </p>
                                    <p class="text-slate-600">
                                        <span class="font-medium">Curso:</span>
                                        {{ $courseLabel ?: 'Sin asignar' }}
                                    </p>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="space-y-1">
                                    <p class="text-sm font-semibold text-slate-800">
                                        {{ $record->user?->name ?: 'Sistema / Sin usuario' }}
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        {{ $record->user?->email ?: 'Sin correo' }}
                                    </p>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                @if ($record->values->isNotEmpty())
                                    <div class="space-y-2">
                                        @foreach ($previewValues as $value)
                                            @php
                                                $variable = $value->variable;
                                                $displayValue = $value->resolved_value;

                                                if ($variable?->data_type === 'boolean') {
                                                    $displayValue = $displayValue === true ? 'Sí' : ($displayValue === false ? 'No' : '—');
                                                } elseif ($variable?->data_type === 'date' && $displayValue) {
                                                    $displayValue = \Illuminate\Support\Carbon::parse($displayValue)->format('d/m/Y');
                                                } elseif ($displayValue !== null && $variable?->unit) {
                                                    $displayValue = $displayValue . ' ' . $variable->unit;
                                                } elseif ($displayValue === null || $displayValue === '') {
                                                    $displayValue = '—';
                                                }
                                            @endphp

                                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    {{ $variable?->category?->name ?: 'Sin categoría' }}
                                                </p>
                                                <p class="mt-1 text-sm font-semibold text-slate-800">
                                                    {{ $variable?->name ?: 'Variable' }}
                                                </p>
                                                <p class="mt-1 text-sm text-slate-600">
                                                    {{ $displayValue }}
                                                </p>
                                            </div>
                                        @endforeach

                                        @if ($remainingValues > 0)
                                            <p class="text-xs font-medium text-slate-500">
                                                + {{ $remainingValues }} variable{{ $remainingValues === 1 ? '' : 's' }} adicional{{ $remainingValues === 1 ? '' : 'es' }}
                                            </p>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-sm text-slate-400">Sin valores capturados</span>
                                @endif
                            </td>

                            <td class="px-6 py-4">
                                @if ($record->observations)
                                    <p class="max-w-sm text-sm text-slate-700">
                                        {{ \Illuminate\Support\Str::limit($record->observations, 140) }}
                                    </p>
                                @else
                                    <span class="text-sm text-slate-400">Sin observaciones</span>
                                @endif
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

                                    <h3 class="mt-4 text-base font-semibold text-slate-900">
                                        {{ $search !== '' || $school_id || $grade_id || $course_id || $category_id || $variable_id || $date_from || $date_to ? 'No se encontraron resultados' : 'No hay registros físicos' }}
                                    </h3>

                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ $search !== '' || $school_id || $grade_id || $course_id || $category_id || $variable_id || $date_from || $date_to
                                            ? 'Ajusta los filtros o limpia la búsqueda para ver más resultados.'
                                            : 'Aún no se han registrado mediciones en el sistema.' }}
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($records->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $records->links() }}
            </div>
        @endif
    </section>
</div>