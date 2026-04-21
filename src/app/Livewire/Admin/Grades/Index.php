<?php

namespace App\Livewire\Admin\Grades;

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

    public ?int $gradeId = null;
    public ?int $school_id = null;
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

    public function create(): void
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $grade = Grade::findOrFail($id);

        $this->gradeId = $grade->id;
        $this->school_id = $grade->school_id;
        $this->name = $grade->name;
        $this->label = $grade->label ?? '';
        $this->is_active = (bool) $grade->is_active;

        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate($this->rules());

        $payload = [
            'school_id' => $data['school_id'],
            'name' => trim($data['name']),
            'label' => $data['label'] !== '' ? trim($data['label']) : null,
            'is_active' => (bool) $data['is_active'],
        ];

        if ($this->isEditing) {
            $grade = Grade::findOrFail($this->gradeId);
            $grade->update($payload);

            session()->flash('success', 'Grado actualizado correctamente.');
        } else {
            Grade::create($payload);

            session()->flash('success', 'Grado creado correctamente.');
        }

        $this->resetForm();
        $this->showModal = false;
    }

    public function delete(int $id): void
    {
        $grade = Grade::findOrFail($id);
        $grade->delete();

        session()->flash('success', 'Grado eliminado correctamente.');
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
            'gradeId',
            'school_id',
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('grades', 'name')
                    ->where(fn ($query) => $query->where('school_id', $this->school_id))
                    ->ignore($this->gradeId),
            ],
            'label' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function render()
    {
        $grades = Grade::query()
            ->with('school')
            ->when($this->search, function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('label', 'like', '%' . $this->search . '%')
                        ->orWhereHas('school', fn ($q) => $q->where('name', 'like', '%' . $this->search . '%'));
                });
            })
            ->orderBy('school_id')
            ->orderByRaw('CAST(name AS UNSIGNED) ASC')
            ->orderBy('name')
            ->paginate($this->perPage);

        $schools = School::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.admin.grades.index', [
            'grades' => $grades,
            'schools' => $schools,
        ])->layout('components.layouts.app');
    }
}