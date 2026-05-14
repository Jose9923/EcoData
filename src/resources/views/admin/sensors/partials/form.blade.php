@php
    $isEdit = isset($sensor) && $sensor;
@endphp

<div class="row g-4">
    <div class="col-12 col-lg-6">
        <label for="weather_station_id" class="form-label fw-semibold">
            Estación meteorológica <span class="text-danger">*</span>
        </label>
        <select id="weather_station_id"
                name="weather_station_id"
                class="form-select form-select-lg rounded-4 @error('weather_station_id') is-invalid @enderror">
            <option value="">Selecciona una estación</option>
            @foreach($stations as $station)
                <option value="{{ $station->id }}"
                    @selected((int) old('weather_station_id', $selectedStationId ?? $sensor?->weather_station_id) === (int) $station->id)>
                    {{ $station->name }} · {{ $station->code }}
                    @if(auth()->user()->hasRole('super_admin'))
                        · {{ $station->school?->name }}
                    @endif
                </option>
            @endforeach
        </select>

        @error('weather_station_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-lg-6">
        <label for="physical_variable_id" class="form-label fw-semibold">Variable física asociada</label>
        <select id="physical_variable_id"
                name="physical_variable_id"
                class="form-select form-select-lg rounded-4 @error('physical_variable_id') is-invalid @enderror">
            <option value="">Sin variable asociada</option>
            @foreach($variables as $variable)
                <option value="{{ $variable->id }}"
                    @selected((int) old('physical_variable_id', $sensor?->physical_variable_id) === (int) $variable->id)>
                    {{ $variable->name }}
                    @if($variable->unit)
                        ({{ $variable->unit }})
                    @endif
                    @if($variable->category)
                        · {{ $variable->category->name }}
                    @endif
                </option>
            @endforeach
        </select>

        @error('physical_variable_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror

        <div class="form-text">
            Selecciona la variable que mide este sensor, por ejemplo temperatura, humedad o presión.
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <label for="name" class="form-label fw-semibold">
            Nombre del sensor <span class="text-danger">*</span>
        </label>
        <input type="text"
               id="name"
               name="name"
               value="{{ old('name', $sensor?->name) }}"
               class="form-control form-control-lg rounded-4 @error('name') is-invalid @enderror"
               placeholder="Ej. Sensor DHT22 de temperatura">

        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-lg-6">
        <label for="code" class="form-label fw-semibold">
            Código <span class="text-danger">*</span>
        </label>
        <input type="text"
               id="code"
               name="code"
               value="{{ old('code', $sensor?->code) }}"
               class="form-control form-control-lg rounded-4 @error('code') is-invalid @enderror"
               placeholder="Ej. TEMP-01">

        @error('code')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror

        <div class="form-text">
            Debe ser único dentro de la estación meteorológica.
        </div>
    </div>

    <div class="col-12 col-md-4">
        <label for="brand" class="form-label fw-semibold">Marca</label>
        <input type="text"
               id="brand"
               name="brand"
               value="{{ old('brand', $sensor?->brand) }}"
               class="form-control form-control-lg rounded-4 @error('brand') is-invalid @enderror"
               placeholder="Ej. Aosong">

        @error('brand')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="model" class="form-label fw-semibold">Modelo</label>
        <input type="text"
               id="model"
               name="model"
               value="{{ old('model', $sensor?->model) }}"
               class="form-control form-control-lg rounded-4 @error('model') is-invalid @enderror"
               placeholder="Ej. DHT22">

        @error('model')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="serial_number" class="form-label fw-semibold">Número de serie</label>
        <input type="text"
               id="serial_number"
               name="serial_number"
               value="{{ old('serial_number', $sensor?->serial_number) }}"
               class="form-control form-control-lg rounded-4 @error('serial_number') is-invalid @enderror"
               placeholder="Ej. SN-2026-001">

        @error('serial_number')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="measurement_unit" class="form-label fw-semibold">Unidad de medida</label>
        <input type="text"
               id="measurement_unit"
               name="measurement_unit"
               value="{{ old('measurement_unit', $sensor?->measurement_unit) }}"
               class="form-control form-control-lg rounded-4 @error('measurement_unit') is-invalid @enderror"
               placeholder="Ej. °C, %, hPa, mm">

        @error('measurement_unit')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="measurement_range" class="form-label fw-semibold">Rango de medición</label>
        <input type="text"
               id="measurement_range"
               name="measurement_range"
               value="{{ old('measurement_range', $sensor?->measurement_range) }}"
               class="form-control form-control-lg rounded-4 @error('measurement_range') is-invalid @enderror"
               placeholder="Ej. -40 a 80 °C">

        @error('measurement_range')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="accuracy" class="form-label fw-semibold">Precisión</label>
        <input type="text"
               id="accuracy"
               name="accuracy"
               value="{{ old('accuracy', $sensor?->accuracy) }}"
               class="form-control form-control-lg rounded-4 @error('accuracy') is-invalid @enderror"
               placeholder="Ej. ±0.5 °C">

        @error('accuracy')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="installation_date" class="form-label fw-semibold">Fecha de instalación</label>
        <input type="date"
               id="installation_date"
               name="installation_date"
               value="{{ old('installation_date', optional($sensor?->installation_date)->format('Y-m-d')) }}"
               class="form-control form-control-lg rounded-4 @error('installation_date') is-invalid @enderror">

        @error('installation_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="last_maintenance_date" class="form-label fw-semibold">Último mantenimiento</label>
        <input type="date"
               id="last_maintenance_date"
               name="last_maintenance_date"
               value="{{ old('last_maintenance_date', optional($sensor?->last_maintenance_date)->format('Y-m-d')) }}"
               class="form-control form-control-lg rounded-4 @error('last_maintenance_date') is-invalid @enderror">

        @error('last_maintenance_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="next_maintenance_date" class="form-label fw-semibold">Próximo mantenimiento</label>
        <input type="date"
               id="next_maintenance_date"
               name="next_maintenance_date"
               value="{{ old('next_maintenance_date', optional($sensor?->next_maintenance_date)->format('Y-m-d')) }}"
               class="form-control form-control-lg rounded-4 @error('next_maintenance_date') is-invalid @enderror">

        @error('next_maintenance_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-lg-6">
        <label for="status" class="form-label fw-semibold">
            Estado técnico <span class="text-danger">*</span>
        </label>
        <select id="status"
                name="status"
                class="form-select form-select-lg rounded-4 @error('status') is-invalid @enderror">
            @foreach($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $sensor?->status ?? 'operativo') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>

        @error('status')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-lg-6">
        <label for="is_active" class="form-label fw-semibold">
            Estado en plataforma <span class="text-danger">*</span>
        </label>
        <select id="is_active"
                name="is_active"
                class="form-select form-select-lg rounded-4 @error('is_active') is-invalid @enderror">
            <option value="1" @selected((string) old('is_active', $sensor?->is_active ?? 1) === '1')>
                Activo
            </option>
            <option value="0" @selected((string) old('is_active', $sensor?->is_active ?? 1) === '0')>
                Inactivo
            </option>
        </select>

        @error('is_active')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="observations" class="form-label fw-semibold">Observaciones</label>
        <textarea id="observations"
                  name="observations"
                  rows="4"
                  class="form-control form-control-lg rounded-4 @error('observations') is-invalid @enderror"
                  placeholder="Registra observaciones técnicas, condiciones de instalación o novedades del sensor.">{{ old('observations', $sensor?->observations) }}</textarea>

        @error('observations')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>