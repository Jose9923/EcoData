@php
    $grade = $grade ?? null;
@endphp

<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="admin-card bg-white p-4 h-100">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Colegio</label>
                    <select name="school_id" class="form-select rounded-4">
                        <option value="">Selecciona un colegio</option>
                        @foreach($schools as $school)
                            <option value="{{ $school->id }}"
                                @selected((string) old('school_id', $grade->school_id ?? '') === (string) $school->id)>
                                {{ $school->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('school_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Estado</label>
                    <div class="form-check form-switch mt-2">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                               @checked(old('is_active', $grade->is_active ?? true))>
                        <label class="form-check-label" for="is_active">Grado activo</label>
                    </div>
                    @error('is_active') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Nombre del grado</label>
                    <input type="text" name="name" class="form-control rounded-4"
                           value="{{ old('name', $grade->name ?? '') }}"
                           placeholder="Ej: 6">
                    @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Etiqueta visible</label>
                    <input type="text" name="label" class="form-control rounded-4"
                           value="{{ old('label', $grade->label ?? '') }}"
                           placeholder="Ej: Sexto">
                    @error('label') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="admin-card bg-white p-4 h-100">
            <h6 class="fw-bold mb-3">Resumen</h6>
            <div class="small text-secondary d-flex flex-column gap-2">
                <div><strong>Colegio:</strong> {{ $schools->firstWhere('id', old('school_id', $grade->school_id ?? null))?->name ?? 'Sin definir' }}</div>
                <div><strong>Grado:</strong> {{ old('name', $grade->name ?? 'Sin definir') }}</div>
                <div><strong>Etiqueta:</strong> {{ old('label', $grade->label ?? 'Sin definir') }}</div>
                <div><strong>Estado:</strong> {{ old('is_active', $grade->is_active ?? true) ? 'Activo' : 'Inactivo' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a href="{{ route('admin.grades.index') }}" class="btn btn-outline-secondary rounded-4 px-4">Cancelar</a>
    <button type="submit" class="btn text-white rounded-4 px-4" style="background-color: var(--school-primary);">
        {{ $buttonText ?? 'Guardar' }}
    </button>
</div>