<?php

namespace App\Livewire\Admin\PhysicalVariableRecords;

use App\Models\Course;
use App\Models\Grade;
use App\Models\PhysicalVariable;
use App\Models\PhysicalVariableCategory;
use App\Models\PhysicalVariableRecord;
use App\Models\School;
use Livewire\Component;
use Livewire\WithPagination;
use App\Exports\PhysicalVariableRecordsExport;
use Maatwebsite\Excel\Facades\Excel;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public int $perPage = 10;

    public ?int $school_id = null;
    public ?int $grade_id = null;
    public ?int $course_id = null;
    public ?int $category_id = null;
    public ?int $variable_id = null;

    public string $date_from = '';
    public string $date_to = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedSchoolId($value): void
    {
        $this->grade_id = null;
        $this->course_id = null;
        $this->variable_id = null;
        $this->resetPage();
    }

    public function updatedGradeId($value): void
    {
        $this->course_id = null;
        $this->resetPage();
    }

    public function updatedCategoryId($value): void
    {
        $this->variable_id = null;
        $this->resetPage();
    }

    public function updatedVariableId($value): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'school_id',
            'grade_id',
            'course_id',
            'category_id',
            'variable_id',
            'date_from',
            'date_to',
        ]);

        $this->perPage = 10;
        $this->resetPage();
    }

    public function render()
    {
        $records = $this->baseQuery()
        ->latest('recorded_at')
        ->paginate($this->perPage);

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

        $variables = PhysicalVariable::query()
            ->when($this->school_id, fn ($query) => $query->where('school_id', $this->school_id))
            ->when($this->category_id, fn ($query) => $query->where('category_id', $this->category_id))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.admin.physical-variable-records.index', [
            'records' => $records,
            'schools' => $schools,
            'grades' => $grades,
            'courses' => $courses,
            'categories' => $categories,
            'variables' => $variables,
        ])->layout('components.layouts.app');
    }

    protected function baseQuery()
    {
        return PhysicalVariableRecord::query()
            ->with([
                'school',
                'grade',
                'course',
                'user',
                'values.variable.category',
            ])
            ->withCount('values')
            ->when($this->school_id, fn ($query) => $query->where('school_id', $this->school_id))
            ->when($this->grade_id, fn ($query) => $query->where('grade_id', $this->grade_id))
            ->when($this->course_id, fn ($query) => $query->where('course_id', $this->course_id))
            ->when($this->date_from, fn ($query) => $query->whereDate('recorded_at', '>=', $this->date_from))
            ->when($this->date_to, fn ($query) => $query->whereDate('recorded_at', '<=', $this->date_to))
            ->when($this->category_id, function ($query) {
                $query->whereHas('values.variable', function ($subQuery) {
                    $subQuery->where('category_id', $this->category_id);
                });
            })
            ->when($this->variable_id, function ($query) {
                $query->whereHas('values', function ($subQuery) {
                    $subQuery->where('physical_variable_id', $this->variable_id);
                });
            })
            ->when($this->search, function ($query) {
                $search = $this->search;

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('observations', 'like', '%' . $search . '%')
                        ->orWhereHas('school', fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
                        ->orWhereHas('grade', fn ($q) => $q
                            ->where('name', 'like', '%' . $search . '%')
                            ->orWhere('label', 'like', '%' . $search . '%'))
                        ->orWhereHas('course', fn ($q) => $q
                            ->where('name', 'like', '%' . $search . '%')
                            ->orWhere('label', 'like', '%' . $search . '%'))
                        ->orWhereHas('user', fn ($q) => $q
                            ->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%'))
                        ->orWhereHas('values.variable', fn ($q) => $q
                            ->where('name', 'like', '%' . $search . '%')
                            ->orWhere('slug', 'like', '%' . $search . '%'));
                });
            });
    }

    public function export()
    {
        $filename = 'registros-variables-fisicas-' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(
            new PhysicalVariableRecordsExport($this->baseQuery()->latest('recorded_at')),
            $filename
        );
    }
}