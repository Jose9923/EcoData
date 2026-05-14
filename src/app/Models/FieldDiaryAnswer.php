<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldDiaryAnswer extends Model
{
    protected $fillable = [
        'field_diary_submission_id',
        'field_diary_question_id',
        'answer_text',
        'answer_file_path',
    ];

    public function submission()
    {
        return $this->belongsTo(FieldDiarySubmission::class, 'field_diary_submission_id');
    }

    public function question()
    {
        return $this->belongsTo(FieldDiaryQuestion::class, 'field_diary_question_id');
    }
}