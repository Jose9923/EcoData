<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin_colegio']) ?? false;
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
                    ? Rule::exists('schools', 'id')
                    : Rule::in([(int) $authUser?->school_id]),
            ],

            'grade_id' => [
                'required',
                'integer',
                Rule::exists('grades', 'id')->where(function ($query) use ($effectiveSchoolId) {
                    $query->where('school_id', $effectiveSchoolId)
                        ->where('is_active', true);
                }),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('courses', 'name')->where(function ($query) use ($effectiveSchoolId) {
                    $query->where('school_id', $effectiveSchoolId)
                        ->where('grade_id', $this->input('grade_id'));
                }),
            ],

            'label' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'school_id.required' => 'Debes seleccionar un colegio.',
            'school_id.integer' => 'El colegio seleccionado no es válido.',
            'school_id.exists' => 'El colegio seleccionado no existe.',
            'school_id.in' => 'No puedes gestionar cursos de un colegio diferente al tuyo.',

            'grade_id.required' => 'Debes seleccionar un grado.',
            'grade_id.integer' => 'El grado seleccionado no es válido.',
            'grade_id.exists' => 'El grado seleccionado no pertenece al colegio indicado o está inactivo.',

            'name.required' => 'El nombre del curso es obligatorio.',
            'name.string' => 'El nombre del curso debe ser un texto válido.',
            'name.max' => 'El nombre del curso no puede superar los 255 caracteres.',
            'name.unique' => 'Ya existe un curso con ese nombre en el grado seleccionado.',

            'label.string' => 'La etiqueta del curso debe ser un texto válido.',
            'label.max' => 'La etiqueta del curso no puede superar los 255 caracteres.',

            'is_active.required' => 'Debes indicar el estado del curso.',
            'is_active.boolean' => 'El estado del curso no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'school_id' => 'colegio',
            'grade_id' => 'grado',
            'name' => 'nombre del curso',
            'label' => 'etiqueta',
            'is_active' => 'estado',
        ];
    }
}