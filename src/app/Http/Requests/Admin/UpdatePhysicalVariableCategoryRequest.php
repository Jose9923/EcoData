<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePhysicalVariableCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super_admin') ?? false;
    }

    public function rules(): array
    {
        $categoryId = $this->route('physical_variable_category');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('physical_variable_categories', 'slug')->ignore($categoryId),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la categoría es obligatorio.',
            'name.string' => 'El nombre de la categoría debe ser un texto válido.',
            'name.max' => 'El nombre de la categoría no puede superar los 255 caracteres.',

            'slug.string' => 'El slug debe ser un texto válido.',
            'slug.max' => 'El slug no puede superar los 255 caracteres.',
            'slug.unique' => 'Ya existe una categoría con ese slug.',

            'description.string' => 'La descripción debe ser un texto válido.',

            'is_active.required' => 'Debes indicar el estado de la categoría.',
            'is_active.boolean' => 'El estado de la categoría no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre de la categoría',
            'slug' => 'slug',
            'description' => 'descripción',
            'is_active' => 'estado',
        ];
    }
}