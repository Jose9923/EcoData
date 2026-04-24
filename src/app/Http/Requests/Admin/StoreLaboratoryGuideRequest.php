<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLaboratoryGuideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'grade_id' => ['nullable', 'integer', 'exists:grades,id'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
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
            'school_id.exists' => 'El colegio seleccionado no existe.',
            'grade_id.exists' => 'El grado seleccionado no existe.',
            'course_id.exists' => 'El curso seleccionado no existe.',
            'title.required' => 'El título de la guía es obligatorio.',
            'pdf.required' => 'Debes cargar un archivo PDF.',
            'pdf.file' => 'El archivo cargado no es válido.',
            'pdf.mimes' => 'La guía debe estar en formato PDF.',
            'pdf.max' => 'El PDF no puede superar los 10 MB.',
            'published_at.date' => 'La fecha de publicación no es válida.',
            'is_active.required' => 'Debes indicar el estado de la guía.',
        ];
    }
}