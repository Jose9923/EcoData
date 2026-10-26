<?php

namespace App\Http\Controllers\Admin;

use App\Exports\PhysicalVariableRecordsExport;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Grade;
use App\Models\PhysicalVariable;
use App\Models\PhysicalVariableCategory;
use App\Models\PhysicalVariableRecord;
use App\Models\School;
use App\Models\User;
use App\Models\WeatherStation;
use App\Policies\PhysicalVariableRecordPolicy;
use App\Services\MeasurementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class PhysicalVariableRecordController extends Controller
{
    public function index(Request $request): View
    {
        $authUser = $request->user();

        $filters = $this->filters($request);
        $filters['school_id'] = $this->effectiveSchoolId($request, $filters['school_id']);

        $query = $this->buildQuery($filters, $authUser);

        $records = $query
            ->with([
                'school',
                'grade',
                'course',
                'weatherStation',
                'user',
                'values.variable.category',
                'weatherStation',
            ])
            ->withCount('values')
            ->paginate($filters['per_page'])
            ->appends($request->query());

        return view('admin.physical-variable-records.index', [
            'records' => $records,
            'filters' => $filters,
            'schools' => $this->visibleSchools($authUser),
            'grades' => $this->visibleGrades($filters['school_id']),
            'courses' => $this->visibleCourses($filters['school_id'], $filters['grade_id']),
            'categories' => $this->visibleCategories(),
            'variables' => $this->visibleVariables($filters['school_id'], $filters['category_id']),
            'weatherStations' => $this->visibleWeatherStations($authUser, $filters['school_id']),
            'sourceTypes' => $this->sourceTypes(),
        ]);
    }

    public function create(Request $request): View
    {
        $authUser = $request->user();

        $selectedSchoolId = $this->effectiveSchoolId($request, $request->integer('school_id') ?: null);
        $selectedGradeId = $authUser->hasRole('estudiante') ? $authUser->grade_id : ($request->integer('grade_id') ?: null);
        $selectedCourseId = $authUser->hasRole('estudiante') ? $authUser->course_id : ($request->integer('course_id') ?: null);
        $selectedCategoryId = $request->integer('category_id') ?: null;

        return view('admin.physical-variable-records.create', [
            'schools' => $this->visibleSchools($authUser),
            'grades' => $this->visibleGrades($selectedSchoolId),
            'courses' => $this->visibleCourses($selectedSchoolId, $selectedGradeId),
            'categories' => $this->visibleCategories(),
            'variables' => $this->visibleVariables($selectedSchoolId, $selectedCategoryId),
            'selectedSchoolId' => $selectedSchoolId,
            'selectedGradeId' => $selectedGradeId,
            'selectedCourseId' => $selectedCourseId,
            'selectedCategoryId' => $selectedCategoryId,
            'recordedAt' => now()->format('Y-m-d\TH:i'),
            'weatherStations' => $this->visibleWeatherStations($authUser, $authUser->hasRole('super_admin') ? null : $selectedSchoolId),
            'sourceTypes' => $this->sourceTypes(),
            'selectedWeatherStationId' => $request->integer('weather_station_id') ?: null,
            'selectedSourceType' => $request->input('source_type', 'manual'),
        ]);
    }

    public function store(Request $request, MeasurementService $measurements): RedirectResponse
    {
        $measurements->save($request->user(), $request->all());

        return redirect()->route('admin.physical-variable-records.index')
            ->with('success', 'Registro físico guardado correctamente.');
    }

    public function show(Request $request, int $physical_variable_record): View
    {
        $authUser = $request->user();

        $record = PhysicalVariableRecord::query()
            ->with([
                'school',
                'grade',
                'course',
                'user',
                'values.variable.category',
                'weatherStation',
            ])
            ->findOrFail($physical_variable_record);

        $this->authorizeRecordView($authUser, $record);

        $canViewHistory = (new PhysicalVariableRecordPolicy)->viewHistory($authUser, $record);
        if ($canViewHistory) {
            $record->load('audits.user');
        }

        return view('admin.physical-variable-records.show', [
            'record' => $record,
            'canViewHistory' => $canViewHistory,
            'sourceTypes' => $this->sourceTypes(),
        ]);
    }

    public function edit(Request $request, int $physical_variable_record): View
    {
        $authUser = $request->user();

        $record = PhysicalVariableRecord::query()
            ->with(['values.variable.category'])
            ->findOrFail($physical_variable_record);

        $this->authorizeRecordEdit($authUser, $record);

        $selectedSchoolId = $record->school_id;

        $selectedGradeId = old('grade_id', $request->integer('grade_id') ?: $record->grade_id);
        $selectedCourseId = old('course_id', $request->integer('course_id') ?: $record->course_id);
        $selectedCategoryId = old('category_id', $request->integer('category_id') ?: null);

        $existingVariableIds = $record->values
            ->pluck('physical_variable_id')
            ->filter()
            ->values();

        $variables = PhysicalVariable::query()
            ->where('school_id', $selectedSchoolId)
            ->where(function ($query) use ($existingVariableIds) {
                $query->where('is_active', true)->orWhereIn('id', $existingVariableIds);
            })
            ->with('category')
            ->orderBy('category_id')
            ->orderBy('name')
            ->get();

        return view('admin.physical-variable-records.edit', [
            'record' => $record,
            'schools' => $this->visibleSchools($authUser),
            'grades' => $this->visibleGrades((int) $selectedSchoolId),
            'courses' => $this->visibleCourses((int) $selectedSchoolId, $selectedGradeId ? (int) $selectedGradeId : null),
            'categories' => $this->visibleCategories(),
            'variables' => $variables,
            'selectedSchoolId' => $selectedSchoolId,
            'selectedGradeId' => $selectedGradeId,
            'selectedCourseId' => $selectedCourseId,
            'selectedCategoryId' => $selectedCategoryId,
            'recordedAt' => optional($record->recorded_at)->format('Y-m-d\TH:i'),
            'weatherStations' => $this->visibleWeatherStations($authUser, $selectedSchoolId)->when($record->weatherStation && ! $record->weatherStation->is_active, fn ($stations) => $stations->push($record->weatherStation)),
            'sourceTypes' => $this->sourceTypes(),
            'selectedWeatherStationId' => old('weather_station_id', $record->weather_station_id),
            'selectedSourceType' => old('source_type', $record->source_type),
        ]);
    }

    public function update(Request $request, int $physical_variable_record, MeasurementService $measurements): RedirectResponse
    {
        $record = PhysicalVariableRecord::findOrFail($physical_variable_record);
        $measurements->save($request->user(), $request->all(), $record);

        return redirect()->route('admin.physical-variable-records.show', $record->id)
            ->with('success', 'Registro físico actualizado correctamente.');
    }

    public function export(Request $request)
    {
        $authUser = $request->user();

        $filters = $this->filters($request);
        $filters['school_id'] = $this->effectiveSchoolId($request, $filters['school_id']);

        $query = $this->buildQuery($filters, $authUser);

        $user = auth()->user();

        $school = $user->hasRole('super_admin')
            ? null
            : $user->school;

        return Excel::download(
            new PhysicalVariableRecordsExport(
                $query,
                $school,
                $user->name,
                null,
                ! $user->hasRole('estudiante')
            ),
            'registros_variables_fisicas.xlsx'
        );
    }

    public function getGrades(Request $request): JsonResponse
    {
        $schoolId = $this->effectiveSchoolId($request, $request->integer('school_id') ?: null);

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
        $schoolId = $this->effectiveSchoolId($request, $request->integer('school_id') ?: null);
        $gradeId = $request->integer('grade_id') ?: null;

        $courses = $this->visibleCourses($schoolId, $gradeId)
            ->map(fn ($course) => [
                'id' => $course->id,
                'label' => $course->label ?: $course->name,
            ])
            ->values();

        return response()->json($courses);
    }

    public function getVariables(Request $request): JsonResponse
    {
        $schoolId = $this->effectiveSchoolId($request, $request->integer('school_id') ?: null);
        $categoryId = $request->integer('category_id') ?: null;

        $variables = $this->visibleVariables($schoolId, $categoryId, withCategory: true)
            ->map(function ($variable) {
                return [
                    'id' => $variable->id,
                    'name' => $variable->name,
                    'category' => $variable->category?->name,
                    'data_type' => $variable->data_type,
                    'unit' => $variable->unit,
                    'min_value' => $variable->min_value,
                    'max_value' => $variable->max_value,
                    'decimals' => $variable->decimals,
                    'description' => $variable->description,
                ];
            })
            ->values();

        return response()->json($variables);
    }

    protected function filters(Request $request): array
    {
        return [
            'search' => (string) $request->string('search')->toString(),
            'school_id' => $request->integer('school_id') ?: null,
            'grade_id' => $request->integer('grade_id') ?: null,
            'course_id' => $request->integer('course_id') ?: null,
            'category_id' => $request->integer('category_id') ?: null,
            'variable_id' => $request->integer('variable_id') ?: null,
            'date_from' => $request->input('date_from') ?: null,
            'date_to' => $request->input('date_to') ?: null,
            'per_page' => in_array((int) $request->integer('per_page', 10), [10, 15, 25, 50], true)
                ? (int) $request->integer('per_page', 10)
                : 10,
            'weather_station_id' => $request->integer('weather_station_id') ?: null,
            'source_type' => $request->input('source_type') ?: null,
        ];
    }

    protected function buildQuery(array $filters, User $authUser)
    {
        return PhysicalVariableRecord::query()->visibleTo($authUser)
            ->when($filters['search'], function ($query) use ($filters, $authUser) {
                $search = $filters['search'];

                $query->where(function ($subQuery) use ($search, $authUser) {
                    $subQuery->where('observations', 'like', '%'.$search.'%')
                        ->orWhereHas('school', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
                        ->orWhereHas('grade', fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('label', 'like', '%'.$search.'%'))
                        ->orWhereHas('course', fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('label', 'like', '%'.$search.'%'))
                        ->when(! $authUser->hasRole('estudiante'), fn ($q) => $q->orWhereHas('user', fn ($author) => $author->where('name', 'like', '%'.$search.'%')))
                        ->orWhereHas('values.variable', fn ($q) => $q->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->when($filters['school_id'], fn ($q) => $q->where('school_id', $filters['school_id']))
            ->when($filters['grade_id'], fn ($q) => $q->where('grade_id', $filters['grade_id']))
            ->when($filters['course_id'], fn ($q) => $q->where('course_id', $filters['course_id']))
            ->when($filters['date_from'], fn ($q) => $q->whereDate('recorded_at', '>=', $filters['date_from']))
            ->when($filters['date_to'], fn ($q) => $q->whereDate('recorded_at', '<=', $filters['date_to']))
            ->when($filters['category_id'], function ($q) use ($filters) {
                $q->whereHas('values.variable', fn ($sub) => $sub->where('category_id', $filters['category_id']));
            })
            ->when($filters['variable_id'], function ($q) use ($filters) {
                $q->whereHas('values', fn ($sub) => $sub->where('physical_variable_id', $filters['variable_id']));
            })
            ->when($filters['weather_station_id'], fn ($q) => $q->where('weather_station_id', $filters['weather_station_id']))
            ->when($filters['source_type'], fn ($q) => $q->where('source_type', $filters['source_type']))
            ->latest('recorded_at');
    }

    private function visibleSchools(User $authUser)
    {
        return School::query()
            ->where('is_active', true)
            ->when(! $authUser->hasRole('super_admin'), fn ($query) => $query->where('id', $authUser->school_id))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function visibleGrades(?int $schoolId)
    {
        return Grade::query()
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->where('is_active', true)
            ->orderByRaw('CAST(name AS UNSIGNED) ASC')
            ->orderBy('name')
            ->get(['id', 'name', 'label']);
    }

    private function visibleCourses(?int $schoolId, ?int $gradeId = null)
    {
        return Course::query()
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->when($gradeId, fn ($query) => $query->where('grade_id', $gradeId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'label']);
    }

    private function visibleCategories()
    {
        return PhysicalVariableCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function visibleVariables(?int $schoolId, ?int $categoryId = null, bool $withCategory = false)
    {
        return PhysicalVariable::query()
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->where('is_active', true)
            ->when($withCategory, fn ($query) => $query->with('category:id,name'))
            ->orderBy('category_id')
            ->orderBy('name')
            ->get();
    }

    private function effectiveSchoolId(Request $request, ?int $requestedSchoolId): ?int
    {
        $authUser = $request->user();

        if (! $authUser->hasRole('super_admin')) {
            abort_if(! $authUser->school_id, 403, 'Tu usuario no tiene un colegio asignado.');

            return (int) $authUser->school_id;
        }

        return $requestedSchoolId;
    }

    private function visibleWeatherStations(User $authUser, ?int $schoolId = null)
    {
        return WeatherStation::query()
            ->when(! $authUser->hasRole('super_admin'), fn ($query) => $query->where('school_id', $authUser->school_id))
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'school_id', 'name', 'code']);
    }

    private function sourceTypes(): array
    {
        return [
            'manual' => 'Manual',
            'station' => 'Estación meteorológica',
            'csv' => 'Cargue CSV',
        ];
    }

    private function authorizeRecordView(User $authUser, PhysicalVariableRecord $record): void
    {
        abort_unless((new PhysicalVariableRecordPolicy)->view($authUser, $record), 403);
    }

    private function authorizeRecordEdit(User $authUser, PhysicalVariableRecord $record): void
    {
        abort_unless((new PhysicalVariableRecordPolicy)->update($authUser, $record), 403);
    }
}
