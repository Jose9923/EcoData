<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = [
        'school_id',
        'grade_id',
        'name',
        'label',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
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

    public function fieldDiaryActivities()
    {
        return $this->hasMany(FieldDiaryActivity::class);
    }

    public function fieldDiarySubmissions()
    {
        return $this->hasMany(FieldDiarySubmission::class);
    }
}