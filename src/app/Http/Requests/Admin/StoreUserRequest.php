<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
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
}