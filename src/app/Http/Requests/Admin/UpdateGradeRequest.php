<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $gradeId = $this->route('grade');

        return [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('grades', 'name')
                    ->where(fn ($query) => $query->where('school_id', $this->school_id))
                    ->ignore($gradeId),
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