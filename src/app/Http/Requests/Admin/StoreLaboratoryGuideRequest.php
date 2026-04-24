<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLaboratoryGuideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin_colegio', 'teacher']) ?? false;
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
            'pdf.mimes' => 'La guía debe estar en formato PDF.',
            'pdf.max' => 'El PDF no puede superar los 10 MB.',

            'published_at.date' => 'La fecha de publicación no es válida.',

            'is_active.required' => 'Debes indicar el estado de la guía.',
            'is_active.boolean' => 'El estado de la guía no es válido.',
        ];
    }
}