@php
    $isEdit = isset($station) && $station;
@endphp

<div class="row g-4">
    @if(auth()->user()->hasRole('super_admin'))
        <div class="col-12 col-lg-6">
            <label for="school_id" class="form-label fw-semibold">
                Colegio <span class="text-danger">*</span>
            </label>
            <select id="school_id"
                    name="school_id"
                    class="form-select form-select-lg rounded-4 @error('school_id') is-invalid @enderror">
                <option value="">Selecciona un colegio</option>
                @foreach($schools as $school)
                    <option value="{{ $school->id }}"
                        @selected((int) old('school_id', $selectedSchoolId ?? $station?->school_id) === (int) $school->id)>
                        {{ $school->name }}
                    </option>
                @endforeach
            </select>

            @error('school_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    @endif

    <div class="col-12 col-lg-6">
        <label for="responsible_user_id" class="form-label fw-semibold">Responsable</label>
        <select id="responsible_user_id"
                name="responsible_user_id"
                class="form-select form-select-lg rounded-4 @error('responsible_user_id') is-invalid @enderror">
            <option value="">Sin responsable asignado</option>
            @foreach($responsibles as $responsible)
                <option value="{{ $responsible->id }}"
                    @selected((int) old('responsible_user_id', $station?->responsible_user_id) === (int) $responsible->id)>
                    {{ $responsible->name }} - {{ $responsible->email }}
                </option>
            @endforeach
        </select>

        @error('responsible_user_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-lg-6">
        <label for="name" class="form-label fw-semibold">
            Nombre de la estación <span class="text-danger">*</span>
        </label>
        <input type="text"
               id="name"
               name="name"
               value="{{ old('name', $station?->name) }}"
               class="form-control form-control-lg rounded-4 @error('name') is-invalid @enderror"
               placeholder="Ej. Estación EcoData Principal">

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
               value="{{ old('code', $station?->code) }}"
               class="form-control form-control-lg rounded-4 @error('code') is-invalid @enderror"
               placeholder="Ej. ECO-AGUAZUL-01">

        @error('code')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror

        <div class="form-text">
            Debe ser único dentro del colegio.
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <label for="location_name" class="form-label fw-semibold">Ubicación o referencia</label>
        <input type="text"
               id="location_name"
               name="location_name"
               value="{{ old('location_name', $station?->location_name) }}"
               class="form-control form-control-lg rounded-4 @error('location_name') is-invalid @enderror"
               placeholder="Ej. Patio central, huerta escolar, bloque B">

        @error('location_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-lg-6">
        <label for="installation_date" class="form-label fw-semibold">Fecha de instalación</label>
        <input type="date"
               id="installation_date"
               name="installation_date"
               value="{{ old('installation_date', optional($station?->installation_date)->format('Y-m-d')) }}"
               class="form-control form-control-lg rounded-4 @error('installation_date') is-invalid @enderror">

        @error('installation_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="latitude" class="form-label fw-semibold">Latitud</label>
        <input type="number"
               step="0.0000001"
               id="latitude"
               name="latitude"
               value="{{ old('latitude', $station?->latitude) }}"
               class="form-control form-control-lg rounded-4 @error('latitude') is-invalid @enderror"
               placeholder="Ej. 5.1723456">

        @error('latitude')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="longitude" class="form-label fw-semibold">Longitud</label>
        <input type="number"
               step="0.0000001"
               id="longitude"
               name="longitude"
               value="{{ old('longitude', $station?->longitude) }}"
               class="form-control form-control-lg rounded-4 @error('longitude') is-invalid @enderror"
               placeholder="Ej. -72.5489123">

        @error('longitude')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="altitude" class="form-label fw-semibold">Altitud</label>
        <div class="input-group input-group-lg">
            <input type="number"
                   step="0.01"
                   id="altitude"
                   name="altitude"
                   value="{{ old('altitude', $station?->altitude) }}"
                   class="form-control rounded-start-4 @error('altitude') is-invalid @enderror"
                   placeholder="Ej. 312">
            <span class="input-group-text rounded-end-4">m</span>

            @error('altitude')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <label for="is_active" class="form-label fw-semibold">
            Estado <span class="text-danger">*</span>
        </label>
        <select id="is_active"
                name="is_active"
                class="form-select form-select-lg rounded-4 @error('is_active') is-invalid @enderror">
            <option value="1" @selected((string) old('is_active', $station?->is_active ?? 1) === '1')>
                Activa
            </option>
            <option value="0" @selected((string) old('is_active', $station?->is_active ?? 1) === '0')>
                Inactiva
            </option>
        </select>

        @error('is_active')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="description" class="form-label fw-semibold">Descripción</label>
        <textarea id="description"
                  name="description"
                  rows="4"
                  class="form-control form-control-lg rounded-4 @error('description') is-invalid @enderror"
                  placeholder="Describe el propósito, condiciones o características de la estación.">{{ old('description', $station?->description) }}</textarea>

        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>