<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldDiaryActivity extends Model
{
    public const ENTRY_TYPES = [
        'observacion',
        'reto',
        'portafolio',
    ];

    protected $fillable = [
        'school_id',
        'grade_id',
        'course_id',
        'weather_station_id',
        'created_by',
        'title',
        'description',
        'entry_type',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function weatherStation()
    {
        return $this->belongsTo(WeatherStation::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions()
    {
        return $this->hasMany(FieldDiaryQuestion::class)->orderBy('order');
    }

    public function submissions()
    {
        return $this->hasMany(FieldDiarySubmission::class);
    }

    public function getEntryTypeLabelAttribute(): string
    {
        return match ($this->entry_type) {
            'observacion' => 'Observación',
            'reto' => 'Reto',
            'portafolio' => 'Portafolio',
            default => 'Sin tipo',
        };
    }
}