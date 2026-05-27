<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFieldDiaryActivityRequest;
use App\Http\Requests\Admin\UpdateFieldDiaryActivityRequest;
use App\Models\Course;
use App\Models\FieldDiaryActivity;
use App\Models\FieldDiaryQuestion;
use App\Models\Grade;
use App\Models\School;
use App\Models\User;
use App\Models\WeatherStation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Notifications\NewFieldDiaryActivityNotification;
use Illuminate\Support\Facades\Notification;

class FieldDiaryActivityController extends Controller
{
    public function index(Request $request): View
    {
        $authUser = $request->user();

        $search = (string) $request->string('search')->toString();
        $perPage = (int) $request->integer('per_page', 10);

        if (! in_array($perPage, [10, 15, 25, 50], true)) {
            $perPage = 10;
        }

        $activities = FieldDiaryActivity::query()
            ->with(['school', 'grade', 'course', 'weatherStation', 'creator'])
            ->withCount(['questions', 'submissions'])
            ->when(! $authUser->hasRole('super_admin'), function ($query) use ($authUser) {
                $query->where('school_id', $authUser->school_id);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('title', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%')
                        ->orWhere('entry_type', 'like', '%' . $search . '%')
                        ->orWhereHas('school', fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
                        ->orWhereHas('grade', fn ($q) => $q->where('name', 'like', '%' . $search . '%')->orWhere('label', 'like', '%' . $search . '%'))
                        ->orWhereHas('course', fn ($q) => $q->where('name', 'like', '%' . $search . '%')->orWhere('label', 'like', '%' . $search . '%'))
                        ->orWhereHas('weatherStation', fn ($q) => $q->where('name', 'like', '%' . $search . '%')->orWhere('code', 'like', '%' . $search . '%'))
                        ->orWhereHas('creator', fn ($q) => $q->where('name', 'like', '%' . $search . '%'));
                });
            })
            ->latest()
            ->paginate($perPage)
            ->appends($request->query());

        return view('admin.field-diary-activities.index', compact('activities', 'search', 'perPage'));
    }

    public function create(Request $request): View
    {
        $authUser = $request->user();

        $selectedSchoolId = $this->resolveSelectedSchoolId($request);
        $selectedGradeId = $request->integer('grade_id') ?: null;
        $selectedCourseId = $request->integer('course_id') ?: null;

        return view('admin.field-diary-activities.create', [
            'activity' => null,
            'schools' => $this->visibleSchools($authUser),
            'grades' => $this->visibleGrades($selectedSchoolId),
            'courses' => $this->visibleCourses($selectedSchoolId, $selectedGradeId),
            'weatherStations' => $this->visibleWeatherStations($authUser, $selectedSchoolId),
            'entryTypes' => $this->entryTypes(),
            'questionTypes' => $this->questionTypes(),
            'selectedSchoolId' => $selectedSchoolId,
            'selectedGradeId' => $selectedGradeId,
            'selectedCourseId' => $selectedCourseId,
        ]);
    }

    public function store(StoreFieldDiaryActivityRequest $request): RedirectResponse
    {
        $authUser = $request->user();
        $data = $request->validated();

        $schoolId = $authUser->hasRole('super_admin')
            ? (int) $data['school_id']
            : (int) $authUser->school_id;

        $activity = DB::transaction(function () use ($data, $schoolId, $authUser) {
            $activity = FieldDiaryActivity::create([
                'school_id' => $schoolId,
                'grade_id' => $data['grade_id'] ?? null,
                'course_id' => $data['course_id'] ?? null,
                'weather_station_id' => $data['weather_station_id'] ?? null,
                'created_by' => $authUser->id,
                'title' => trim($data['title']),
                'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
                'entry_type' => $data['entry_type'],
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'is_active' => (bool) $data['is_active'],
            ]);

            $this->syncQuestions($activity, $data['questions'] ?? []);

            return $activity;
        });

        if ($activity->is_active) {
            $activity->load(['grade', 'course', 'weatherStation']);

            $students = User::role('estudiante')
                ->where('school_id', $activity->school_id)
                ->where('is_active', true)
                ->when($activity->grade_id, fn ($query) => $query->where('grade_id', $activity->grade_id))
                ->when($activity->course_id, fn ($query) => $query->where('course_id', $activity->course_id))
                ->get();

            if ($students->isNotEmpty()) {
                Notification::send($students, new NewFieldDiaryActivityNotification($activity));
            }
        }

        return redirect()
            ->route('admin.field-diary-activities.index')
            ->with('success', 'Actividad de diario de campo creada correctamente.');
    }

