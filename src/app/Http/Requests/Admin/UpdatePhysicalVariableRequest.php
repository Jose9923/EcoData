<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePhysicalVariableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $variableId = $this->route('physical_variable');

        return [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'category_id' => ['required', 'integer', 'exists:physical_variable_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('physical_variables', 'slug')->ignore($variableId),
            ],
            'unit' => ['nullable', 'string', 'max:255'],
            'data_type' => ['required', Rule::in(['decimal', 'integer', 'text', 'boolean', 'date'])],
            'min_value' => ['nullable'],
            'max_value' => ['nullable'],
            'decimals' => ['nullable', 'integer', 'min:0', 'max:10'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'school_id.required' => 'Debes seleccionar un colegio.',
            'school_id.integer' => 'El colegio seleccionado no es válido.',
            'school_id.exists' => 'El colegio seleccionado no existe.',

            'category_id.required' => 'Debes seleccionar una categoría.',
            'category_id.integer' => 'La categoría seleccionada no es válida.',
            'category_id.exists' => 'La categoría seleccionada no existe.',

            'name.required' => 'El nombre de la variable es obligatorio.',
            'name.string' => 'El nombre de la variable debe ser un texto válido.',
            'name.max' => 'El nombre de la variable no puede superar los 255 caracteres.',

            'slug.string' => 'El slug debe ser un texto válido.',
            'slug.max' => 'El slug no puede superar los 255 caracteres.',
            'slug.unique' => 'Ya existe una variable con ese slug.',

            'unit.string' => 'La unidad debe ser un texto válido.',
            'unit.max' => 'La unidad no puede superar los 255 caracteres.',

            'data_type.required' => 'Debes seleccionar un tipo de dato.',
            'data_type.in' => 'El tipo de dato seleccionado no es válido.',

            'decimals.integer' => 'Los decimales deben ser un número entero.',
            'decimals.min' => 'Los decimales no pueden ser menores que 0.',
            'decimals.max' => 'Los decimales no pueden ser mayores que 10.',

            'description.string' => 'La descripción debe ser un texto válido.',

            'is_active.required' => 'Debes indicar el estado de la variable.',
            'is_active.boolean' => 'El estado de la variable no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'school_id' => 'colegio',
            'category_id' => 'categoría',
            'name' => 'nombre de la variable',
            'slug' => 'slug',
            'unit' => 'unidad',
            'data_type' => 'tipo de dato',
            'min_value' => 'valor mínimo',
            'max_value' => 'valor máximo',
            'decimals' => 'decimales',
            'description' => 'descripción',
            'is_active' => 'estado',
        ];
    }
}