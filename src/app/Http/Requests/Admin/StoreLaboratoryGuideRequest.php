<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class StoreLaboratoryGuideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin_colegio', 'docente']) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->hasFile('pdf')) {
            $file = $this->file('pdf');
        } else {
            Log::warning('DEBUG PDF - no llegó archivo en el campo pdf', [
                'input_names' => array_keys($this->all()),
                'files' => $this->allFiles(),
            ]);
        }
    }

    public function rules(): array
    {
        $authUser = $this->user();

        $isSuperAdmin = $authUser?->hasRole('super_admin') ?? false;

        $effectiveSchoolId = $isSuperAdmin
            ? $this->input('school_id')
            : $authUser?->school_id;

        return [
            'school_id' => [
                'required',
                'integer',
                $isSuperAdmin
                    ? Rule::exists('schools', 'id')->where(fn ($query) => $query->where('is_active', true))
                    : Rule::in([(int) $authUser?->school_id]),
            ],

            'grade_id' => [
                'nullable',
                'integer',
                Rule::exists('grades', 'id')->where(function ($query) use ($effectiveSchoolId) {
                    $query->where('school_id', $effectiveSchoolId)
                        ->where('is_active', true);
                }),
            ],

            'course_id' => [
                'nullable',
                'integer',
                Rule::exists('courses', 'id')->where(function ($query) use ($effectiveSchoolId) {
                    $query->where('school_id', $effectiveSchoolId)
                        ->where('is_active', true);

                    if ($this->input('grade_id')) {
                        $query->where('grade_id', $this->input('grade_id'));
                    }
                }),
            ],

            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],

            // Puedes dejar mimes:pdf. Si el PDF del banco falla por MIME raro,
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:10240'],

            'published_at' => ['nullable', 'date'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'school_id.required' => 'Debes seleccionar un colegio.',
            'school_id.integer' => 'El colegio seleccionado no es válido.',
            'school_id.exists' => 'El colegio seleccionado no existe o está inactivo.',
            'school_id.in' => 'No puedes gestionar guías de laboratorio de un colegio diferente al tuyo.',

            'grade_id.integer' => 'El grado seleccionado no es válido.',
            'grade_id.exists' => 'El grado seleccionado no pertenece al colegio indicado o está inactivo.',

            'course_id.integer' => 'El curso seleccionado no es válido.',
            'course_id.exists' => 'El curso seleccionado no pertenece al colegio o grado indicado, o está inactivo.',

            'title.required' => 'El título de la guía es obligatorio.',
            'title.string' => 'El título de la guía debe ser un texto válido.',
            'title.max' => 'El título de la guía no puede superar los 255 caracteres.',

            'description.string' => 'La descripción debe ser un texto válido.',

            'pdf.required' => 'Debes cargar un archivo PDF.',
            'pdf.file' => 'El archivo cargado no es válido.',
            'pdf.uploaded' => 'El PDF no pudo cargarse correctamente. Puede estar dañado, incompleto o venir en un formato no estándar. Intenta abrirlo y volverlo a guardar como PDF.',
            'pdf.mimes' => 'La guía debe estar en formato PDF.',
            'pdf.max' => 'El PDF no puede superar los 10 MB.',

            'published_at.date' => 'La fecha de publicación no es válida.',

            'is_active.required' => 'Debes indicar el estado de la guía.',
            'is_active.boolean' => 'El estado de la guía no es válido.',
        ];
    }
}