    public function show(Request $request, FieldDiaryActivity $field_diary_activity): View
    {
        $authUser = $request->user();

        $this->authorizeSchoolScope($authUser, $field_diary_activity->school_id);

        $field_diary_activity->load([
            'school',
            'grade',
            'course',
            'weatherStation',
            'creator',
            'questions',
        ]);

        $field_diary_activity->loadCount(['questions', 'submissions']);

        return view('admin.field-diary-activities.show', [
            'activity' => $field_diary_activity,
        ]);
    }

    public function edit(Request $request, FieldDiaryActivity $field_diary_activity): View
    {
        $authUser = $request->user();

        $this->authorizeSchoolScope($authUser, $field_diary_activity->school_id);

        $field_diary_activity->load('questions');

        $selectedSchoolId = $authUser->hasRole('super_admin')
            ? old('school_id', $request->integer('school_id') ?: $field_diary_activity->school_id)
            : $authUser->school_id;

        $selectedGradeId = old('grade_id', $request->integer('grade_id') ?: $field_diary_activity->grade_id);
        $selectedCourseId = old('course_id', $request->integer('course_id') ?: $field_diary_activity->course_id);

        return view('admin.field-diary-activities.edit', [
            'activity' => $field_diary_activity,
            'schools' => $this->visibleSchools($authUser),
            'grades' => $this->visibleGrades((int) $selectedSchoolId),
            'courses' => $this->visibleCourses((int) $selectedSchoolId, $selectedGradeId ? (int) $selectedGradeId : null),
            'weatherStations' => $this->visibleWeatherStations($authUser, (int) $selectedSchoolId),
            'entryTypes' => $this->entryTypes(),
            'questionTypes' => $this->questionTypes(),
            'selectedSchoolId' => $selectedSchoolId,
            'selectedGradeId' => $selectedGradeId,
            'selectedCourseId' => $selectedCourseId,
        ]);
    }

    public function update(UpdateFieldDiaryActivityRequest $request, FieldDiaryActivity $field_diary_activity): RedirectResponse
    {
        $authUser = $request->user();

        $this->authorizeSchoolScope($authUser, $field_diary_activity->school_id);

        $data = $request->validated();

        $schoolId = $authUser->hasRole('super_admin')
            ? (int) $data['school_id']
            : (int) $authUser->school_id;

        DB::transaction(function () use ($field_diary_activity, $data, $schoolId) {
            $field_diary_activity->update([
                'school_id' => $schoolId,
                'grade_id' => $data['grade_id'] ?? null,
                'course_id' => $data['course_id'] ?? null,
                'weather_station_id' => $data['weather_station_id'] ?? null,
                'title' => trim($data['title']),
                'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
                'entry_type' => $data['entry_type'],
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'is_active' => (bool) $data['is_active'],
            ]);

            $field_diary_activity->questions()->delete();

            $this->syncQuestions($field_diary_activity, $data['questions'] ?? []);
        });

        return redirect()
            ->route('admin.field-diary-activities.index')
            ->with('success', 'Actividad de diario de campo actualizada correctamente.');
    }

    public function destroy(Request $request, FieldDiaryActivity $field_diary_activity): RedirectResponse
    {
        $authUser = $request->user();

        $this->authorizeSchoolScope($authUser, $field_diary_activity->school_id);

        $field_diary_activity->delete();

        return redirect()
            ->route('admin.field-diary-activities.index')
            ->with('success', 'Actividad de diario de campo eliminada correctamente.');
    }

