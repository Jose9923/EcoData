<?php

namespace App\Http\Requests\Admin;

use App\Models\Sensor;
use App\Models\WeatherStation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSensorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $authUser = $this->user();

        if (! $authUser?->hasAnyRole(['super_admin', 'admin_colegio', 'docente'])) {
            return false;
        }

        if ($authUser->hasRole('super_admin')) {
            return true;
        }

        $sensor = $this->route('sensor');

        if (! $sensor instanceof Sensor) {
            $sensor = Sensor::with('weatherStation')->find($sensor);
        } else {
            $sensor->loadMissing('weatherStation');
        }

        return $sensor && (int) $sensor->weatherStation?->school_id === (int) $authUser->school_id;
    }

    public function rules(): array
    {
        $authUser = $this->user();

        $sensorId = $this->route('sensor') instanceof Sensor
            ? $this->route('sensor')->id
            : $this->route('sensor');

        $stationId = $this->integer('weather_station_id');

        $station = $stationId
            ? WeatherStation::query()->find($stationId)
            : null;

        $schoolId = $station?->school_id;

        return [
            'weather_station_id' => [
                'required',
                'integer',
                Rule::exists('weather_stations', 'id')->where(function ($query) use ($authUser) {
                    $query->where('is_active', true);

                    if (! $authUser->hasRole('super_admin')) {
                        $query->where('school_id', $authUser->school_id);
                    }
                }),
            ],

            'physical_variable_id' => [
                'nullable',
                'integer',
                Rule::exists('physical_variables', 'id')->where(function ($query) use ($schoolId) {
                    $query->where('is_active', true);

                    if ($schoolId) {
                        $query->where('school_id', $schoolId);
                    }
                }),
            ],

            'name' => ['required', 'string', 'max:255'],

            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('sensors', 'code')
                    ->where(fn ($query) => $query->where('weather_station_id', $stationId))
                    ->ignore($sensorId),
            ],

            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'measurement_unit' => ['nullable', 'string', 'max:50'],
            'measurement_range' => ['nullable', 'string', 'max:255'],
            'accuracy' => ['nullable', 'string', 'max:255'],
            'installation_date' => ['nullable', 'date'],
            'last_maintenance_date' => ['nullable', 'date'],
            'next_maintenance_date' => ['nullable', 'date', 'after_or_equal:last_maintenance_date'],
            'status' => ['required', Rule::in(Sensor::STATUSES)],
            'observations' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'weather_station_id' => 'estación meteorológica',
            'physical_variable_id' => 'variable física',
            'name' => 'nombre',
            'code' => 'código',
            'brand' => 'marca',
            'model' => 'modelo',
            'serial_number' => 'número de serie',
            'measurement_unit' => 'unidad de medida',
            'measurement_range' => 'rango de medición',
            'accuracy' => 'precisión',
            'installation_date' => 'fecha de instalación',
            'last_maintenance_date' => 'último mantenimiento',
            'next_maintenance_date' => 'próximo mantenimiento',
            'status' => 'estado',
            'observations' => 'observaciones',
            'is_active' => 'estado activo',
        ];
    }

    public function messages(): array
    {
        return [
            'weather_station_id.required' => 'Debes seleccionar una estación meteorológica.',
            'weather_station_id.exists' => 'La estación seleccionada no existe, está inactiva o no pertenece a tu colegio.',
            'name.required' => 'Debes ingresar el nombre del sensor.',
            'code.required' => 'Debes ingresar un código para el sensor.',
            'code.unique' => 'Ya existe un sensor con este código en la estación seleccionada.',
            'status.required' => 'Debes seleccionar el estado del sensor.',
            'status.in' => 'El estado seleccionado no es válido.',
            'next_maintenance_date.after_or_equal' => 'La fecha del próximo mantenimiento no puede ser anterior al último mantenimiento.',
        ];
    }
}
