@php
    $school = $school ?? null;
@endphp

<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="admin-card bg-white p-4 h-100">
            <div class="form-section-title">Información general</div>

            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Nombre del colegio</label>
                    <input type="text" name="name" class="form-control rounded-4"
                           value="{{ old('name', $school->name ?? '') }}">
                    @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Slug</label>
                    <input type="text" name="slug" class="form-control rounded-4"
                           value="{{ old('slug', $school->slug ?? '') }}">
                    @error('slug') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Color primario</label>
                    <input type="color" name="primary_color" class="form-control form-control-color rounded-4 w-100"
                           value="{{ old('primary_color', $school->primary_color ?? '#1d4ed8') }}">
                    @error('primary_color') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Color secundario</label>
                    <input type="color" name="secondary_color" class="form-control form-control-color rounded-4 w-100"
                           value="{{ old('secondary_color', $school->secondary_color ?? '#0f172a') }}">
                    @error('secondary_color') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Color acento</label>
                    <input type="color" name="accent_color" class="form-control form-control-color rounded-4 w-100"
                           value="{{ old('accent_color', $school->accent_color ?? '#22c55e') }}">
                    @error('accent_color') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Escudo institucional</label>
                    <input type="file" name="shield" class="form-control rounded-4" accept="image/*">
                    @error('shield') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <input type="hidden" name="is_active" value="0">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1"
                               @checked(old('is_active', $school->is_active ?? true))>
                        <label class="form-check-label" for="is_active">Colegio activo</label>
                    </div>
                    @error('is_active') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="admin-card bg-white p-4 h-100">
            <div class="form-section-title">Vista previa</div>

            <div class="school-preview p-4"
                 style="background: linear-gradient(135deg, {{ old('primary_color', $school->primary_color ?? '#1d4ed8') }}, {{ old('secondary_color', $school->secondary_color ?? '#0f172a') }});">
                <div class="d-flex align-items-center gap-3">
                    <div class="school-avatar bg-white bg-opacity-25 text-white">
                        @if(!empty($school?->shield_path))
                            <img src="{{ asset('storage/' . $school->shield_path) }}" class="w-100 h-100 object-fit-cover" alt="Escudo">
                        @else
                            ESC
                        @endif
                    </div>
                    <div>
                        <div class="fw-bold">{{ old('name', $school->name ?? 'Nombre del colegio') }}</div>
                        <small>{{ old('slug', $school->slug ?? 'slug-del-colegio') }}</small>
                    </div>
                </div>

                <div class="mt-3 d-flex gap-2">
                    <span class="badge rounded-pill px-3 py-2"
                          style="background-color: {{ old('accent_color', $school->accent_color ?? '#22c55e') }};">
                        Acento
                    </span>

                    <span class="badge rounded-pill bg-light text-dark px-3 py-2">
                        {{ old('is_active', $school->is_active ?? true) ? 'Activo' : 'Inactivo' }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a href="{{ route('admin.schools.index') }}" class="btn btn-outline-secondary rounded-4 px-4">
        Cancelar
    </a>
    <button type="submit" class="btn text-white rounded-4 px-4" style="background-color: var(--school-primary);">
        {{ $buttonText ?? 'Guardar' }}
    </button>
</div>