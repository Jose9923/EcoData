@php
    $category = $category ?? null;
@endphp

<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="admin-card bg-white p-4 h-100">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Nombre</label>
                    <input type="text" name="name" class="form-control rounded-4"
                           value="{{ old('name', $category->name ?? '') }}"
                           placeholder="Ej: Temperatura">
                    @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Slug</label>
                    <input type="text" name="slug" class="form-control rounded-4"
                           value="{{ old('slug', $category->slug ?? '') }}"
                           placeholder="Ej: temperatura">
                    @error('slug') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Descripción</label>
                    <textarea name="description" rows="4" class="form-control rounded-4"
                              placeholder="Descripción opcional de la categoría">{{ old('description', $category->description ?? '') }}</textarea>
                    @error('description') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <input type="hidden" name="is_active" value="0">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                               @checked(old('is_active', $category->is_active ?? true))>
                        <label class="form-check-label" for="is_active">Categoría activa</label>
                    </div>
                    @error('is_active') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="admin-card bg-white p-4 h-100">
            <h6 class="fw-bold mb-3">Resumen</h6>
            <div class="small text-secondary d-flex flex-column gap-2">
                <div><strong>Nombre:</strong> {{ old('name', $category->name ?? 'Sin definir') }}</div>
                <div><strong>Slug:</strong> {{ old('slug', $category->slug ?? 'Sin definir') }}</div>
                <div><strong>Estado:</strong> {{ old('is_active', $category->is_active ?? true) ? 'Activo' : 'Inactivo' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a href="{{ route('admin.physical-variable-categories.index') }}" class="btn btn-outline-secondary rounded-4 px-4">
        Cancelar
    </a>
    <button type="submit" class="btn text-white rounded-4 px-4" style="background-color: var(--school-primary);">
        {{ $buttonText ?? 'Guardar' }}
    </button>
</div>