<?php

namespace App\Http\Requests\Admin;

use App\Models\FieldDiaryActivity;
use App\Models\FieldDiaryQuestion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFieldDiaryActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin_colegio', 'docente']) ?? false;
    }

    public function rules(): array
    {
        $authUser = $this->user();

        $schoolId = $authUser->hasRole('super_admin')
            ? $this->integer('school_id')
            : $authUser->school_id;

        return [
            'school_id' => [
                $authUser->hasRole('super_admin') ? 'required' : 'nullable',
                'integer',
                Rule::exists('schools', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],

            'grade_id' => [
                'nullable',
                'integer',
                Rule::exists('grades', 'id')->where(function ($query) use ($schoolId) {
                    if ($schoolId) {
                        $query->where('school_id', $schoolId);
                    }

                    $query->where('is_active', true);
                }),
            ],

            'course_id' => [
                'nullable',
                'integer',
                Rule::exists('courses', 'id')->where(function ($query) use ($schoolId) {
                    if ($schoolId) {
                        $query->where('school_id', $schoolId);
                    }

                    $query->where('is_active', true);

                    if ($this->filled('grade_id')) {
                        $query->where('grade_id', $this->integer('grade_id'));
                    }
                }),
            ],

            'weather_station_id' => [
                'nullable',
                'integer',
                Rule::exists('weather_stations', 'id')->where(function ($query) use ($schoolId) {
                    if ($schoolId) {
                        $query->where('school_id', $schoolId);
                    }

                    $query->where('is_active', true);
                }),
            ],

            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'entry_type' => ['required', Rule::in(FieldDiaryActivity::ENTRY_TYPES)],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['required', 'boolean'],

            'questions' => ['required', 'array', 'min:1'],
            'questions.*.question_text' => ['required', 'string'],
            'questions.*.question_type' => ['required', Rule::in(FieldDiaryQuestion::QUESTION_TYPES)],
            'questions.*.options_text' => ['nullable', 'string'],
            'questions.*.order' => ['nullable', 'integer', 'min:1'],
            'questions.*.is_required' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ($this->input('questions', []) as $index => $question) {
                $type = $question['question_type'] ?? null;

                if (in_array($type, ['select', 'radio', 'checkbox'], true)) {
                    $options = trim((string) ($question['options_text'] ?? ''));

                    if ($options === '') {
                        $validator->errors()->add(
                            "questions.$index.options_text",
                            'Las preguntas de selección deben tener opciones, una por línea.'
                        );
                    }
                }
            }
        });
    }

    public function attributes(): array
    {
        return [
            'school_id' => 'colegio',
            'grade_id' => 'grado',
            'course_id' => 'curso',
            'weather_station_id' => 'estación meteorológica',
            'title' => 'título',
            'description' => 'descripción',
            'entry_type' => 'tipo de actividad',
            'starts_at' => 'fecha de inicio',
            'ends_at' => 'fecha de cierre',
            'is_active' => 'estado',
            'questions' => 'preguntas',
            'questions.*.question_text' => 'texto de la pregunta',
            'questions.*.question_type' => 'tipo de pregunta',
            'questions.*.options_text' => 'opciones',
            'questions.*.order' => 'orden',
            'questions.*.is_required' => 'obligatoria',
        ];
    }
}