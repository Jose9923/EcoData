<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sensor extends Model
{
    public const STATUSES = [
        'operativo',
        'mantenimiento',
        'fallando',
        'inactivo',
        'retirado',
    ];

    protected $fillable = [
        'weather_station_id',
        'physical_variable_id',
        'name',
        'code',
        'brand',
        'model',
        'serial_number',
        'measurement_unit',
        'measurement_range',
        'accuracy',
        'installation_date',
        'last_maintenance_date',
        'next_maintenance_date',
        'status',
        'observations',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'installation_date' => 'date',
            'last_maintenance_date' => 'date',
            'next_maintenance_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function weatherStation()
    {
        return $this->belongsTo(WeatherStation::class);
    }

    public function variable()
    {
        return $this->belongsTo(PhysicalVariable::class, 'physical_variable_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'operativo' => 'Operativo',
            'mantenimiento' => 'En mantenimiento',
            'fallando' => 'Fallando',
            'inactivo' => 'Inactivo',
            'retirado' => 'Retirado',
            default => 'Sin estado',
        };
    }
}