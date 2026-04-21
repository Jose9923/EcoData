<?php

namespace App\Livewire\Admin\PhysicalVariableRecords;

use App\Models\Course;
use App\Models\Grade;
use App\Models\PhysicalVariable;
use App\Models\PhysicalVariableCategory;
use App\Models\PhysicalVariableRecord;
use App\Models\School;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Create extends Component
{
    public ?int $school_id = null;
    public ?int $grade_id = null;
    public ?int $course_id = null;
    public ?int $category_id = null;

    public string $recorded_at = '';
    public string $observations = '';

    /**
     * values[physical_variable_id] = raw input value
     */
    public array $values = [];

    public function mount(): void
    {
        $this->recorded_at = now()->format('Y-m-d\TH:i');
    }

    public function updatedSchoolId($value): void
    {
        $this->grade_id = null;
        $this->course_id = null;
        $this->resetValues();
    }

    public function updatedGradeId($value): void
    {
        $this->course_id = null;
    }

    public function updatedCategoryId($value): void
    {
        $this->resetValues();
    }

    public function save(): void
    {
        $variables = $this->activeVariables();
        $validated = $this->validate($this->rules($variables));

        $normalizedValues = $this->normalizeValuesForSave($variables);
        $filledValues = array_filter($normalizedValues, fn ($item) => $item !== null);

        if (count($filledValues) === 0) {
            $this->addError('values', 'Debes registrar al menos una variable física.');
            return;
        }

        $rangeError = $this->validateRanges($variables, $normalizedValues);
        if ($rangeError !== null) {
            return;
        }

        DB::transaction(function () use ($validated, $filledValues) {
            $record = PhysicalVariableRecord::create([
                'school_id' => $validated['school_id'],
                'grade_id' => $validated['grade_id'] ?: null,
                'course_id' => $validated['course_id'] ?: null,
                'user_id' => auth()->id(),
                'recorded_at' => $validated['recorded_at'],
                'observations' => trim($validated['observations'] ?? '') !== ''
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

        session()->flash('success', 'Registro físico guardado correctamente.');

        $this->grade_id = null;
        $this->course_id = null;
        $this->category_id = null;
        $this->observations = '';
        $this->recorded_at = now()->format('Y-m-d\TH:i');
        $this->resetValues();
        $this->resetValidation();
    }

    protected function rules($variables): array
    {
        $rules = [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'grade_id' => [
                'nullable',
                'integer',
                Rule::exists('grades', 'id')->where(function ($query) {
                    if ($this->school_id) {
                        $query->where('school_id', $this->school_id);
                    }
                }),
            ],
            'course_id' => [
                'nullable',
                'integer',
                Rule::exists('courses', 'id')->where(function ($query) {
                    if ($this->school_id) {
                        $query->where('school_id', $this->school_id);
                    }
                    if ($this->grade_id) {
                        $query->where('grade_id', $this->grade_id);
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

        return $rules;
    }

    protected function activeVariables()
    {
        return PhysicalVariable::query()
            ->where('school_id', $this->school_id)
            ->where('is_active', true)
            ->when($this->category_id, fn ($query) => $query->where('category_id', $this->category_id))
            ->with('category')
            ->orderBy('category_id')
            ->orderBy('name')
            ->get();
    }

    protected function normalizeValuesForSave($variables): array
    {
        $normalized = [];

        foreach ($variables as $variable) {
            $raw = $this->values[$variable->id] ?? null;

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

    protected function validateRanges($variables, array $normalizedValues): ?bool
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
                $this->addError(
                    'values.' . $variable->id,
                    "El valor de {$variable->name} no puede ser menor que {$variable->min_value}."
                );
                return false;
            }

            if ($variable->max_value !== null && $numeric > (float) $variable->max_value) {
                $this->addError(
                    'values.' . $variable->id,
                    "El valor de {$variable->name} no puede ser mayor que {$variable->max_value}."
                );
                return false;
            }
        }

        return true;
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

    protected function resetValues(): void
    {
        $this->values = [];
    }

    public function render()
    {
        $schools = School::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $grades = Grade::query()
            ->when($this->school_id, fn ($query) => $query->where('school_id', $this->school_id))
            ->where('is_active', true)
            ->orderByRaw('CAST(name AS UNSIGNED) ASC')
            ->orderBy('name')
            ->get(['id', 'name', 'label']);

        $courses = Course::query()
            ->when($this->school_id, fn ($query) => $query->where('school_id', $this->school_id))
            ->when($this->grade_id, fn ($query) => $query->where('grade_id', $this->grade_id))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'label']);

        $categories = PhysicalVariableCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $variables = $this->school_id
            ? $this->activeVariables()
            : collect();

        return view('livewire.admin.physical-variable-records.create', [
            'schools' => $schools,
            'grades' => $grades,
            'courses' => $courses,
            'categories' => $categories,
            'variables' => $variables,
        ])->layout('components.layouts.app');
    }
}