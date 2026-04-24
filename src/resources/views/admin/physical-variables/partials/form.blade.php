@php
    $variable = $variable ?? null;

    $selectedSchoolId = old('school_id', $selectedSchoolId ?? $variable->school_id ?? auth()->user()?->school_id);
    $selectedSchool = $schools->firstWhere('id', (int) $selectedSchoolId);
@endphp

<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="admin-card bg-white p-4 h-100">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Colegio</label>

                    @if(auth()->user()?->hasRole('super_admin'))
                        <select name="school_id" class="form-select rounded-4">
                            <option value="">Selecciona un colegio</option>
                            @foreach($schools as $school)
                                <option value="{{ $school->id }}"
                                    @selected((string) $selectedSchoolId === (string) $school->id)>
                                    {{ $school->name }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <input type="hidden" name="school_id" value="{{ $selectedSchoolId }}">

                        <input type="text"
                               class="form-control rounded-4 bg-light"
                               value="{{ $selectedSchool?->name ?? auth()->user()?->school?->name ?? 'Colegio asignado' }}"
                               disabled>

                        <div class="form-text">
                            Tu rol solo permite gestionar variables físicas del colegio asignado.
                        </div>
                    @endif

                    @error('school_id')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Categoría</label>
                    <select name="category_id" class="form-select rounded-4">
                        <option value="">Selecciona una categoría</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}"
                                @selected((string) old('category_id', $variable->category_id ?? '') === (string) $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Nombre</label>
                    <input type="text" name="name" class="form-control rounded-4"
                           value="{{ old('name', $variable->name ?? '') }}"
                           placeholder="Ej: Temperatura ambiental">
                    @error('name')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Slug</label>
                    <input type="text" name="slug" class="form-control rounded-4"
                           value="{{ old('slug', $variable->slug ?? '') }}"
                           placeholder="Ej: temperatura-ambiental">
                    @error('slug')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Unidad</label>
                    <input type="text" name="unit" class="form-control rounded-4"
                           value="{{ old('unit', $variable->unit ?? '') }}"
                           placeholder="Ej: °C">
                    @error('unit')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Tipo de dato</label>
                    <select name="data_type" id="data_type" class="form-select rounded-4">
                        @foreach($dataTypes as $value => $label)
                            <option value="{{ $value }}"
                                @selected(old('data_type', $variable->data_type ?? 'decimal') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('data_type')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Decimales</label>
                    <input type="number" min="0" max="10" name="decimals" id="decimals"
                           class="form-control rounded-4"
                           value="{{ old('decimals', $variable->decimals ?? 2) }}">
                    @error('decimals')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-6 range-field">
                    <label class="form-label fw-semibold">Valor mínimo</label>
                    <input type="number" step="any" name="min_value" id="min_value"
                        class="form-control rounded-4"
                        value="{{ old('min_value', isset($variable) && $variable->min_value !== null ? number_format((float) $variable->min_value, $variable->decimals ?? 0, '.', '') : '') }}">
                    @error('min_value')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-6 range-field">
                    <label class="form-label fw-semibold">Valor máximo</label>
                    <input type="number" step="any" name="max_value" id="max_value"
                        class="form-control rounded-4"
                        value="{{ old('max_value', isset($variable) && $variable->max_value !== null ? number_format((float) $variable->max_value, $variable->decimals ?? 0, '.', '') : '') }}">
                    @error('max_value')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Descripción</label>
                    <textarea name="description" rows="4" class="form-control rounded-4"
                              placeholder="Descripción opcional de la variable">{{ old('description', $variable->description ?? '') }}</textarea>
                    @error('description')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <input type="hidden" name="is_active" value="0">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                               @checked(old('is_active', $variable->is_active ?? true))>
                        <label class="form-check-label" for="is_active">Variable activa</label>
                    </div>
                    @error('is_active')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="admin-card bg-white p-4 h-100">
            <h6 class="fw-bold mb-3">Resumen</h6>
            <div class="small text-secondary d-flex flex-column gap-2">
                <div><strong>Colegio:</strong> {{ $selectedSchool?->name ?? auth()->user()?->school?->name ?? 'Sin definir' }}</div>
                <div><strong>Nombre:</strong> {{ old('name', $variable->name ?? 'Sin definir') }}</div>
                <div><strong>Slug:</strong> {{ old('slug', $variable->slug ?? 'Sin definir') }}</div>
                <div><strong>Unidad:</strong> {{ old('unit', $variable->unit ?? 'Sin definir') }}</div>
                <div><strong>Tipo:</strong> {{ ucfirst(old('data_type', $variable->data_type ?? 'decimal')) }}</div>
                <div><strong>Estado:</strong> {{ old('is_active', $variable->is_active ?? true) ? 'Activo' : 'Inactivo' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a href="{{ route('admin.physical-variables.index') }}" class="btn btn-outline-secondary rounded-4 px-4">
        Cancelar
    </a>
    <button type="submit" class="btn text-white rounded-4 px-4" style="background-color: var(--school-primary);">
        {{ $buttonText ?? 'Guardar' }}
    </button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dataType = document.getElementById('data_type');
    const decimals = document.getElementById('decimals');
    const minValue = document.getElementById('min_value');
    const maxValue = document.getElementById('max_value');
    const rangeFields = document.querySelectorAll('.range-field');

    function updateFields() {
        const value = dataType.value;

        if (['text', 'boolean', 'date'].includes(value)) {
            rangeFields.forEach(field => field.style.display = 'none');
            minValue.value = '';
            maxValue.value = '';
            decimals.value = 0;
            decimals.setAttribute('readonly', 'readonly');
        } else if (value === 'integer') {
            rangeFields.forEach(field => field.style.display = '');
            decimals.value = 0;
            decimals.setAttribute('readonly', 'readonly');
        } else {
            rangeFields.forEach(field => field.style.display = '');
            decimals.removeAttribute('readonly');
        }
    }

    dataType?.addEventListener('change', updateFields);
    updateFields();
});
</script>