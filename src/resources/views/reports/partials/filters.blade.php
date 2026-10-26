<div class="col-md-3">
    <label class="form-label" for="date-from">Desde</label>
    <input class="form-control" type="date" name="date_from" id="date-from" value="{{ $filters['date_from'] }}" required>
</div>
<div class="col-md-3">
    <label class="form-label" for="date-to">Hasta</label>
    <input class="form-control" type="date" name="date_to" id="date-to" value="{{ $filters['date_to'] }}" required>
</div>
<div class="col-md-3">
    <label class="form-label" for="report-variable">Variable</label>
    <select name="variable_id" id="report-variable" class="form-select">
        <option value="">Selecciona una variable</option>
        @foreach($variables as $item)
            <option value="{{ $item->id }}" @selected(($filters['variable_id'] ?? null) == $item->id)>{{ $item->name }} ({{ $item->unit ?: 'sin unidad' }}){{ auth()->user()->hasRole('super_admin') ? ' · '.$item->school?->name : '' }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-3">
    <label class="form-label" for="report-station">Estación</label>
    <select name="weather_station_id" id="report-station" class="form-select">
        <option value="">Todas las estaciones y registros manuales</option>
        @foreach($stations as $item)
            <option value="{{ $item->id }}" @selected(($filters['weather_station_id'] ?? null) == $item->id)>{{ $item->name }}{{ auth()->user()->hasRole('super_admin') ? ' · '.$item->school?->name : '' }}</option>
        @endforeach
    </select>
</div>
