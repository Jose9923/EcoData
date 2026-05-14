@php
    $isEdit = isset($event) && $event;
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
                        @selected((int) old('school_id', $selectedSchoolId ?? $event?->school_id) === (int) $school->id)>
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
        <label for="title" class="form-label fw-semibold">
            Título <span class="text-danger">*</span>
        </label>
        <input type="text"
               id="title"
               name="title"
               value="{{ old('title', $event?->title) }}"
               class="form-control form-control-lg rounded-4 @error('title') is-invalid @enderror"
               placeholder="Ej. Día Mundial del Agua">

        @error('title')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="starts_at" class="form-label fw-semibold">
            Fecha de inicio <span class="text-danger">*</span>
        </label>
        <input type="date"
               id="starts_at"
               name="starts_at"
               value="{{ old('starts_at', optional($event?->starts_at)->format('Y-m-d')) }}"
               class="form-control form-control-lg rounded-4 @error('starts_at') is-invalid @enderror">

        @error('starts_at')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="ends_at" class="form-label fw-semibold">
            Fecha de finalización <span class="text-danger">*</span>
        </label>
        <input type="date"
               id="ends_at"
               name="ends_at"
               value="{{ old('ends_at', optional($event?->ends_at)->format('Y-m-d')) }}"
               class="form-control form-control-lg rounded-4 @error('ends_at') is-invalid @enderror">

        @error('ends_at')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-lg-6">
        <label for="image" class="form-label fw-semibold">Imagen del evento</label>
        <input type="file"
               id="image"
               name="image"
               accept="image/*"
               class="form-control form-control-lg rounded-4 @error('image') is-invalid @enderror">

        @error('image')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror

        <div class="form-text">
            Formatos permitidos: JPG, PNG o WEBP. Tamaño máximo: 4 MB.
        </div>

        @if($isEdit && $event->image_path)
            <div class="mt-3">
                <div class="text-muted small fw-semibold mb-2">Imagen actual</div>
                <img src="{{ asset('storage/' . $event->image_path) }}"
                     alt="{{ $event->title }}"
                     class="rounded-4 border"
                     style="max-width: 260px; height: 140px; object-fit: cover;">
            </div>
        @endif
    </div>

    <div class="col-12 col-lg-6">
        <label for="is_active" class="form-label fw-semibold">
            Estado <span class="text-danger">*</span>
        </label>
        <select id="is_active"
                name="is_active"
                class="form-select form-select-lg rounded-4 @error('is_active') is-invalid @enderror">
            <option value="1" @selected((string) old('is_active', $event?->is_active ?? 1) === '1')>
                Activo
            </option>
            <option value="0" @selected((string) old('is_active', $event?->is_active ?? 1) === '0')>
                Inactivo
            </option>
        </select>

        @error('is_active')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror

        <div class="form-text">
            Solo los eventos activos se muestran a los usuarios.
        </div>
    </div>

    <div class="col-12">
        <label for="description" class="form-label fw-semibold">Descripción</label>
        <textarea id="description"
                  name="description"
                  rows="6"
                  class="form-control form-control-lg rounded-4 @error('description') is-invalid @enderror"
                  placeholder="Describe el propósito de la conmemoración, actividades sugeridas o mensaje ambiental.">{{ old('description', $event?->description) }}</textarea>

        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>