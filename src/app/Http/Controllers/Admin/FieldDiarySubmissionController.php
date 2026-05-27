<?php

namespace App\Http\Controllers\Admin;

use App\Exports\FieldDiarySubmissionsExport;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\FieldDiaryActivity;
use App\Models\FieldDiarySubmission;
use App\Models\Grade;
use App\Models\School;
use App\Models\User;
use App\Notifications\FieldDiarySubmissionReviewedNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class FieldDiarySubmissionController extends Controller
{
    public function index(Request $request): View
    {
        $authUser = $request->user();

        $filters = $this->filters($request);
        $filters['school_id'] = $this->effectiveSchoolId($authUser, $filters['school_id']);

        $query = FieldDiarySubmission::query()
            ->with([
                'activity.school',
                'activity.weatherStation',
                'student',
                'school',
                'grade',
                'course',
                'reviewer',
            ]);

        $this->applySubmissionSchoolScope($query, $filters['school_id']);

        $submissions = $query
            ->when($filters['grade_id'], fn ($query) => $query->where('grade_id', $filters['grade_id']))
            ->when($filters['course_id'], fn ($query) => $query->where('course_id', $filters['course_id']))
            ->when($filters['activity_id'], fn ($query) => $query->where('field_diary_activity_id', $filters['activity_id']))
            ->when($filters['status'], fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['student_id'], fn ($query) => $query->where('user_id', $filters['student_id']))
            ->when($filters['search'], function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->whereHas('activity', fn ($q) => $q->where('title', 'like', '%' . $search . '%'))
                        ->orWhereHas('student', fn ($q) => $q->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%')
                            ->orWhere('document_number', 'like', '%' . $search . '%'))
                        ->orWhereHas('school', fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
                        ->orWhereHas('grade', fn ($q) => $q->where('name', 'like', '%' . $search . '%')
                            ->orWhere('label', 'like', '%' . $search . '%'))
                        ->orWhereHas('course', fn ($q) => $q->where('name', 'like', '%' . $search . '%')
                            ->orWhere('label', 'like', '%' . $search . '%'));
                });
            })
            ->latest()
            ->paginate($filters['per_page'])
            ->appends($request->query());

        return view('admin.field-diary-submissions.index', [
            'submissions' => $submissions,
            'filters' => $filters,
            'schools' => $this->visibleSchools($authUser),
            'grades' => $this->visibleGrades($filters['school_id']),
            'courses' => $this->visibleCourses($filters['school_id'], $filters['grade_id']),
            'activities' => $this->visibleActivities($authUser, $filters['school_id'], $filters['grade_id'], $filters['course_id']),
            'students' => $this->visibleStudents($authUser, $filters['school_id'], $filters['grade_id'], $filters['course_id']),
            'statuses' => $this->statuses(),
        ]);
    }

    public function show(Request $request, FieldDiarySubmission $field_diary_submission): View
    {
        $authUser = $request->user();

        $this->authorizeSubmissionScope($authUser, $field_diary_submission);

        $field_diary_submission->load([
            'activity.school',
            'activity.grade',
            'activity.course',
            'activity.weatherStation',
            'activity.questions',
            'student',
            'school',
            'grade',
            'course',
            'reviewer',
            'answers.question',
        ]);

        $answersByQuestion = $field_diary_submission->answers
            ->filter(fn ($answer) => (int) $answer->question?->field_diary_activity_id === (int) $field_diary_submission->field_diary_activity_id)
            ->keyBy('field_diary_question_id');

        return view('admin.field-diary-submissions.show', [
            'submission' => $field_diary_submission,
            'activity' => $field_diary_submission->activity,
            'answersByQuestion' => $answersByQuestion,
            'statuses' => $this->statuses(),
        ]);
    }

    public function review(Request $request, FieldDiarySubmission $field_diary_submission): RedirectResponse
    {
        $authUser = $request->user();

        $this->authorizeSubmissionScope($authUser, $field_diary_submission);

        $data = $request->validate([
            'status' => ['required', Rule::in(['revisado', 'devuelto'])],
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'teacher_feedback' => ['nullable', 'string'],
        ], [
            'status.required' => 'Debes seleccionar el resultado de la revisión.',
            'status.in' => 'El estado seleccionado no es válido.',
            'score.numeric' => 'La nota debe ser numérica.',
            'score.min' => 'La nota no puede ser menor que 0.',
            'score.max' => 'La nota no puede ser mayor que 100.',
        ], [
            'status' => 'estado',
            'score' => 'nota',
            'teacher_feedback' => 'retroalimentación',
        ]);

        $field_diary_submission->update([
            'status' => $data['status'],
            'score' => $data['score'] ?? null,
            'teacher_feedback' => filled($data['teacher_feedback'] ?? null)
                ? trim($data['teacher_feedback'])
                : null,
            'reviewed_by' => $authUser->id,
            'reviewed_at' => now(),
        ]);

        $field_diary_submission->load(['student', 'activity']);

        if ($field_diary_submission->student && $field_diary_submission->student->is_active) {
            try {
                $field_diary_submission->student->notify(
                    new FieldDiarySubmissionReviewedNotification($field_diary_submission)
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }
        return redirect()
            ->route('admin.field-diary-submissions.show', $field_diary_submission)
            ->with(
                'success',
                $data['status'] === 'revisado'
                    ? 'Entrega revisada correctamente.'
                    : 'Entrega devuelta para corrección.'
            );
    }

    private function filters(Request $request): array
    {
        $perPage = (int) $request->integer('per_page', 10);

        if (! in_array($perPage, [10, 15, 25, 50], true)) {
            $perPage = 10;
        }

        return [
            'search' => (string) $request->string('search')->toString(),
            'school_id' => $request->integer('school_id') ?: null,
            'grade_id' => $request->integer('grade_id') ?: null,
            'course_id' => $request->integer('course_id') ?: null,
            'activity_id' => $request->integer('activity_id') ?: null,
            'student_id' => $request->integer('student_id') ?: null,
            'status' => $request->input('status') ?: null,
            'per_page' => $perPage,
        ];
    }

    private function effectiveSchoolId(User $authUser, ?int $requestedSchoolId): ?int
    {
        if ($authUser->hasRole('super_admin')) {
            return $requestedSchoolId;
        }

        abort_if(! $authUser->school_id, 403, 'Tu usuario no tiene un colegio asignado.');

        return (int) $authUser->school_id;
    }

    private function authorizeSubmissionScope(User $authUser, FieldDiarySubmission $submission): void
    {
        if ($authUser->hasRole('super_admin')) {
            return;
        }

        abort_if(! $authUser->school_id, 403, 'Tu usuario no tiene un colegio asignado.');

        $submission->loadMissing('activity');

        abort_if(
            (int) $submission->school_id !== (int) $authUser->school_id
                || (int) $submission->activity?->school_id !== (int) $authUser->school_id,
            403,
            'No tienes autorizacion para revisar entregas de otro colegio.'
        );
    }

    private function visibleSchools(User $authUser)
    {
        return School::query()
            ->where('is_active', true)
            ->when(! $authUser->hasRole('super_admin'), fn ($query) => $query->where('id', $authUser->school_id))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getGrades(Request $request): JsonResponse
    {
        $authUser = $request->user();
        $schoolId = $this->effectiveSchoolId($authUser, $request->integer('school_id') ?: null);

        $grades = $this->visibleGrades($schoolId)
            ->map(fn ($grade) => [
                'id' => $grade->id,
                'label' => $grade->label ?: $grade->name,
            ])
            ->values();

        return response()->json($grades);
    }

    public function getCourses(Request $request): JsonResponse
    {
        $authUser = $request->user();
        $schoolId = $this->effectiveSchoolId($authUser, $request->integer('school_id') ?: null);
        $gradeId = $request->integer('grade_id') ?: null;

        $courses = $this->visibleCourses($schoolId, $gradeId)
            ->map(fn ($course) => [
                'id' => $course->id,
                'label' => $course->label ?: $course->name,
            ])
            ->values();

        return response()->json($courses);
    }

    public function getActivities(Request $request): JsonResponse
    {
        $authUser = $request->user();
        $schoolId = $this->effectiveSchoolId($authUser, $request->integer('school_id') ?: null);
        $gradeId = $request->integer('grade_id') ?: null;
        $courseId = $request->integer('course_id') ?: null;

        $activities = $this->visibleActivities($authUser, $schoolId, $gradeId, $courseId)
            ->map(fn ($activity) => [
                'id' => $activity->id,
                'label' => $activity->title,
            ])
            ->values();

        return response()->json($activities);
    }

    public function getStudents(Request $request): JsonResponse
    {
        $authUser = $request->user();
        $schoolId = $this->effectiveSchoolId($authUser, $request->integer('school_id') ?: null);
        $gradeId = $request->integer('grade_id') ?: null;
        $courseId = $request->integer('course_id') ?: null;

        $students = $this->visibleStudents($authUser, $schoolId, $gradeId, $courseId)
            ->map(fn ($student) => [
                'id' => $student->id,
                'label' => trim($student->name . ($student->document_number ? ' - ' . $student->document_number : '')),
            ])
            ->values();

        return response()->json($students);
    }

    private function visibleGrades(?int $schoolId)
    {
        if (! $schoolId) {
            return collect();
        }

        return Grade::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->orderByRaw('CAST(name AS UNSIGNED) ASC')
            ->orderBy('name')
            ->get(['id', 'school_id', 'name', 'label']);
    }

    private function visibleCourses(?int $schoolId, ?int $gradeId)
    {
        if (! $schoolId) {
            return collect();
        }

        return Course::query()
            ->where('school_id', $schoolId)
            ->when($gradeId, fn ($query) => $query->where('grade_id', $gradeId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'school_id', 'grade_id', 'name', 'label']);
    }

    private function visibleActivities(User $authUser, ?int $schoolId, ?int $gradeId = null, ?int $courseId = null)
    {
        if (! $schoolId) {
            return collect();
        }

        return FieldDiaryActivity::query()
            ->when(! $authUser->hasRole('super_admin'), fn ($query) => $query->where('school_id', $authUser->school_id))
            ->where('school_id', $schoolId)
            ->when($gradeId, function ($query) use ($gradeId) {
                $query->where(function ($subQuery) use ($gradeId) {
                    $subQuery->whereNull('grade_id')
                        ->orWhere('grade_id', $gradeId);
                });
            })
            ->when($courseId, function ($query) use ($courseId) {
                $query->where(function ($subQuery) use ($courseId) {
                    $subQuery->whereNull('course_id')
                        ->orWhere('course_id', $courseId);
                });
            })
            ->orderByDesc('created_at')
            ->get(['id', 'school_id', 'title']);
    }

    private function visibleStudents(User $authUser, ?int $schoolId, ?int $gradeId, ?int $courseId)
    {
        if (! $schoolId) {
            return collect();
        }

        return User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'estudiante'))
            ->when(! $authUser->hasRole('super_admin'), fn ($query) => $query->where('school_id', $authUser->school_id))
            ->where('school_id', $schoolId)
            ->when($gradeId, fn ($query) => $query->where('grade_id', $gradeId))
            ->when($courseId, fn ($query) => $query->where('course_id', $courseId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'document_number']);
    }

    private function statuses(): array
    {
        return [
            'borrador' => 'Borrador',
            'enviado' => 'Enviado',
            'revisado' => 'Revisado',
            'devuelto' => 'Devuelto',
        ];
    }

    private function applySubmissionSchoolScope(Builder $query, ?int $schoolId): void
    {
        if (! $schoolId) {
            return;
        }

        $query->where('school_id', $schoolId)
            ->whereHas('activity', fn ($activityQuery) => $activityQuery->where('school_id', $schoolId));
    }

    public function export(Request $request)
    {
        $authUser = $request->user();

        $filters = $this->filters($request);
        $filters['school_id'] = $this->effectiveSchoolId($authUser, $filters['school_id']);

        $query = FieldDiarySubmission::query()
            ->with([
                'activity.school',
                'activity.weatherStation',
                'student',
                'school',
                'grade',
                'course',
                'reviewer',
                'answers.question',
            ]);

        $this->applySubmissionSchoolScope($query, $filters['school_id']);

        $query
            ->when($filters['grade_id'], fn ($query) => $query->where('grade_id', $filters['grade_id']))
            ->when($filters['course_id'], fn ($query) => $query->where('course_id', $filters['course_id']))
            ->when($filters['activity_id'], fn ($query) => $query->where('field_diary_activity_id', $filters['activity_id']))
            ->when($filters['status'], fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['student_id'], fn ($query) => $query->where('user_id', $filters['student_id']))
            ->when($filters['search'], function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->whereHas('activity', fn ($q) => $q->where('title', 'like', '%' . $search . '%'))
                        ->orWhereHas('student', fn ($q) => $q->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%')
                            ->orWhere('document_number', 'like', '%' . $search . '%'))
                        ->orWhereHas('school', fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
                        ->orWhereHas('grade', fn ($q) => $q->where('name', 'like', '%' . $search . '%')
                            ->orWhere('label', 'like', '%' . $search . '%'))
                        ->orWhereHas('course', fn ($q) => $q->where('name', 'like', '%' . $search . '%')
                            ->orWhere('label', 'like', '%' . $search . '%'));
                });
            })
            ->latest();

        $school = $authUser->hasRole('super_admin') && $filters['school_id']
            ? School::find($filters['school_id'])
            : $authUser->school;

        if ($authUser->hasRole('super_admin') && ! $filters['school_id']) {
            $school = null;
        }

        return Excel::download(
            new FieldDiarySubmissionsExport(
                $query,
                $school,
                $authUser->name,
                $this->filtersText($filters, $authUser)
            ),
            'diario_de_campo_ecodata_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    private function filtersText(array $filters, User $authUser): ?string
    {
        $parts = [];
        $isSuperAdmin = $authUser->hasRole('super_admin');
        $schoolId = $this->effectiveSchoolId($authUser, $filters['school_id']);

        if ($filters['school_id']) {
            $school = $isSuperAdmin
                ? School::find($filters['school_id'])
                : $authUser->school;

            $parts[] = 'Colegio: ' . ($school?->name ?? $filters['school_id']);
        }

        if ($filters['grade_id']) {
            $grade = Grade::query()
                ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
                ->find($filters['grade_id']);

            $parts[] = 'Grado: ' . ($grade?->label ?: $grade?->name ?: $filters['grade_id']);
        }

        if ($filters['course_id']) {
            $course = Course::query()
                ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
                ->when($filters['grade_id'], fn ($query) => $query->where('grade_id', $filters['grade_id']))
                ->find($filters['course_id']);

            $parts[] = 'Curso: ' . ($course?->label ?: $course?->name ?: $filters['course_id']);
        }

        if ($filters['activity_id']) {
            $activity = FieldDiaryActivity::query()
                ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
                ->find($filters['activity_id']);

            $parts[] = 'Actividad: ' . ($activity?->title ?? $filters['activity_id']);
        }

        if ($filters['student_id']) {
            $student = User::query()
                ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
                ->find($filters['student_id']);

            $parts[] = 'Estudiante: ' . ($student?->name ?? $filters['student_id']);
        }

        if ($filters['status']) {
            $statuses = $this->statuses();
            $parts[] = 'Estado: ' . ($statuses[$filters['status']] ?? $filters['status']);
        }

        if ($filters['search']) {
            $parts[] = 'Búsqueda: ' . $filters['search'];
        }

        return $parts ? implode(' | ', $parts) : 'Sin filtros aplicados';
    }
}
