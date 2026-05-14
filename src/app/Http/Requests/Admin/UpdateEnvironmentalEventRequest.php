<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEnvironmentalEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin_colegio', 'docente']) ?? false;
    }

    public function rules(): array
    {
        $authUser = $this->user();

        return [
            'school_id' => [
                $authUser->hasRole('super_admin') ? 'required' : 'nullable',
                'integer',
                Rule::exists('schools', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'school_id' => 'colegio',
            'title' => 'título',
            'description' => 'descripción',
            'image' => 'imagen',
            'starts_at' => 'fecha de inicio',
            'ends_at' => 'fecha de finalización',
            'is_active' => 'estado',
        ];
    }

    public function messages(): array
    {
        return [
            'school_id.required' => 'Debes seleccionar un colegio.',
            'school_id.exists' => 'El colegio seleccionado no existe o está inactivo.',
            'title.required' => 'Debes ingresar el título del evento ambiental.',
            'starts_at.required' => 'Debes indicar la fecha de inicio.',
            'ends_at.required' => 'Debes indicar la fecha de finalización.',
            'ends_at.after_or_equal' => 'La fecha de finalización no puede ser anterior a la fecha de inicio.',
            'image.image' => 'El archivo debe ser una imagen válida.',
            'image.mimes' => 'La imagen debe estar en formato JPG, JPEG, PNG o WEBP.',
            'image.max' => 'La imagen no puede superar los 4 MB.',
        ];
    }
}