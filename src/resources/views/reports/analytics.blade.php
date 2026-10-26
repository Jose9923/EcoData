@extends('layouts.app')

@section('content')
@php
    $metric = fn ($value) => $value === null ? 'Sin datos' : number_format((float) $value, min(4, max(2, $variable?->decimals ?? 2)), ',', '.');
    $selectedStation = $stations->firstWhere('id', $filters['weather_station_id'] ?? null);
@endphp
<div id="measurement-charts" class="charts-module d-flex flex-column gap-4"
     data-group-by="{{ $filters['group_by'] ?? 'day' }}" data-chart-type="{{ $filters['chart_type'] ?? 'line' }}"
     data-scope-label="{{ $selectedStation?->name ?? 'Todas las estaciones y registros sin estación' }}">
    <header class="charts-heading">
        <div>
            <div class="charts-eyebrow">Explora tus mediciones</div>
            <h1 class="h2 mb-2">Gráficos ambientales</h1>
            <p class="text-secondary mb-0">Observa tendencias, compara estaciones y descubre cómo se distribuyen los valores de una variable.</p>
        </div>
        <a href="{{ route('admin.physical-variable-records.index') }}" class="btn btn-outline-secondary">Ver mediciones</a>
    </header>

    <section class="admin-card bg-white p-3 p-md-4" aria-labelledby="chart-filters-title">
        <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
            <h2 id="chart-filters-title" class="h5 mb-0">Selecciona los datos</h2>
            <span class="small text-secondary">Solo se muestran mediciones permitidas para tu cuenta.</span>
        </div>
        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                <strong>Revisa los filtros:</strong>
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form method="GET" action="{{ route('charts.index') }}" id="chart-filters" class="row g-3">
            @include('reports.partials.filters')
            <div class="col-sm-6 col-xl-3">
                <label class="form-label" for="chart-group-by">Agrupar por</label>
                <select id="chart-group-by" name="group_by" class="form-select" data-searchable-select="false">
                    <option value="day" @selected(($filters['group_by'] ?? 'day') === 'day')>Día</option>
                    <option value="week" @selected(($filters['group_by'] ?? 'day') === 'week')>Semana</option>
                    <option value="month" @selected(($filters['group_by'] ?? 'day') === 'month')>Mes</option>
                </select>
            </div>
            <div class="col-sm-6 col-xl-3">
                <label class="form-label" for="chart-type">Tipo de gráfico de evolución</label>
                <select id="chart-type" name="chart_type" class="form-select" data-searchable-select="false">
                    <option value="line" @selected(($filters['chart_type'] ?? 'line') === 'line')>Líneas</option>
                    <option value="bar" @selected(($filters['chart_type'] ?? 'line') === 'bar')>Barras</option>
                </select>
            </div>
            <div class="col-xl-6 charts-filter-actions">
                <button type="submit" class="btn btn-primary">Actualizar gráficos</button>
                <a href="{{ route('charts.index') }}" class="btn btn-outline-secondary">Limpiar filtros</a>
                @if($variable)
                    <a href="{{ route('reports.mail', \Illuminate\Support\Arr::only($filters, ['date_from', 'date_to', 'variable_id', 'weather_station_id'])) }}" class="btn btn-outline-secondary">Reporte por correo</a>
                @endif
            </div>
            <p class="small text-secondary mb-0">Consulta hasta 366 días. Las semanas comienzan el lunes; los grupos del inicio y el final pueden ser parciales.</p>
        </form>
    </section>

    @if(!$variable)
        <section class="admin-card bg-white charts-empty p-4 p-md-5" aria-labelledby="charts-empty-title">
            <div class="charts-empty-icon" aria-hidden="true">▥</div>
            <h2 id="charts-empty-title" class="h4">Tus datos cuentan una historia</h2>
            <p class="text-secondary mb-0">Selecciona una variable numérica y un período, y pulsa <strong>Actualizar gráficos</strong> para comenzar.</p>
            @if($variables->isEmpty())
                <p class="text-secondary mt-3 mb-0">Todavía no hay variables numéricas disponibles para tu cuenta. Solicita su configuración a la administración de tu colegio.</p>
            @endif
        </section>
    @elseif(!$current?->count)
        <section class="admin-card bg-white charts-empty p-4 p-md-5" aria-labelledby="charts-no-data-title">
            <div class="charts-empty-icon" aria-hidden="true">▥</div>
            <h2 id="charts-no-data-title" class="h4">No hay mediciones en este período</h2>
            <p class="text-secondary mb-0">No encontramos valores de <strong>{{ $variable->name }}</strong> entre {{ $filters['date_from'] }} y {{ $filters['date_to'] }} con estos filtros. Prueba otras fechas o selecciona todas las estaciones.</p>
        </section>
    @else
        <div class="charts-selection">
            <div>
                <h2 class="h4 mb-1">{{ $variable->name }} <span class="text-secondary fw-normal">· {{ $variable->unit ?: 'sin unidad' }}</span></h2>
                <p class="small text-secondary mb-0">{{ $filters['date_from'] }} a {{ $filters['date_to'] }} · {{ $selectedStation?->name ?? 'Todas las estaciones y registros sin estación' }}</p>
            </div>
            <button id="download-chart-csv" type="button" class="btn btn-outline-primary" disabled>Descargar datos CSV</button>
        </div>

        <div class="charts-kpis" aria-label="Resumen del período seleccionado">
            <article class="admin-card bg-white charts-kpi">
                <span class="charts-kpi-label">Mediciones</span>
                <strong>{{ number_format((int) $current->count, 0, ',', '.') }}</strong>
                <span class="small text-secondary">Valores incluidos en el análisis</span>
            </article>
            <article class="admin-card bg-white charts-kpi">
                <span class="charts-kpi-label">Promedio</span>
                <strong>{{ $metric($current->average) }} <small>{{ $variable->unit }}</small></strong>
                <span class="small text-secondary">Calculado sobre todas las mediciones</span>
            </article>
            <article class="admin-card bg-white charts-kpi">
                <span class="charts-kpi-label">Mínimo / máximo</span>
                <strong class="charts-kpi-range">{{ $metric($current->minimum) }} / {{ $metric($current->maximum) }}</strong>
                <span class="small text-secondary">{{ $variable->unit ?: 'Sin unidad configurada' }}</span>
            </article>
            <article class="admin-card bg-white charts-kpi">
                <span class="charts-kpi-label">Cambio de promedio</span>
                <strong>
                    @if($previous?->count)
                        {{ ((float) $current->average - (float) $previous->average) > 0 ? '+' : '' }}{{ $metric((float) $current->average - (float) $previous->average) }}
                        <small>{{ $variable->unit }}</small>
                    @else
                        <span class="h5">Sin comparación</span>
                    @endif
                </strong>
                <span class="small text-secondary">
                    @if($previous?->count)
                        Frente al período anterior de igual duración
                    @else
                        No hay mediciones en el período anterior
                    @endif
                </span>
            </article>
        </div>

        @if($outside)
            <div class="alert alert-warning mb-0" role="status"><strong>{{ $outside }} {{ $outside === 1 ? 'medición está' : 'mediciones están' }} fuera de los límites configurados.</strong> Revisa su origen y el historial antes de corregirlas. Los límites actuales se usan como referencia para todo el período.</div>
        @endif
        <div id="charts-load-status" class="alert alert-warning mb-0" role="status" hidden>No se pudieron cargar los gráficos. Puedes consultar las tablas de datos.</div>
        <div id="charts-fallback" class="alert alert-info mb-0" role="status">Los datos completos están disponibles en las tablas de esta página. Si los gráficos no aparecen, puedes seguir consultando los valores allí.</div>
        <noscript><p class="alert alert-info mb-0">Activa JavaScript para ver los gráficos interactivos y descargar PNG o CSV. Las tablas muestran los datos diarios.</p></noscript>

        <section class="admin-card bg-white p-3 p-md-4" aria-labelledby="trend-title">
            <div class="charts-panel-heading">
                <div>
                    <h3 id="trend-title" class="h5 mb-1">Evolución de mediciones</h3>
                    <p class="small text-secondary mb-0" id="trend-description">Promedios del período. Las fechas sin mediciones se conservan como huecos, sin asignarles cero.</p>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-chart-download="trend" disabled>Descargar PNG</button>
            </div>
            <div class="charts-toggles my-3">
                <div class="form-check form-switch">
                    <input type="checkbox" class="form-check-input" id="compare-period" @checked($previous?->count) @disabled(!$previous?->count)>
                    <label class="form-check-label" for="compare-period">Comparar con el período anterior</label>
                </div>
                @if($variable->min_value !== null || $variable->max_value !== null)
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="show-limits">
                        <label class="form-check-label" for="show-limits">Mostrar límites configurados</label>
                    </div>
                @endif
            </div>
            <div class="charts-canvas-wrap charts-canvas-trend"><canvas id="trend-chart" role="img" aria-labelledby="trend-title trend-description" aria-describedby="chart-data-caption">Consulta los valores en la tabla de datos.</canvas></div>
            <p class="small text-secondary mt-3 mb-0" id="chart-period-note">Período anterior: {{ $previousFilters['date_from'] }} a {{ $previousFilters['date_to'] }}. Se alinea por días transcurridos para comparar períodos de igual duración.</p>
            <p class="small text-secondary mt-1 mb-0" id="chart-group-note">Cada promedio considera la cantidad de mediciones de su grupo.</p>
        </section>

        <div class="charts-secondary-grid">
            <section class="admin-card bg-white p-3 p-md-4" aria-labelledby="station-title">
                <div class="charts-panel-heading">
                    <div><h3 id="station-title" class="h5 mb-1">Comparación entre estaciones</h3><p class="small text-secondary mb-0">Promedio de la misma variable en cada estación.</p></div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-chart-download="stations" disabled>PNG</button>
                </div>
                <div class="charts-station-scroll mt-3"><div id="station-chart-wrap" class="charts-canvas-wrap"><canvas id="station-chart" role="img" aria-labelledby="station-title">Consulta la tabla de estaciones.</canvas></div></div>
                <details class="charts-details mt-3">
                    <summary>Ver datos por estación</summary>
                    <div class="table-responsive mt-2"><table class="table table-sm align-middle">
                        <caption class="visually-hidden">Estadísticas de {{ $variable->name }} por estación</caption>
                        <thead><tr><th scope="col">Estación</th><th scope="col">Mediciones</th><th scope="col">Promedio</th><th scope="col">Mínimo</th><th scope="col">Máximo</th></tr></thead>
                        <tbody>@foreach($chartData['stations'] as $station)<tr><th scope="row">{{ $station['name'] }}</th><td>{{ $station['count'] }}</td><td>{{ $metric($station['average']) }}</td><td>{{ $metric($station['minimum']) }}</td><td>{{ $metric($station['maximum']) }}</td></tr>
                        @endforeach
                        </tbody>
                    </table></div>
                </details>
                <p class="small text-secondary mb-0 mt-2">“Sin estación” reúne las mediciones que no tienen una estación asignada. Los resultados respetan los filtros seleccionados.</p>
            </section>
            <section class="admin-card bg-white p-3 p-md-4" aria-labelledby="distribution-title">
                <div class="charts-panel-heading">
                    <div><h3 id="distribution-title" class="h5 mb-1">Distribución de valores</h3><p class="small text-secondary mb-0">Cuántas mediciones se encuentran en cada intervalo.</p></div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-chart-download="distribution" disabled>PNG</button>
                </div>
                <div class="charts-canvas-wrap mt-3"><canvas id="distribution-chart" role="img" aria-labelledby="distribution-title">Consulta la tabla de distribución.</canvas></div>
                <details class="charts-details mt-3">
                    <summary>Ver distribución de valores</summary>
                    <div class="table-responsive mt-2"><table class="table table-sm">
                        <caption class="visually-hidden">Frecuencia de los valores de {{ $variable->name }}</caption>
                        <thead><tr><th scope="col">Desde</th><th scope="col">Hasta</th><th scope="col">Mediciones</th></tr></thead>
                        <tbody>@foreach($chartData['distribution'] as $interval)<tr><td>{{ rtrim(rtrim(number_format((float) $interval['from'], 8, ',', ''), '0'), ',') }}</td><td>{{ rtrim(rtrim(number_format((float) $interval['to'], 8, ',', ''), '0'), ',') }}</td><td>{{ $interval['count'] }}</td></tr>
                        @endforeach
                        </tbody>
                    </table></div>
                </details>
                <p class="small text-secondary mb-0 mt-2">Cada intervalo incluye su inicio y excluye su final, salvo el último, que también incluye el valor máximo.</p>
            </section>
        </div>

        <section class="admin-card bg-white p-3 p-md-4" aria-labelledby="chart-data-title">
            <div class="charts-panel-heading"><div><h3 id="chart-data-title" class="h5 mb-1">Datos de la evolución</h3><p class="small text-secondary mb-0">Consulta los valores exactos representados y compáralos con sus fechas originales.</p></div></div>
            <div class="charts-data-scroll table-responsive mt-3" tabindex="0" aria-label="Datos de la evolución, tabla desplazable">
                <table id="chart-data-table" class="table table-sm align-middle mb-0">
                    <caption id="chart-data-caption">Promedios diarios de {{ $variable->name }} ({{ $variable->unit ?: 'sin unidad' }}). Sin datos indica que no hay mediciones en esa fecha.</caption>
                    <thead><tr><th scope="col">Fecha / grupo</th><th scope="col">Mediciones</th><th scope="col">Promedio</th><th scope="col">Mínimo</th><th scope="col">Máximo</th><th scope="col">Fechas anteriores</th><th scope="col">Mediciones anteriores</th><th scope="col">Promedio anterior</th></tr></thead>
                    <tbody id="chart-data-body">
                    @foreach($chartData['trend'] as $point)
                        <tr><th scope="row">{{ $point['date'] }}</th><td>{{ $point['count'] }}</td><td>{{ $metric($point['average']) }}</td><td>{{ $metric($point['minimum']) }}</td><td>{{ $metric($point['maximum']) }}</td><td>{{ $point['previousDate'] }}</td><td>{{ $point['previousCount'] }}</td><td>{{ $metric($point['previousAverage']) }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        <script id="measurement-chart-data" type="application/json">{!! \Illuminate\Support\Js::encode($chartData) !!}</script>
        <p class="visually-hidden" id="chart-export-status" role="status" aria-live="polite"></p>
    @endif
</div>
@endsection
