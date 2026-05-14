<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldDiaryQuestion extends Model
{
    public const QUESTION_TYPES = [
        'text',
        'textarea',
        'number',
        'date',
        'select',
        'radio',
        'checkbox',
        'file',
    ];

    protected $fillable = [
        'field_diary_activity_id',
        'question_text',
        'question_type',
        'options',
        'order',
        'is_required',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'order' => 'integer',
            'is_required' => 'boolean',
        ];
    }

    public function activity()
    {
        return $this->belongsTo(FieldDiaryActivity::class, 'field_diary_activity_id');
    }

    public function answers()
    {
        return $this->hasMany(FieldDiaryAnswer::class);
    }

    public function getQuestionTypeLabelAttribute(): string
    {
        return match ($this->question_type) {
            'text' => 'Respuesta corta',
            'textarea' => 'Respuesta larga',
            'number' => 'Número',
            'date' => 'Fecha',
            'select' => 'Lista desplegable',
            'radio' => 'Selección única',
            'checkbox' => 'Selección múltiple',
            'file' => 'Archivo / evidencia',
            default => 'Sin tipo',
        };
    }
}