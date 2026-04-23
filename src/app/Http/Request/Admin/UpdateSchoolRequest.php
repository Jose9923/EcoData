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
}