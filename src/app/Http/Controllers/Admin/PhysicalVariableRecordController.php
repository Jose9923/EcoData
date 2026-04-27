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
        $authUser = $request->user();

        $filters = $this->filters($request);
        $filters['school_id'] = $this->effectiveSchoolId($request, $filters['school_id']);

        $query = $this->buildQuery($filters, $authUser);

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
            'schools' => $this->visibleSchools($authUser),
            'grades' => $this->visibleGrades($filters['school_id']),
            'courses' => $this->visibleCourses($filters['school_id'], $filters['grade_id']),
            'categories' => $this->visibleCategories(),
            'variables' => $this->visibleVariables($filters['school_id'], $filters['category_id']),
        ]);
    }

    public function create(Request $request): View
    {
        $authUser = $request->user();

        $selectedSchoolId = $this->effectiveSchoolId($request, $request->integer('school_id') ?: null);
        $selectedGradeId = $request->integer('grade_id') ?: null;
        $selectedCourseId = $request->integer('course_id') ?: null;
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
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $authUser = $request->user();

        $schoolId = $this->effectiveSchoolId($request, $request->integer('school_id') ?: null);
        $categoryId = $request->integer('category_id') ?: null;

        $request->merge([
            'school_id' => $schoolId,
        ]);

        $variables = $this->visibleVariables($schoolId, $categoryId, withCategory: true);

        $validator = $this->makeDynamicValidator($request, $variables, $authUser);
        $validated = $validator->validate();

        $normalizedValues = $this->normalizeValuesForSave($variables, $request->input('values', []));
        $filledValues = array_filter($normalizedValues, fn ($item) => $item !== null);

        if (count($filledValues) === 0) {
            return back()
                ->withErrors(['values' => 'Debes registrar al menos una variable física.'])
                ->withInput();
        }

        $precisionError = $this->validatePrecision($variables, $normalizedValues);

        if ($precisionError !== null) {
            return back()
                ->withErrors($precisionError)
                ->withInput();
        }

        $rangeError = $this->validateRanges($variables, $normalizedValues);

        if ($rangeError !== null) {
            return back()
                ->withErrors($rangeError)
                ->withInput();
        }

        DB::transaction(function () use ($validated, $filledValues, $authUser) {
            $record = PhysicalVariableRecord::create([
                'school_id' => $validated['school_id'],
                'grade_id' => $validated['grade_id'] ?: null,
                'course_id' => $validated['course_id'] ?: null,
                'user_id' => $authUser->id,
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
            ])
            ->findOrFail($physical_variable_record);

        $this->authorizeSchoolScope($authUser, $record->school_id);

        return view('admin.physical-variable-records.show', compact('record'));
    }

    public function edit(Request $request, int $physical_variable_record): View
    {
        $authUser = $request->user();

        $record = PhysicalVariableRecord::query()
            ->with(['values.variable.category'])
            ->findOrFail($physical_variable_record);

        $this->authorizeSchoolScope($authUser, $record->school_id);

        $selectedSchoolId = $authUser->hasRole('super_admin')
            ? old('school_id', $request->integer('school_id') ?: $record->school_id)
            : $authUser->school_id;

        $selectedGradeId = old('grade_id', $request->integer('grade_id') ?: $record->grade_id);
        $selectedCourseId = old('course_id', $request->integer('course_id') ?: $record->course_id);
        $selectedCategoryId = old('category_id', $request->integer('category_id') ?: null);

        $existingVariableIds = $record->values
            ->pluck('physical_variable_id')
            ->filter()
            ->values();

        $variables = PhysicalVariable::query()
            ->where('school_id', $selectedSchoolId)
            ->where('is_active', true)
            ->whereIn('id', $existingVariableIds)
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
        ]);
    }

    public function update(Request $request, int $physical_variable_record): RedirectResponse
    {
        $authUser = $request->user();

        $record = PhysicalVariableRecord::query()
            ->with('values')
            ->findOrFail($physical_variable_record);

        $this->authorizeSchoolScope($authUser, $record->school_id);

        $schoolId = $this->effectiveSchoolId($request, $request->integer('school_id') ?: null);
        $categoryId = $request->integer('category_id') ?: null;

        $request->merge([
            'school_id' => $schoolId,
        ]);

        $variables = $this->visibleVariables($schoolId, $categoryId, withCategory: true);

        $validator = $this->makeDynamicValidator($request, $variables, $authUser);
        $validated = $validator->validate();

        $normalizedValues = $this->normalizeValuesForSave($variables, $request->input('values', []));
        $filledValues = array_filter($normalizedValues, fn ($item) => $item !== null);

        if (count($filledValues) === 0) {
            return back()
                ->withErrors(['values' => 'Debes registrar al menos una variable física.'])
                ->withInput();
        }

        $precisionError = $this->validatePrecision($variables, $normalizedValues);

        if ($precisionError !== null) {
            return back()
                ->withErrors($precisionError)
                ->withInput();
        }

        $rangeError = $this->validateRanges($variables, $normalizedValues);

        if ($rangeError !== null) {
            return back()
                ->withErrors($rangeError)
                ->withInput();
        }

        DB::transaction(function () use ($record, $validated, $filledValues) {
            $record->update([
                'school_id' => $validated['school_id'],
                'grade_id' => $validated['grade_id'] ?: null,
                'course_id' => $validated['course_id'] ?: null,
                'recorded_at' => $validated['recorded_at'],
                'observations' => filled($validated['observations'] ?? null)
                    ? trim($validated['observations'])
                    : null,
            ]);

            $record->values()->delete();

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
            ->route('admin.physical-variable-records.show', $record->id)
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
                $filtersText ?? null
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
        ];
    }

    protected function buildQuery(array $filters, User $authUser)
    {
        return PhysicalVariableRecord::query()
            ->when(! $authUser->hasRole('super_admin'), function ($query) use ($authUser) {
                $query->where('school_id', $authUser->school_id);
            })
            ->when($filters['search'], function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('observations', 'like', '%' . $search . '%')
                        ->orWhereHas('school', fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
                        ->orWhereHas('grade', fn ($q) => $q->where('name', 'like', '%' . $search . '%')->orWhere('label', 'like', '%' . $search . '%'))
                        ->orWhereHas('course', fn ($q) => $q->where('name', 'like', '%' . $search . '%')->orWhere('label', 'like', '%' . $search . '%'))
                        ->orWhereHas('user', function ($q) use ($search) {
                            $q->where('name', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%')
                                ->orWhere('document_type', 'like', '%' . $search . '%')
                                ->orWhere('document_number', 'like', '%' . $search . '%');
                        })
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

    protected function makeDynamicValidator(Request $request, $variables, User $authUser)
    {
        $schoolId = $this->effectiveSchoolId($request, $request->integer('school_id') ?: null);

        $rules = [
            'school_id' => [
                'required',
                'integer',
                $authUser->hasRole('super_admin')
                    ? Rule::exists('schools', 'id')->where(fn ($query) => $query->where('is_active', true))
                    : Rule::in([(int) $authUser->school_id]),
            ],
            'grade_id' => [
                'nullable',
                'integer',
                Rule::exists('grades', 'id')->where(function ($query) use ($schoolId) {
                    $query->where('school_id', $schoolId)
                        ->where('is_active', true);
                }),
            ],
            'course_id' => [
                'nullable',
                'integer',
                Rule::exists('courses', 'id')->where(function ($query) use ($schoolId, $request) {
                    $query->where('school_id', $schoolId)
                        ->where('is_active', true);

                    if ($request->filled('grade_id')) {
                        $query->where('grade_id', $request->integer('grade_id'));
                    }
                }),
            ],
            'category_id' => ['nullable', 'integer', Rule::exists('physical_variable_categories', 'id')->where(fn ($query) => $query->where('is_active', true))],
            'recorded_at' => ['required', 'date'],
            'observations' => ['nullable', 'string'],
        ];

        $attributes = [
            'school_id' => 'colegio',
            'grade_id' => 'grado',
            'course_id' => 'curso',
            'category_id' => 'categoría',
            'recorded_at' => 'fecha y hora',
            'observations' => 'observaciones',
        ];

        foreach ($variables as $variable) {
            $key = 'values.' . $variable->id;

            $rules[$key] = match ($variable->data_type) {
                'integer' => ['nullable', 'integer'],
                'decimal' => ['nullable', 'numeric'],
                'text' => ['nullable', 'string'],
                'boolean' => ['nullable', 'boolean'],
                'date' => ['nullable', 'date'],
                default => ['nullable'],
            };

            $attributes[$key] = $variable->name;
        }

        $messages = [
            'school_id.required' => 'Debes seleccionar un colegio.',
            'school_id.exists' => 'El colegio seleccionado no existe o está inactivo.',
            'school_id.in' => 'No puedes gestionar registros físicos de un colegio diferente al tuyo.',
            'grade_id.exists' => 'El grado seleccionado no pertenece al colegio indicado o está inactivo.',
            'course_id.exists' => 'El curso seleccionado no pertenece al colegio o grado indicado, o está inactivo.',
            'category_id.exists' => 'La categoría seleccionada no existe o está inactiva.',
            'recorded_at.required' => 'Debes indicar la fecha y hora del registro.',
            'recorded_at.date' => 'La fecha y hora del registro no es válida.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        $validator->setAttributeNames($attributes);

        return $validator;
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
                    'value_numeric' => (float) (is_string($raw) ? str_replace(',', '.', $raw) : $raw),
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

    protected function validatePrecision($variables, array $normalizedValues): ?array
    {
        foreach ($variables as $variable) {
            $payload = $normalizedValues[$variable->id] ?? null;

            if ($payload === null) {
                continue;
            }

            if ($variable->data_type !== 'decimal') {
                continue;
            }

            $value = $payload['value_numeric'];

            if ($value === null) {
                continue;
            }

            $allowedDecimals = (int) ($variable->decimals ?? 0);

            $stringValue = rtrim(rtrim((string) $value, '0'), '.');

            if (str_contains($stringValue, '.')) {
                $actualDecimals = strlen(substr(strrchr($stringValue, '.'), 1));

                if ($actualDecimals > $allowedDecimals) {
                    return [
                        'values.' . $variable->id => "El valor de {$variable->name} solo permite {$allowedDecimals} decimales.",
                    ];
                }
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

    private function authorizeSchoolScope(User $authUser, ?int $schoolId): void
    {
        if ($authUser->hasRole('super_admin')) {
            return;
        }

        abort_if(! $authUser->school_id, 403, 'Tu usuario no tiene un colegio asignado.');

        abort_if(
            (int) $schoolId !== (int) $authUser->school_id,
            403,
            'No tienes autorización para gestionar registros físicos de otro colegio.'
        );
    }
}