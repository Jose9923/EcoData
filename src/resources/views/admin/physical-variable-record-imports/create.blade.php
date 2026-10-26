@extends('layouts.app')
@section('content')
<div class="d-flex flex-column gap-4">
<section class="admin-hero p-4">
    <h1 class="h2 fw-bold">Importar mediciones CSV</h1>
    <p class="mb-0">Descarga la plantilla de la estación, revisa todas las filas y confirma la importación.</p>
</section>
@if($errors->any())
<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
@if(session('import_result'))
<div class="alert alert-success">
    {{ session('import_result.count') }} registros guardados para {{ session('import_result.station') }}.
    <a href="{{ route('admin.physical-variable-records.index') }}">Consultar registros</a>
</div>
@endif
<section class="admin-card bg-white p-4">
<h2 class="h5">1. Plantilla y archivo</h2>
<form method="POST" action="{{ route('admin.physical-variable-record-imports.preview') }}" enctype="multipart/form-data" class="row g-3">
@csrf
<div class="col-md-8">
    <label class="form-label" for="import-station">Estación meteorológica</label>
    <select id="import-station" name="weather_station_id" class="form-select" required>
        <option value="">Selecciona una estación</option>
        @foreach($weatherStations as $station)
        <option value="{{ $station->id }}" @selected((string) old('weather_station_id', $preview['station_id'] ?? '') === (string) $station->id)>{{ $station->school?->name }} · {{ $station->name }} ({{ $station->code }})</option>
        @endforeach
    </select>
</div>
<div class="col-md-4">
    <label class="form-label" for="import-delimiter">Separador</label>
    <select id="import-delimiter" name="delimiter" class="form-select">
        <option value="," @selected(old('delimiter', $preview['delimiter'] ?? ',') === ',')>Coma (,)</option>
        <option value=";" @selected(old('delimiter', $preview['delimiter'] ?? ',') === ';')>Punto y coma (;)</option>
    </select>
</div>
<div class="col-12">
    <button class="btn btn-outline-primary" type="button" id="download-template">Descargar plantilla de esta estación</button>
    <div class="form-text">Incluye las variables activas de sus sensores activos y sus unidades exactas. No cambies las cabeceras ni las unidades.</div>
</div>
<div class="col-md-6">
    <label class="form-label" for="csv-file">Archivo CSV UTF-8</label>
    <input id="csv-file" type="file" name="csv_file" accept=".csv,text/csv" class="form-control" required>
    <div class="form-text">Máximo 2 MiB, 500 filas y 100 variables. No se guardan mediciones durante la vista previa.</div>
</div>
<div class="col-md-6">
    <label class="form-label" for="import-observations">Observaciones comunes (opcional)</label>
    <textarea id="import-observations" name="observations" class="form-control" maxlength="10000" rows="2">{{ old('observations') }}</textarea>
</div>
<div class="col-12 small text-secondary">
    <p>Fecha y hora: <code>2026-10-26 14:30</code> (hora de la aplicación). Fechas de variables: <code>YYYY-MM-DD</code>. Decimales con punto, sin separador de miles; máximo 4 y según la configuración de cada variable. Booleanos: <code>1</code> o <code>0</code>. Las celdas vacías no crean valores.</p>
    <p>La columna opcional <code>observations</code> reemplaza las observaciones comunes en cada fila. Los registros importados son generales para el colegio de la estación, con origen CSV. La importación se confirma completa o no se guarda ninguna fila.</p>
</div>
<div class="col-12"><button type="submit" class="btn btn-primary">Validar y ver vista previa</button></div>
</form>
</section>
@if($preview && (int) $preview['actor_id'] === (int) auth()->id())
<section class="admin-card bg-white p-4">
<h2 class="h5">2. Vista previa: {{ $preview['station_name'] }}</h2>
<p>{{ count($preview['rows']) }} filas · {{ count($preview['errors']) }} filas con errores. La confirmación vence a los 20 minutos.</p>
@if($preview['errors'])
<div class="alert alert-danger">No se puede confirmar mientras existan errores. Corrige el archivo y vuelve a cargarlo.</div>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Fila CSV</th><th>Errores</th></tr></thead><tbody>
@foreach($preview['errors'] as $line => $errorsInRow)
<tr><td>{{ $line }}</td><td>@foreach($errorsInRow as $error)<div>{{ $error }}</div>@endforeach</td></tr>
@endforeach
</tbody></table></div>
@endif
<p class="small text-secondary">Se muestran las primeras 50 filas. La validación y la confirmación incluyen las {{ count($preview['rows']) }} filas.</p>
<div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Fila</th><th>Fecha y hora</th><th>Valores y unidades</th><th>Observaciones</th><th>Estado</th></tr></thead><tbody>
@foreach(array_slice($preview['rows'], 0, 50) as $row)
<tr>
<td>{{ $row['line'] }}</td><td>{{ $row['input']['recorded_at'] ?? '—' }}</td>
<td>@foreach(($row['input']['values'] ?? []) as $id => $value)
@php($variable = $preview['columns'][$id] ?? null)
<div>{{ $variable['name'] ?? 'Variable '.$id }}: {{ $value === '' ? 'Sin valor' : $value }} {{ $variable['unit'] ?? '' }}</div>
@endforeach</td>
<td>{{ $row['input']['observations'] ?? '—' }}</td>
<td>{{ isset($preview['errors'][$row['line']]) ? 'Con errores' : 'Válida' }}</td>
</tr>
@endforeach
</tbody></table></div>
@if(! $preview['errors'])
<form method="POST" action="{{ route('admin.physical-variable-record-imports.commit') }}">
@csrf
<input type="hidden" name="token" value="{{ $preview['token'] }}">
<button type="submit" class="btn btn-success" onclick="this.form.requestSubmit(); this.disabled = true; return false;">Confirmar y guardar {{ count($preview['rows']) }} registros</button>
</form>
@endif
</section>
@endif
</div>
<script>
document.getElementById('download-template').addEventListener('click', function () {
    const station = document.getElementById('import-station');
    if (!station.reportValidity()) return;
    const url = new URL(@json(route('admin.physical-variable-record-imports.template')));
    url.searchParams.set('weather_station_id', station.value);
    url.searchParams.set('delimiter', document.getElementById('import-delimiter').value);
    window.location.href = url.toString();
});
</script>
@endsection
