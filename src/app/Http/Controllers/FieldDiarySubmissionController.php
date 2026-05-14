<?php

namespace App\Http\Controllers;

use App\Models\FieldDiaryActivity;
use App\Models\FieldDiarySubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class FieldDiarySubmissionController extends Controller
{
    public function index(Request $request): View
    {
        $authUser = $request->user();

        abort_if(! $authUser->school_id, 403, 'Tu usuario no tiene un colegio asignado.');

        $today = now()->toDateString();

        $activities = FieldDiaryActivity::query()
            ->with([
                'school',
                'grade',
                'course',
                'weatherStation',
                'questions',
                'submissions' => fn ($query) => $query->where('user_id', $authUser->id),
            ])
            ->withCount('questions')
            ->where('is_active', true)
            ->where('school_id', $authUser->school_id)
            ->where(function ($query) use ($authUser) {
                $query->whereNull('grade_id')
                    ->orWhere('grade_id', $authUser->grade_id);
            })
            ->where(function ($query) use ($authUser) {
                $query->whereNull('course_id')
                    ->orWhere('course_id', $authUser->course_id);
            })
            ->where(function ($query) use ($today) {
                $query->whereNull('starts_at')
                    ->orWhereDate('starts_at', '<=', $today);
            })
            ->where(function ($query) use ($today) {
                $query->whereNull('ends_at')
                    ->orWhereDate('ends_at', '>=', $today);
            })
            ->latest()
            ->get();

        $mySubmissions = FieldDiarySubmission::query()
            ->with(['activity.weatherStation', 'activity.questions'])
            ->where('user_id', $authUser->id)
            ->latest()
            ->get();

        return view('estudiante.field-diaries.index', [
            'activities' => $activities,
            'mySubmissions' => $mySubmissions,
        ]);
    }

    public function show(Request $request, FieldDiaryActivity $field_diary_activity): View
    {
        $authUser = $request->user();

        $this->authorizeStudentActivity($authUser, $field_diary_activity);

        $field_diary_activity->load([
            'school',
            'grade',
            'course',
            'weatherStation',
            'questions',
        ]);

        $submission = FieldDiarySubmission::query()
            ->with('answers.question')
            ->where('field_diary_activity_id', $field_diary_activity->id)
            ->where('user_id', $authUser->id)
            ->first();

        $answersByQuestion = $submission
            ? $submission->answers->keyBy('field_diary_question_id')
            : collect();

        return view('estudiante.field-diaries.show', [
            'activity' => $field_diary_activity,
            'submission' => $submission,
            'answersByQuestion' => $answersByQuestion,
        ]);
    }

    public function save(Request $request, FieldDiaryActivity $field_diary_activity): RedirectResponse
    {
        return $this->persistSubmission($request, $field_diary_activity, 'borrador');
    }

    public function submit(Request $request, FieldDiaryActivity $field_diary_activity): RedirectResponse
    {
        return $this->persistSubmission($request, $field_diary_activity, 'enviado');
    }

    private function persistSubmission(Request $request, FieldDiaryActivity $activity, string $status): RedirectResponse
    {
        $authUser = $request->user();

        $this->authorizeStudentActivity($authUser, $activity);

        $activity->load('questions');

        $existingSubmission = FieldDiarySubmission::query()
            ->where('field_diary_activity_id', $activity->id)
            ->where('user_id', $authUser->id)
            ->first();

        if ($existingSubmission && in_array($existingSubmission->status, ['enviado', 'revisado'], true)) {
            return back()
                ->with('warning', 'Esta actividad ya fue enviada o revisada y no puede modificarse.')
                ->withInput();
        }

        $rules = [];

        foreach ($activity->questions as $question) {
            $baseKey = 'answers.' . $question->id;

            if ($question->question_type === 'file') {
                $rules['files.' . $question->id] = [
                    $question->is_required && $status === 'enviado' && ! $this->hasExistingFile($existingSubmission, $question->id)
                        ? 'required'
                        : 'nullable',
                    'file',
                    'max:5120',
                    'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,csv',
                ];
                continue;
            }

            $rules[$baseKey] = $this->rulesForQuestion($question, $status);
        }

        $messages = [
            'required' => 'Este campo es obligatorio.',
            'file' => 'Debes subir un archivo válido.',
            'mimes' => 'El archivo debe estar en un formato permitido.',
            'max' => 'El archivo no puede superar los 5 MB.',
            'numeric' => 'La respuesta debe ser numérica.',
            'date' => 'La respuesta debe ser una fecha válida.',
        ];

        $validated = $request->validate($rules, $messages);

        DB::transaction(function () use ($request, $activity, $authUser, $status, &$existingSubmission) {
            $submission = FieldDiarySubmission::updateOrCreate(
                [
                    'field_diary_activity_id' => $activity->id,
                    'user_id' => $authUser->id,
                ],
                [
                    'school_id' => $authUser->school_id,
                    'grade_id' => $authUser->grade_id,
                    'course_id' => $authUser->course_id,
                    'status' => $status,
                    'submitted_at' => $status === 'enviado' ? now() : $existingSubmission?->submitted_at,
                ]
            );

            foreach ($activity->questions as $question) {
                $answerText = null;
                $filePath = null;

                if ($question->question_type === 'file') {
                    $uploadedFile = $request->file('files.' . $question->id);

                    $existingAnswer = $submission->answers()
                        ->where('field_diary_question_id', $question->id)
                        ->first();

                    if ($uploadedFile) {
                        if ($existingAnswer?->answer_file_path && Storage::disk('public')->exists($existingAnswer->answer_file_path)) {
                            Storage::disk('public')->delete($existingAnswer->answer_file_path);
                        }

                        $filePath = $uploadedFile->storeAs(
                            'field-diaries/user-' . $authUser->id . '/activity-' . $activity->id,
                            now()->format('Ymd_His') . '_' . $uploadedFile->getClientOriginalName(),
                            'public'
                        );
                    } else {
                        $filePath = $existingAnswer?->answer_file_path;
                    }
                } else {
                    $rawAnswer = $request->input('answers.' . $question->id);

                    if (is_array($rawAnswer)) {
                        $answerText = json_encode(array_values($rawAnswer), JSON_UNESCAPED_UNICODE);
                    } else {
                        $answerText = filled($rawAnswer) ? trim((string) $rawAnswer) : null;
                    }
                }

                $submission->answers()->updateOrCreate(
                    [
                        'field_diary_question_id' => $question->id,
                    ],
                    [
                        'answer_text' => $answerText,
                        'answer_file_path' => $filePath,
                    ]
                );
            }
        });

        return redirect()
            ->route('estudiante.field-diaries.show', $activity)
            ->with(
                'success',
                $status === 'enviado'
                    ? 'Actividad enviada correctamente.'
                    : 'Borrador guardado correctamente.'
            );
    }

    private function rulesForQuestion($question, string $status): array
    {
        $rules = [];

        if ($question->is_required && $status === 'enviado') {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        return match ($question->question_type) {
            'number' => [...$rules, 'numeric'],
            'date' => [...$rules, 'date'],
            'checkbox' => [...$rules, 'array'],
            default => [...$rules, 'string'],
        };
    }

    private function hasExistingFile(?FieldDiarySubmission $submission, int $questionId): bool
    {
        if (! $submission) {
            return false;
        }

        return $submission->answers()
            ->where('field_diary_question_id', $questionId)
            ->whereNotNull('answer_file_path')
            ->exists();
    }

    private function authorizeStudentActivity($authUser, FieldDiaryActivity $activity): void
    {
        abort_if(! $authUser->school_id, 403, 'Tu usuario no tiene un colegio asignado.');

        abort_if(! $activity->is_active, 404);

        abort_if((int) $activity->school_id !== (int) $authUser->school_id, 403);

        if ($activity->grade_id !== null) {
            abort_if((int) $activity->grade_id !== (int) $authUser->grade_id, 403);
        }

        if ($activity->course_id !== null) {
            abort_if((int) $activity->course_id !== (int) $authUser->course_id, 403);
        }

        $today = now()->toDateString();

        if ($activity->starts_at) {
            abort_if($activity->starts_at->toDateString() > $today, 403, 'Esta actividad aún no está disponible.');
        }

        if ($activity->ends_at) {
            abort_if($activity->ends_at->toDateString() < $today, 403, 'Esta actividad ya cerró.');
        }
    }
}