    private function syncQuestions(FieldDiaryActivity $activity, array $questions): void
    {
        foreach ($questions as $index => $question) {
            $options = null;

            if (in_array($question['question_type'], ['select', 'radio', 'checkbox'], true)) {
                $options = collect(explode(PHP_EOL, (string) ($question['options_text'] ?? '')))
                    ->map(fn ($option) => trim($option))
                    ->filter()
                    ->values()
                    ->all();
            }

            $activity->questions()->create([
                'question_text' => trim($question['question_text']),
                'question_type' => $question['question_type'],
                'options' => $options,
                'order' => filled($question['order'] ?? null) ? (int) $question['order'] : $index + 1,
                'is_required' => (bool) ($question['is_required'] ?? false),
            ]);
        }
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
        $schoolId = $this->resolveSelectedSchoolId($request);

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
        $schoolId = $this->resolveSelectedSchoolId($request);
        $gradeId = $request->integer('grade_id') ?: null;

        $courses = $this->visibleCourses($schoolId, $gradeId)
            ->map(fn ($course) => [
                'id' => $course->id,
                'label' => $course->label ?: $course->name,
            ])
            ->values();

        return response()->json($courses);
    }

    public function getWeatherStations(Request $request): JsonResponse
    {
        $authUser = $request->user();
        $schoolId = $this->resolveSelectedSchoolId($request);

        $stations = $this->visibleWeatherStations($authUser, $schoolId)
            ->map(fn ($station) => [
                'id' => $station->id,
                'label' => trim($station->name . ' - ' . $station->code),
            ])
            ->values();

        return response()->json($stations);
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
            ->get(['id', 'name', 'label']);
    }

    private function visibleCourses(?int $schoolId, ?int $gradeId = null)
    {
        if (! $schoolId) {
            return collect();
        }

        return Course::query()
            ->where('school_id', $schoolId)
            ->when($gradeId, fn ($query) => $query->where('grade_id', $gradeId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'label']);
    }

    private function visibleWeatherStations(User $authUser, ?int $schoolId = null)
    {
        if (! $schoolId) {
            return collect();
        }

        return WeatherStation::query()
            ->with('school:id,name')
            ->where('is_active', true)
            ->when(! $authUser->hasRole('super_admin'), fn ($query) => $query->where('school_id', $authUser->school_id))
            ->where('school_id', $schoolId)
            ->orderBy('school_id')
            ->orderBy('name')
            ->get(['id', 'school_id', 'name', 'code']);
    }

    private function resolveSelectedSchoolId(Request $request): ?int
    {
        $authUser = $request->user();

        if (! $authUser->hasRole('super_admin')) {
            abort_if(! $authUser->school_id, 403, 'Tu usuario no tiene un colegio asignado.');

            return (int) $authUser->school_id;
        }

        return $request->integer('school_id') ?: null;
    }

    private function authorizeSchoolScope(User $authUser, ?int $schoolId): void
    {
        if ($authUser->hasRole('super_admin')) {
            return;
        }

        abort_if(! $authUser->school_id, 403, 'Tu usuario no tiene un colegio asignado.');

        abort_if(
            (int) $schoolId !== (int) $authUser->school_id,
            403,
            'No tienes autorización para gestionar actividades de diario de campo de otro colegio.'
        );
    }

    private function entryTypes(): array
    {
        return [
            'observacion' => 'Observación',
            'reto' => 'Reto',
            'portafolio' => 'Portafolio',
        ];
    }

    private function questionTypes(): array
    {
        return [
            'text' => 'Respuesta corta',
            'textarea' => 'Respuesta larga',
            'number' => 'Número',
            'date' => 'Fecha',
            'select' => 'Lista desplegable',
            'radio' => 'Selección única',
            'checkbox' => 'Selección múltiple',
            'file' => 'Archivo / evidencia',
        ];
    }
}
