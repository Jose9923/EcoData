<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'grade_id' => [
                'required',
                'integer',
                Rule::exists('grades', 'id')->where(fn ($query) => $query->where('school_id', $this->school_id)),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('courses', 'name')->where(fn ($query) => $query
                    ->where('school_id', $this->school_id)
                    ->where('grade_id', $this->grade_id)),
            ],
            'label' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}