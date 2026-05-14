<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldDiarySubmission extends Model
{
    public const STATUSES = [
        'borrador',
        'enviado',
        'revisado',
        'devuelto',
    ];

    protected $fillable = [
        'field_diary_activity_id',
        'user_id',
        'school_id',
        'grade_id',
        'course_id',
        'status',
        'submitted_at',
        'teacher_feedback',
        'score',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'score' => 'decimal:2',
        ];
    }

    public function activity()
    {
        return $this->belongsTo(FieldDiaryActivity::class, 'field_diary_activity_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
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

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function answers()
    {
        return $this->hasMany(FieldDiaryAnswer::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'borrador' => 'Borrador',
            'enviado' => 'Enviado',
            'revisado' => 'Revisado',
            'devuelto' => 'Devuelto',
            default => 'Sin estado',
        };
    }
}