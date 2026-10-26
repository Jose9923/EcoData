<?php

namespace App\Services;

use App\Models\FieldDiaryActivity;
use App\Models\FieldDiarySubmission;

class FieldDiaryRevision
{
    /** Content fingerprints detect concurrent writes even within one timestamp second. */
    public function token(FieldDiaryActivity $activity, ?FieldDiarySubmission $submission): string
    {
        $activity->loadMissing('questions');
        $submission?->loadMissing('answers');

        return hash('sha256', json_encode([
            'activity' => $activity->only(['id', 'school_id', 'grade_id', 'course_id', 'title', 'description', 'entry_type', 'weather_station_id', 'starts_at', 'ends_at', 'is_active']),
            'questions' => $activity->questions->sortBy('id')->values()->map(fn ($question) => $question->only([
                'id', 'question_text', 'question_type', 'options', 'order', 'is_required',
            ]))->all(),
            'submission' => $submission?->only(['id', 'status', 'submitted_at', 'reviewed_at', 'reviewed_by', 'teacher_feedback', 'score']),
            'answers' => $submission?->answers->sortBy('id')->values()->map(fn ($answer) => $answer->only([
                'field_diary_question_id', 'answer_text', 'answer_file_path',
            ]))->all(),
        ], JSON_THROW_ON_ERROR));
    }
}
