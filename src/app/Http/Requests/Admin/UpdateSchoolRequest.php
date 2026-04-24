<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $schoolId = $this->route('school');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('schools', 'slug')->ignore($schoolId)],
            'primary_color' => ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'secondary_color' => ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'accent_color' => ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'is_active' => ['required', 'boolean'],
            'shield' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del colegio es obligatorio.',
            'name.string' => 'El nombre del colegio debe ser un texto válido.',
            'name.max' => 'El nombre del colegio no puede superar los 255 caracteres.',

            'slug.string' => 'El slug debe ser un texto válido.',
            'slug.max' => 'El slug no puede superar los 255 caracteres.',
            'slug.unique' => 'Ya existe un colegio con ese slug.',

            'primary_color.required' => 'El color primario es obligatorio.',
            'primary_color.regex' => 'El color primario debe tener un formato hexadecimal válido.',

            'secondary_color.required' => 'El color secundario es obligatorio.',
            'secondary_color.regex' => 'El color secundario debe tener un formato hexadecimal válido.',

            'accent_color.required' => 'El color de acento es obligatorio.',
            'accent_color.regex' => 'El color de acento debe tener un formato hexadecimal válido.',

            'is_active.required' => 'Debes indicar el estado del colegio.',
            'is_active.boolean' => 'El estado del colegio no es válido.',

            'shield.image' => 'El escudo debe ser una imagen válida.',
            'shield.max' => 'El escudo no puede superar los 2 MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre del colegio',
            'slug' => 'slug',
            'primary_color' => 'color primario',
            'secondary_color' => 'color secundario',
            'accent_color' => 'color de acento',
            'is_active' => 'estado',
            'shield' => 'escudo',
        ];
    }
}