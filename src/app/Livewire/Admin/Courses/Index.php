<?php

namespace App\Livewire\Admin\Courses;

use App\Models\Course;
use App\Models\Grade;
use App\Models\School;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public int $perPage = 10;

    public ?int $courseId = null;
    public ?int $school_id = null;
    public ?int $grade_id = null;
    public string $name = '';
    public string $label = '';
    public bool $is_active = true;

    public bool $showModal = false;
    public bool $isEditing = false;

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
    }

    public function create(): void
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $course = Course::findOrFail($id);

        $this->courseId = $course->id;
        $this->school_id = $course->school_id;
        $this->grade_id = $course->grade_id;
        $this->name = $course->name;
        $this->label = $course->label ?? '';
        $this->is_active = (bool) $course->is_active;

        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate($this->rules());

        $payload = [
            'school_id' => $data['school_id'],
            'grade_id' => $data['grade_id'],
            'name' => trim($data['name']),
            'label' => $data['label'] !== '' ? trim($data['label']) : null,
            'is_active' => (bool) $data['is_active'],
        ];

        if ($this->isEditing) {
            $course = Course::findOrFail($this->courseId);
            $course->update($payload);

            session()->flash('success', 'Curso actualizado correctamente.');
        } else {
            Course::create($payload);

            session()->flash('success', 'Curso creado correctamente.');
        }

        $this->resetForm();
        $this->showModal = false;
    }

    public function delete(int $id): void
    {
        $course = Course::findOrFail($id);
        $course->delete();

        session()->flash('success', 'Curso eliminado correctamente.');
    }

    public function closeModal(): void
    {
        $this->resetForm();
        $this->showModal = false;
    }

    protected function resetForm(): void
    {
        $this->resetValidation();

        $this->reset([
            'courseId',
            'school_id',
            'grade_id',
            'name',
            'label',
        ]);

        $this->is_active = true;
        $this->isEditing = false;
    }

    protected function rules(): array
    {
        return [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'grade_id' => [
                'required',
                'integer',
                Rule::exists('grades', 'id')->where(function ($query) {
                    if ($this->school_id) {
                        $query->where('school_id', $this->school_id);
                    }
                }),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('courses', 'name')
                    ->where(fn ($query) => $query
                        ->where('school_id', $this->school_id)
                        ->where('grade_id', $this->grade_id))
                    ->ignore($this->courseId),
            ],
            'label' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function render()
    {
        $courses = Course::query()
            ->with(['school', 'grade'])
            ->when($this->search, function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('label', 'like', '%' . $this->search . '%')
                        ->orWhereHas('school', fn ($q) => $q->where('name', 'like', '%' . $this->search . '%'))
                        ->orWhereHas('grade', fn ($q) => $q
                            ->where('name', 'like', '%' . $this->search . '%')
                            ->orWhere('label', 'like', '%' . $this->search . '%'));
                });
            })
            ->orderBy('school_id')
            ->orderBy('grade_id')
            ->orderBy('name')
            ->paginate($this->perPage);

        $schools = School::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $grades = Grade::query()
            ->when($this->school_id, fn ($query) => $query->where('school_id', $this->school_id))
            ->where('is_active', true)
            ->orderByRaw('CAST(name AS UNSIGNED) ASC')
            ->orderBy('name')
            ->get(['id', 'school_id', 'name', 'label']);

        return view('livewire.admin.courses.index', [
            'courses' => $courses,
            'schools' => $schools,
            'grades' => $grades,
        ])->layout('components.layouts.app');
    }
}