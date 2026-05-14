<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'shield_path',
        'primary_color',
        'secondary_color',
        'accent_color',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function weatherStations()
    {
        return $this->hasMany(WeatherStation::class);
    }

    public function environmentalEvents()
    {
        return $this->hasMany(EnvironmentalEvent::class);
    }

    public function fieldDiaryActivities()
    {
        return $this->hasMany(FieldDiaryActivity::class);
    }

    public function fieldDiarySubmissions()
    {
        return $this->hasMany(FieldDiarySubmission::class);
    }
}