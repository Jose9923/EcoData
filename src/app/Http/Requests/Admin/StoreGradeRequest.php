<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGradeRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('grades', 'name')->where(
                    fn ($query) => $query->where('school_id', $effectiveSchoolId)
                ),
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
            'school_id.in' => 'No puedes gestionar grados de un colegio diferente al tuyo.',

            'name.required' => 'El nombre del grado es obligatorio.',
            'name.string' => 'El nombre del grado debe ser un texto válido.',
            'name.max' => 'El nombre del grado no puede superar los 255 caracteres.',
            'name.unique' => 'Ya existe un grado con ese nombre en el colegio seleccionado.',

            'label.string' => 'La etiqueta del grado debe ser un texto válido.',
            'label.max' => 'La etiqueta del grado no puede superar los 255 caracteres.',

            'is_active.required' => 'Debes indicar el estado del grado.',
            'is_active.boolean' => 'El estado del grado no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'school_id' => 'colegio',
            'name' => 'nombre del grado',
            'label' => 'etiqueta',
            'is_active' => 'estado',
        ];
    }
}