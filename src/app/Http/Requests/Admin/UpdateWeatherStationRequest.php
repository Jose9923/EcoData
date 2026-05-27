<?php

namespace App\Http\Requests\Admin;

use App\Models\WeatherStation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWeatherStationRequest extends FormRequest
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

        $station = $this->route('weather_station');

        if (! $station instanceof WeatherStation) {
            $station = WeatherStation::find($station);
        }

        return $station && (int) $station->school_id === (int) $authUser->school_id;
    }

    public function rules(): array
    {
        $authUser = $this->user();

        $stationId = $this->route('weather_station') instanceof WeatherStation
            ? $this->route('weather_station')->id
            : $this->route('weather_station');

        $schoolId = $authUser->hasRole('super_admin')
            ? $this->integer('school_id')
            : $authUser->school_id;

        return [
            'school_id' => [
                $authUser->hasRole('super_admin') ? 'required' : 'nullable',
                'integer',
                $authUser->hasRole('super_admin')
                    ? Rule::exists('schools', 'id')->where(fn ($query) => $query->where('is_active', true))
                    : Rule::in([(int) $authUser->school_id]),
            ],

            'responsible_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) use ($schoolId) {
                    if ($schoolId) {
                        $query->where('school_id', $schoolId);
                    }

                    $query->where('is_active', true);
                }),
            ],

            'name' => ['required', 'string', 'max:255'],

            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('weather_stations', 'code')
                    ->where(fn ($query) => $query->where('school_id', $schoolId))
                    ->ignore($stationId),
            ],

            'location_name' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'altitude' => ['nullable', 'numeric', 'between:-500,9000'],
            'installation_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'school_id' => 'colegio',
            'responsible_user_id' => 'responsable',
            'name' => 'nombre',
            'code' => 'código',
            'location_name' => 'ubicación',
            'latitude' => 'latitud',
            'longitude' => 'longitud',
            'altitude' => 'altitud',
            'installation_date' => 'fecha de instalación',
            'description' => 'descripción',
            'is_active' => 'estado',
        ];
    }

    public function messages(): array
    {
        return [
            'school_id.in' => 'No puedes gestionar estaciones meteorológicas de un colegio diferente al tuyo.',
        ];
    }
}
