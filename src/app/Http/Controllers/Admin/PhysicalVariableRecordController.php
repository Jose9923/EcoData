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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class PhysicalVariableRecordController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $query = $this->buildQuery($filters);

        $records = $query
            ->with([
                'school',
                'grade',
                'course',
                'user',
                'values.variable.category',
            ])
            ->withCount('values')
            ->paginate($filters['per_page'])
            ->appends($request->query());

        return view('admin.physical-variable-records.index', [
            'records' => $records,
            'filters' => $filters,
            'schools' => School::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'grades' => Grade::query()
                ->when($filters['school_id'], fn ($q) => $q->where('school_id', $filters['school_id']))
                ->where('is_active', true)
                ->orderByRaw('CAST(name AS UNSIGNED) ASC')
                ->orderBy('name')
                ->get(['id', 'name', 'label']),
            'courses' => Course::query()
                ->when($filters['school_id'], fn ($q) => $q->where('school_id', $filters['school_id']))
                ->when($filters['grade_id'], fn ($q) => $q->where('grade_id', $filters['grade_id']))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'label']),
            'categories' => PhysicalVariableCategory::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'variables' => PhysicalVariable::query()
                ->when($filters['school_id'], fn ($q) => $q->where('school_id', $filters['school_id']))
                ->when($filters['category_id'], fn ($q) => $q->where('category_id', $filters['category_id']))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        return view('admin.physical-variable-records.create', [
            'schools' => School::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'grades' => collect(),
            'courses' => collect(),
            'categories' => PhysicalVariableCategory::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'variables' => collect(),
            'recordedAt' => now()->format('Y-m-d\TH:i'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = $request->integer('school_id') ?: null;
        $categoryId = $request->integer('category_id') ?: null;

        $variables = PhysicalVariable::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->with('category')
            ->orderBy('category_id')
            ->orderBy('name')
            ->get();

        $rules = [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'grade_id' => [
                'nullable',
                'integer',
                Rule::exists('grades', 'id')->where(function ($query) use ($schoolId) {
                    if ($schoolId) {
                        $query->where('school_id', $schoolId);
                    }
                }),
            ],
            'course_id' => [
                'nullable',
                'integer',
                Rule::exists('courses', 'id')->where(function ($query) use ($schoolId, $request) {
                    if ($schoolId) {
                        $query->where('school_id', $schoolId);
                    }
                    if ($request->filled('grade_id')) {
                        $query->where('grade_id', $request->integer('grade_id'));
                    }
                }),
            ],
            'category_id' => ['nullable', 'integer', 'exists:physical_variable_categories,id'],
            'recorded_at' => ['required', 'date'],
            'observations' => ['nullable', 'string'],
        ];

        foreach ($variables as $variable) {
            $key = 'values.' . $variable->id;

            $rules[$key] = match ($variable->data_type) {
                'integer' => ['nullable', 'integer'],
                'decimal' => ['nullable', 'numeric'],
                'text' => ['nullable', 'string'],
                'boolean' => ['nullable'],
                'date' => ['nullable', 'date'],
                default => ['nullable'],
            };
        }

        $validator = Validator::make($request->all(), $rules);
        $validated = $validator->validate();

        $normalizedValues = $this->normalizeValuesForSave($variables, $request->input('values', []));
        $filledValues = array_filter($normalizedValues, fn ($item) => $item !== null);

        if (count($filledValues) === 0) {
            return back()
                ->withErrors(['values' => 'Debes registrar al menos una variable física.'])
                ->withInput();
        }

        $rangeError = $this->validateRanges($variables, $normalizedValues);
        if ($rangeError !== null) {
            return back()
                ->withErrors($rangeError)
                ->withInput();
        }

        DB::transaction(function () use ($validated, $filledValues) {
            $record = PhysicalVariableRecord::create([
                'school_id' => $validated['school_id'],
                'grade_id' => $validated['grade_id'] ?: null,
                'course_id' => $validated['course_id'] ?: null,
                'user_id' => auth()->id(),
                'recorded_at' => $validated['recorded_at'],
                'observations' => filled($validated['observations'] ?? null)
                    ? trim($validated['observations'])
                    : null,
            ]);

            foreach ($filledValues as $variableId => $payload) {
                $record->values()->create([
                    'physical_variable_id' => $variableId,
                    'value_numeric' => $payload['value_numeric'],
                    'value_text' => $payload['value_text'],
                    'value_boolean' => $payload['value_boolean'],
                    'value_date' => $payload['value_date'],
                ]);
            }
        });

        return redirect()
            ->route('admin.physical-variable-records.index')
            ->with('success', 'Registro físico guardado correctamente.');
    }

    public function export(Request $request)
    {
        $filters = $this->filters($request);
        $query = $this->buildQuery($filters);

        return Excel::download(
            new PhysicalVariableRecordsExport($query),
            'registros-fisicos-' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    public function getGrades(Request $request): JsonResponse
    {
        $schoolId = $request->integer('school_id');

        $grades = Grade::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->orderByRaw('CAST(name AS UNSIGNED) ASC')
            ->orderBy('name')
            ->get(['id', 'name', 'label'])
            ->map(fn ($grade) => [
                'id' => $grade->id,
                'label' => $grade->label ?: $grade->name,
            ])
            ->values();

        return response()->json($grades);
    }

    public function getCourses(Request $request): JsonResponse
    {
        $schoolId = $request->integer('school_id');
        $gradeId = $request->integer('grade_id');

        $courses = Course::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($gradeId, fn ($q) => $q->where('grade_id', $gradeId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'label'])
            ->map(fn ($course) => [
                'id' => $course->id,
                'label' => $course->label ?: $course->name,
            ])
            ->values();

        return response()->json($courses);
    }

    public function getVariables(Request $request): JsonResponse
    {
        $schoolId = $request->integer('school_id');
        $categoryId = $request->integer('category_id');

        $variables = PhysicalVariable::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->with('category:id,name')
            ->orderBy('category_id')
            ->orderBy('name')
            ->get()
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
        ];
    }

    protected function buildQuery(array $filters)
    {
        return PhysicalVariableRecord::query()
            ->when($filters['search'], function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('observations', 'like', '%' . $search . '%')
                        ->orWhereHas('school', fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
                        ->orWhereHas('grade', fn ($q) => $q->where('name', 'like', '%' . $search . '%')->orWhere('label', 'like', '%' . $search . '%'))
                        ->orWhereHas('course', fn ($q) => $q->where('name', 'like', '%' . $search . '%')->orWhere('label', 'like', '%' . $search . '%'))
                        ->orWhereHas('user', fn ($q) => $q->where('name', 'like', '%' . $search . '%')->orWhere('email', 'like', '%' . $search . '%'))
                        ->orWhereHas('values.variable', fn ($q) => $q->where('name', 'like', '%' . $search . '%'));
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
            ->latest('recorded_at');
    }

    protected function normalizeValuesForSave($variables, array $values): array
    {
        $normalized = [];

        foreach ($variables as $variable) {
            $raw = $values[$variable->id] ?? null;

            if ($this->isEmptyRawValue($raw, $variable->data_type)) {
                $normalized[$variable->id] = null;
                continue;
            }

            $normalized[$variable->id] = match ($variable->data_type) {
                'integer', 'decimal' => [
                    'value_numeric' => (float) $raw,
                    'value_text' => null,
                    'value_boolean' => null,
                    'value_date' => null,
                ],
                'text' => [
                    'value_numeric' => null,
                    'value_text' => trim((string) $raw),
                    'value_boolean' => null,
                    'value_date' => null,
                ],
                'boolean' => [
                    'value_numeric' => null,
                    'value_text' => null,
                    'value_boolean' => filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
                    'value_date' => null,
                ],
                'date' => [
                    'value_numeric' => null,
                    'value_text' => null,
                    'value_boolean' => null,
                    'value_date' => $raw,
                ],
                default => null,
            };
        }

        return $normalized;
    }

    protected function validateRanges($variables, array $normalizedValues): ?array
    {
        foreach ($variables as $variable) {
            $payload = $normalizedValues[$variable->id] ?? null;

            if ($payload === null) {
                continue;
            }

            if (! in_array($variable->data_type, ['integer', 'decimal'], true)) {
                continue;
            }

            $numeric = $payload['value_numeric'];

            if ($variable->min_value !== null && $numeric < (float) $variable->min_value) {
                return ['values.' . $variable->id => "El valor de {$variable->name} no puede ser menor que {$variable->min_value}."];
            }

            if ($variable->max_value !== null && $numeric > (float) $variable->max_value) {
                return ['values.' . $variable->id => "El valor de {$variable->name} no puede ser mayor que {$variable->max_value}."];
            }
        }

        return null;
    }

    protected function isEmptyRawValue(mixed $raw, string $dataType): bool
    {
        if ($dataType === 'boolean') {
            return $raw === null || $raw === '';
        }

        if ($dataType === 'text') {
            return trim((string) $raw) === '';
        }

        return $raw === null || $raw === '';
    }
}