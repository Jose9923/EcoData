<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $authUser = $this->user();

        if (! $authUser?->hasAnyRole(['super_admin', 'admin_colegio'])) {
            return false;
        }

        if ($authUser->hasRole('super_admin')) {
            return true;
        }

        $targetUser = User::with('roles')->find($this->route('user'));

        if (! $targetUser) {
            return false;
        }

        return (int) $targetUser->school_id === (int) $authUser->school_id
            && ! $targetUser->hasRole('super_admin');
    }

    public function rules(): array
    {
        $authUser = $this->user();
        $userId = $this->route('user');

        $isSuperAdmin = $authUser?->hasRole('super_admin') ?? false;
        $effectiveSchoolId = $isSuperAdmin
            ? $this->input('school_id')
            : $authUser?->school_id;

        $allowedRolesRule = Rule::exists('roles', 'name');

        if (! $isSuperAdmin) {
            $allowedRolesRule = Rule::exists('roles', 'name')->where(fn ($query) => $query->where('name', '!=', 'super_admin'));
        }

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

            'document_type' => ['required', 'string', 'max:20'],

            'document_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('users', 'document_number')->ignore($userId),
            ],

            'school_id' => [
                $isSuperAdmin ? 'nullable' : 'required',
                'integer',
                $isSuperAdmin
                    ? Rule::exists('schools', 'id')
                    : Rule::in([(int) $authUser?->school_id]),
            ],

            'grade_id' => [
                'nullable',
                'integer',
                Rule::exists('grades', 'id')->where(function ($query) use ($effectiveSchoolId) {
                    if ($effectiveSchoolId) {
                        $query->where('school_id', $effectiveSchoolId);
                    }
                }),
            ],

            'course_id' => [
                'nullable',
                'integer',
                Rule::exists('courses', 'id')->where(function ($query) use ($effectiveSchoolId) {
                    if ($effectiveSchoolId) {
                        $query->where('school_id', $effectiveSchoolId);
                    }

                    if ($this->grade_id) {
                        $query->where('grade_id', $this->grade_id);
                    }
                }),
            ],

            'role' => ['required', 'string', $allowedRolesRule],
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

            'document_type.required' => 'Debes seleccionar el tipo de identificación.',
            'document_number.required' => 'El número de identificación es obligatorio.',
            'document_number.unique' => 'Ya existe un usuario con ese número de identificación.',

            'password.string' => 'La contraseña debe ser un texto válido.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',

            'school_id.required' => 'Debes seleccionar el colegio.',
            'school_id.integer' => 'El colegio seleccionado no es válido.',
            'school_id.exists' => 'El colegio seleccionado no existe.',
            'school_id.in' => 'No puedes asignar usuarios a un colegio diferente al tuyo.',

            'grade_id.integer' => 'El grado seleccionado no es válido.',
            'grade_id.exists' => 'El grado seleccionado no pertenece al colegio indicado.',

            'course_id.integer' => 'El curso seleccionado no es válido.',
            'course_id.exists' => 'El curso seleccionado no pertenece al colegio o grado indicado.',

            'role.required' => 'Debes seleccionar un rol.',
            'role.string' => 'El rol seleccionado no es válido.',
            'role.exists' => 'El rol seleccionado no existe o no está permitido para tu usuario.',

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
            'document_type' => 'tipo de identificación',
            'document_number' => 'número de identificación',
            'password_confirmation' => 'confirmación de contraseña',
            'school_id' => 'colegio',
            'grade_id' => 'grado',
            'course_id' => 'curso',
            'role' => 'rol',
            'is_active' => 'estado',
        ];
    }
}