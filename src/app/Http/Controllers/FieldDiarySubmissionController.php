<?php

namespace App\Http\Controllers;

use App\Models\FieldDiaryActivity;
use App\Models\FieldDiarySubmission;
use App\Services\FieldDiaryAccess;
use App\Services\FieldDiaryRevision;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
            ->where('school_id', $authUser->school_id)
            ->whereHas('activity', fn ($query) => $query->where('school_id', $authUser->school_id))
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

        app(FieldDiaryAccess::class)->authorizeStudent($authUser, $field_diary_activity);

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
            ->where('school_id', $authUser->school_id)
            ->first();

        $isOpen = app(FieldDiaryAccess::class)->isOpen($field_diary_activity);
        abort_unless($isOpen || $submission, 403, 'Esta actividad no está disponible.');

        $answersByQuestion = $submission
            ? $this->answersForActivity($submission, $field_diary_activity)
            : collect();

        return view('estudiante.field-diaries.show', [
            'activity' => $field_diary_activity,
            'submission' => $submission,
            'answersByQuestion' => $answersByQuestion,
            'canEdit' => $isOpen && (! $submission || in_array($submission->status, ['borrador', 'devuelto'], true)),
            'isOpen' => $isOpen,
            'revision' => app(FieldDiaryRevision::class)->token($field_diary_activity, $submission),
        ]);
    }

    public function save(Request $request, FieldDiaryActivity $field_diary_activity): RedirectResponse|JsonResponse
    {
        return $this->persistSubmission($request, $field_diary_activity, 'borrador');
    }

    public function submit(Request $request, FieldDiaryActivity $field_diary_activity): RedirectResponse|JsonResponse
    {
        return $this->persistSubmission($request, $field_diary_activity, 'enviado');
    }

    private function persistSubmission(Request $request, FieldDiaryActivity $activity, string $status): RedirectResponse|JsonResponse
    {
        $authUser = $request->user();
        app(FieldDiaryAccess::class)->authorizeWrite($authUser, $activity);
        $request->validate([
            'revision' => ['required', 'string', 'size:64'],
            'autosave' => ['sometimes', 'boolean'],
            'answers' => ['sometimes', 'array'],
            'files' => ['sometimes', 'array'],
        ], ['revision.required' => 'Recarga la página antes de guardar esta actividad.']);
        $autosave = $request->boolean('autosave');
        if ($autosave && ($status !== 'borrador' || $request->allFiles())) {
            throw ValidationException::withMessages(['files' => 'Los archivos se guardan con Guardar borrador o Enviar respuestas.']);
        }

        $newFiles = [];
        try {
            $result = DB::transaction(function () use ($request, $activity, $authUser, $status, $autosave, &$newFiles) {
                // All activity edits and student writes acquire these locks in the same order.
                $activity = FieldDiaryActivity::query()->lockForUpdate()->findOrFail($activity->id);
                app(FieldDiaryAccess::class)->authorizeWrite($authUser, $activity);
                $activity->load('questions');
                $submission = FieldDiarySubmission::query()
                    ->where('field_diary_activity_id', $activity->id)
                    ->where('user_id', $authUser->id)
                    ->lockForUpdate()->first();
                abort_if($submission && (int) $submission->school_id !== (int) $authUser->school_id, 403);
                if ($submission && ! in_array($submission->status, ['borrador', 'devuelto'], true)) {
                    $this->conflict($request, 'La entrega ya fue enviada o revisada. Recarga la página para consultar su estado.');
                }
                $revision = app(FieldDiaryRevision::class);
                if (! hash_equals($revision->token($activity, $submission), (string) $request->input('revision'))) {
                    $this->conflict($request, 'El diario cambió en otra sesión o fue revisado. Copia tus cambios y recarga antes de continuar.');
                }

                $allowedIds = $activity->questions->pluck('id')->map(fn ($id) => (string) $id)->all();
                foreach (['answers', 'files'] as $key) {
                    $received = $key === 'files' ? $request->file('files', []) : $request->input('answers', []);
                    if (array_diff(array_map('strval', array_keys($received)), $allowedIds)) {
                        throw ValidationException::withMessages([$key => 'Una respuesta no corresponde a esta actividad.']);
                    }
                }

                $rules = [];
                foreach ($activity->questions as $question) {
                    if ($question->question_type === 'file') {
                        if (! $autosave) {
                            $rules['files.'.$question->id] = [
                                $question->is_required && $status === 'enviado' && ! $this->hasExistingFile($submission, $question->id) ? 'required' : 'nullable',
                                'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,csv',
                            ];
                        }

                        continue;
                    }
                    if ($autosave && ! array_key_exists($question->id, $request->input('answers', []))) {
                        continue;
                    }
                    $key = 'answers.'.$question->id;
                    $rules[$key] = $this->rulesForQuestion($question, $status);
                    if ($question->question_type === 'checkbox') {
                        $rules[$key.'.*'] = ['string', Rule::in($question->options ?? [])];
                    }
                }
                $request->validate($rules, [
                    'required' => 'Este campo es obligatorio.',
                    'file' => 'Debes subir un archivo válido.',
                    'mimes' => 'El archivo debe estar en un formato permitido.',
                    'max' => 'El archivo no puede superar los 5 MB.',
                    'numeric' => 'La respuesta debe ser numérica.',
                    'date' => 'La respuesta debe ser una fecha válida.',
                    'in' => 'Selecciona una de las opciones de la pregunta.',
                ]);

                $submission ??= new FieldDiarySubmission([
                    'field_diary_activity_id' => $activity->id,
                    'user_id' => $authUser->id,
                    'school_id' => $authUser->school_id,
                    'grade_id' => $authUser->grade_id,
                    'course_id' => $authUser->course_id,
                ]);
                $submission->status = $status === 'enviado' ? 'enviado' : ($submission->status === 'devuelto' ? 'devuelto' : 'borrador');
                if ($status === 'enviado') {
                    $submission->submitted_at = now();
                    $submission->score = null;
                    $submission->reviewed_by = null;
                    $submission->reviewed_at = null;
                }
                $submission->save();

                foreach ($activity->questions as $question) {
                    if ($question->question_type === 'file') {
                        $uploadedFile = $request->file('files.'.$question->id);
                        if ($autosave || ! $uploadedFile) {
                            continue;
                        }
                        $oldPath = $submission->answers()->where('field_diary_question_id', $question->id)->value('answer_file_path');
                        // Unique file names per question avoid collisions between evidence uploads.
                        $path = $uploadedFile->store('field-diaries/user-'.$authUser->id.'/activity-'.$activity->id.'/question-'.$question->id, 'local');
                        abort_unless($path, 500, 'No se pudo guardar el archivo. Intenta nuevamente.');
                        $newFiles[] = $path;
                        $payload = ['answer_text' => null, 'answer_file_path' => $path];
                        if ($oldPath) {
                            DB::afterCommit(function () use ($oldPath) {
                                try {
                                    $this->deleteStoredAnswerFile($oldPath);
                                } catch (\Throwable $exception) {
                                    // Cleanup failure must never remove the newly committed evidence.
                                    report($exception);
                                }
                            });
                        }
                    } else {
                        if ($autosave && ! array_key_exists($question->id, $request->input('answers', []))) {
                            continue;
                        }
                        $raw = $request->input('answers.'.$question->id);
                        $payload = [
                            'answer_text' => is_array($raw) ? json_encode(array_values($raw), JSON_UNESCAPED_UNICODE) : (filled($raw) ? trim((string) $raw) : null),
                            'answer_file_path' => null,
                        ];
                    }
                    $submission->answers()->updateOrCreate(['field_diary_question_id' => $question->id], $payload);
                }
                $submission->unsetRelation('answers');

                return [
                    'revision' => $revision->token($activity, $submission),
                    'status' => $submission->status,
                    'saved_at' => now()->toIso8601String(),
                ];
            });
        } catch (\Throwable $exception) {
            foreach ($newFiles as $path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        $message = $status === 'enviado' ? 'Actividad enviada correctamente.' : 'Borrador guardado correctamente.';
        if ($request->expectsJson()) {
            return response()->json([...$result, 'message' => $message]);
        }

        return redirect()->route('estudiante.field-diaries.show', $activity)->with('success', $message);
    }

    private function conflict(Request $request, string $message): never
    {
        if ($request->expectsJson()) {
            throw new HttpResponseException(response()->json(['message' => $message, 'reload_required' => true], 409));
        }
        throw ValidationException::withMessages(['revision' => $message]);
    }

    private function rulesForQuestion($question, string $status): array
    {
        $rules = [$question->is_required && $status === 'enviado' ? 'required' : 'nullable'];

        return match ($question->question_type) {
            'number' => [...$rules, 'numeric'],
            'date' => [...$rules, 'date'],
            'checkbox' => [...$rules, 'array'],
            'select', 'radio' => [...$rules, 'string', Rule::in($question->options ?? [])],
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

    private function answersForActivity(FieldDiarySubmission $submission, FieldDiaryActivity $activity)
    {
        return $submission->answers
            ->filter(fn ($answer) => (int) $answer->question?->field_diary_activity_id === (int) $activity->id)
            ->keyBy('field_diary_question_id');
    }

    private function deleteStoredAnswerFile(?string $path): void
    {
        if (! $path) {
            return;
        }

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }
        }
    }
}
