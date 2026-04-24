<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'school_id' => ['nullable', 'integer', 'exists:schools,id'],
            'grade_id' => [
                'nullable',
                'integer',
                Rule::exists('grades', 'id')->where(function ($query) {
                    if ($this->school_id) {
                        $query->where('school_id', $this->school_id);
                    }
                }),
            ],
            'course_id' => [
                'nullable',
                'integer',
                Rule::exists('courses', 'id')->where(function ($query) {
                    if ($this->school_id) {
                        $query->where('school_id', $this->school_id);
                    }

                    if ($this->grade_id) {
                        $query->where('grade_id', $this->grade_id);
                    }
                }),
            ],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del usuario es obligatorio.',
            'name.string' => 'El nombre del usuario debe ser un texto válido.',
            'name.max' => 'El nombre del usuario no puede superar los 255 caracteres.',

            'email.required' => 'El correo electrónico es obligatorio.',
            'email.string' => 'El correo electrónico debe ser un texto válido.',
            'email.email' => 'Debes ingresar un correo electrónico válido.',
            'email.max' => 'El correo electrónico no puede superar los 255 caracteres.',
            'email.unique' => 'Ya existe un usuario con ese correo electrónico.',

            'password.string' => 'La contraseña debe ser un texto válido.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',

            'school_id.integer' => 'El colegio seleccionado no es válido.',
            'school_id.exists' => 'El colegio seleccionado no existe.',

            'grade_id.integer' => 'El grado seleccionado no es válido.',
            'grade_id.exists' => 'El grado seleccionado no pertenece al colegio indicado.',

            'course_id.integer' => 'El curso seleccionado no es válido.',
            'course_id.exists' => 'El curso seleccionado no pertenece al colegio o grado indicado.',

            'role.required' => 'Debes seleccionar un rol.',
            'role.string' => 'El rol seleccionado no es válido.',
            'role.exists' => 'El rol seleccionado no existe.',

            'is_active.required' => 'Debes indicar el estado del usuario.',
            'is_active.boolean' => 'El estado del usuario no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo electrónico',
            'password' => 'contraseña',
            'password_confirmation' => 'confirmación de contraseña',
            'school_id' => 'colegio',
            'grade_id' => 'grado',
            'course_id' => 'curso',
            'role' => 'rol',
            'is_active' => 'estado',
        ];
    }
}