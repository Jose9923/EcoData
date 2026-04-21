<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhysicalVariableRecord extends Model
{
    protected $fillable = [
        'school_id',
        'grade_id',
        'course_id',
        'user_id',
        'recorded_at',
        'observations',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function values()
    {
        return $this->hasMany(PhysicalVariableRecordValue::class, 'physical_variable_record_id');
    }
}