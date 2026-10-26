<?php

namespace App\Http\Requests\Admin;

use App\Support\MeasurementDecimal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePhysicalVariableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin_colegio']) ?? false;
    }

    public function rules(): array
    {
        $authUser = $this->user();

        $isSuperAdmin = $authUser?->hasRole('super_admin') ?? false;

        $effectiveSchoolId = $isSuperAdmin
            ? $this->input('school_id')
            : $authUser?->school_id;

        return [
            'school_id' => [
                'required',
                'integer',
                $isSuperAdmin
                    ? Rule::exists('schools', 'id')->where(fn ($query) => $query->where('is_active', true))
                    : Rule::in([(int) $authUser?->school_id]),
            ],

            'category_id' => [
                'required',
                'integer',
                Rule::exists('physical_variable_categories', 'id')
                    ->where(fn ($query) => $query->where('is_active', true)),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('physical_variables', 'slug')
                    ->where(fn ($query) => $query->where('school_id', $effectiveSchoolId)),
            ],

            'unit' => ['nullable', 'string', 'max:50'],

            'data_type' => [
                'required',
                Rule::in(['decimal', 'integer', 'text', 'boolean', 'date']),
            ],

            'min_value' => [
                'nullable',
                'numeric',
                'required_if:data_type,decimal,integer',
            ],

            'max_value' => [
                'nullable',
                'numeric',
                'required_if:data_type,decimal,integer',
            ],

            'decimals' => [
                'nullable',
                'integer',
                'min:0',
                'max:4',
            ],

            'description' => ['nullable', 'string'],

            'is_active' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $type = $this->input('data_type');
            if (! in_array($type, ['decimal', 'integer'], true)) {
                return;
            }
            $precision = $type === 'integer' ? 0 : min(4, max(0, (int) $this->input('decimals')));
            $bounds = [];
            foreach (['min_value', 'max_value'] as $key) {
                if ($this->input($key) === null || $this->input($key) === '') {
                    continue;
                }
                try {
                    $bounds[$key] = MeasurementDecimal::normalize($this->input($key), $precision);
                } catch (\InvalidArgumentException $error) {
                    $validator->errors()->add($key, $error->getMessage());
                }
            }
            if (count($bounds) === 2 && MeasurementDecimal::compare($bounds['min_value'], $bounds['max_value']) > 0) {
                $validator->errors()->add('min_value', 'El mínimo no puede ser mayor que el máximo.');
            }
            if ($type === 'integer' && (int) $this->input('decimals', 0) !== 0) {
                $validator->errors()->add('decimals', 'Las variables enteras no admiten decimales.');
            }
            if ($type === 'decimal' && $this->input('decimals') === null) {
                $validator->errors()->add('decimals', 'Debes indicar la cantidad de decimales.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'school_id.required' => 'Debes seleccionar un colegio.',
            'school_id.integer' => 'El colegio seleccionado no es válido.',
            'school_id.exists' => 'El colegio seleccionado no existe o está inactivo.',
            'school_id.in' => 'No puedes gestionar variables físicas de un colegio diferente al tuyo.',

            'category_id.required' => 'Debes seleccionar una categoría.',
            'category_id.integer' => 'La categoría seleccionada no es válida.',
            'category_id.exists' => 'La categoría seleccionada no existe o está inactiva.',

            'name.required' => 'El nombre de la variable es obligatorio.',
            'name.string' => 'El nombre de la variable debe ser un texto válido.',
            'name.max' => 'El nombre de la variable no puede superar los 255 caracteres.',

            'slug.string' => 'El slug debe ser un texto válido.',
            'slug.max' => 'El slug no puede superar los 255 caracteres.',
            'slug.unique' => 'Ya existe una variable con ese slug en el colegio seleccionado.',

            'unit.string' => 'La unidad debe ser un texto válido.',
            'unit.max' => 'La unidad no puede superar los 50 caracteres.',

            'data_type.required' => 'Debes seleccionar un tipo de dato.',
            'data_type.in' => 'El tipo de dato seleccionado no es válido.',

            'min_value.numeric' => 'El valor mínimo debe ser numérico.',
            'min_value.required_if' => 'Debes indicar el valor mínimo para variables numéricas.',

            'max_value.numeric' => 'El valor máximo debe ser numérico.',
            'max_value.required_if' => 'Debes indicar el valor máximo para variables numéricas.',

            'decimals.integer' => 'Los decimales deben ser un número entero.',
            'decimals.min' => 'Los decimales no pueden ser menores que 0.',
            'decimals.max' => 'Los decimales no pueden ser mayores que 4.',

            'description.string' => 'La descripción debe ser un texto válido.',

            'is_active.required' => 'Debes indicar el estado de la variable.',
            'is_active.boolean' => 'El estado de la variable no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'school_id' => 'colegio',
            'category_id' => 'categoría',
            'name' => 'nombre de la variable',
            'slug' => 'slug',
            'unit' => 'unidad',
            'data_type' => 'tipo de dato',
            'min_value' => 'valor mínimo',
            'max_value' => 'valor máximo',
            'decimals' => 'decimales',
            'description' => 'descripción',
            'is_active' => 'estado',
        ];
    }
}
