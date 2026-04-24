<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ImportUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin_colegio']) ?? false;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
            'mode' => ['required', 'in:create_only,update_or_create'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Debes seleccionar un archivo Excel.',
            'file.file' => 'El archivo cargado no es válido.',
            'file.mimes' => 'El archivo debe estar en formato Excel (.xlsx o .xls).',
            'file.max' => 'El archivo no puede superar los 10 MB.',
            'mode.required' => 'Debes seleccionar el modo de importación.',
            'mode.in' => 'El modo de importación no es válido.',
        ];
    }
}