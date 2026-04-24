<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'document_type' => ['required', 'string', 'max:20'],
            'document_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('users', 'document_number')->ignore($userId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser un texto válido.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',

            'email.required' => 'El correo electrónico es obligatorio.',
            'email.string' => 'El correo electrónico debe ser un texto válido.',
            'email.email' => 'Debes ingresar un correo electrónico válido.',
            'email.max' => 'El correo electrónico no puede superar los 255 caracteres.',
            'email.unique' => 'Ya existe un usuario con ese correo electrónico.',
            'document_type.required' => 'El tipo de documento es obligatorio.',
            'document_type.string' => 'El tipo de documento debe ser un texto válido.',
            'document_type.max' => 'El tipo de documento no puede superar los 20 caracteres.',
            'document_number.required' => 'El número de documento es obligatorio.',
            'document_number.string' => 'El número de documento debe ser un texto válido.',
            'document_number.max' => 'El número de documento no puede superar los 50 caracteres.',
            'document_number.unique' => 'Ya existe un usuario con ese número de documento.',
        ];
    }
}