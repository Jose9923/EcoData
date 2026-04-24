@php
    $guide = $guide ?? null;
@endphp

<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="admin-card bg-white p-4 h-100">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Colegio</label>
                    <select name="school_id" class="form-select rounded-4">
                        <option value="">Selecciona</option>
                        @foreach($schools as $school)
                            <option value="{{ $school->id }}" @selected(old('school_id', $guide->school_id ?? '') == $school->id)>
                                {{ $school->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('school_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Grado</label>
                    <select name="grade_id" class="form-select rounded-4">
                        <option value="">Todos</option>
                        @foreach($grades as $grade)
                            <option value="{{ $grade->id }}" @selected(old('grade_id', $guide->grade_id ?? '') == $grade->id)>
                                {{ $grade->label ?: $grade->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('grade_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Curso</label>
                    <select name="course_id" class="form-select rounded-4">
                        <option value="">Todos</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" @selected(old('course_id', $guide->course_id ?? '') == $course->id)>
                                {{ $course->label ?: $course->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('course_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Fecha de publicación</label>
                    <input type="datetime-local" name="published_at" class="form-control rounded-4"
                           value="{{ old('published_at', isset($guide) && $guide->published_at ? $guide->published_at->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}">
                    @error('published_at') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Título</label>
                    <input type="text" name="title" class="form-control rounded-4"
                           value="{{ old('title', $guide->title ?? '') }}">
                    @error('title') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Descripción</label>
                    <textarea name="description" rows="4" class="form-control rounded-4">{{ old('description', $guide->description ?? '') }}</textarea>
                    @error('description') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Archivo PDF</label>
                    <input type="file" name="pdf" accept="application/pdf" class="form-control rounded-4">
                    @if(!empty($guide?->pdf_path))
                        <small class="text-secondary d-block mt-2">
                            Archivo actual: <a href="{{ asset('storage/' . $guide->pdf_path) }}" target="_blank">Ver PDF</a>
                        </small>
                    @endif
                    @error('pdf') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <input type="hidden" name="is_active" value="0">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1"
                               @checked(old('is_active', $guide->is_active ?? true))>
                        <label class="form-check-label">Guía activa</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a href="{{ route('admin.laboratory-guides.index') }}" class="btn btn-outline-secondary rounded-4 px-4">Cancelar</a>
    <button type="submit" class="btn text-white rounded-4 px-4" style="background-color: var(--school-primary);">
        {{ $buttonText ?? 'Guardar' }}
    </button>
</div>