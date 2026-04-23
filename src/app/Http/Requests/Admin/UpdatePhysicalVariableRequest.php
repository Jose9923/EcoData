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
}