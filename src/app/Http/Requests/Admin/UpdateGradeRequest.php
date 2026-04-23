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
}