<?php

namespace App\Livewire\Admin\Schools;

use App\Models\School;
use App\Repositories\Contracts\SchoolRepositoryInterface;
use App\Services\SchoolService;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination, WithFileUploads;

    public string $search = '';
    public int $perPage = 10;

    public ?int $schoolId = null;
    public string $name = '';
    public string $slug = '';
    public string $primary_color = '#1d4ed8';
    public string $secondary_color = '#0f172a';
    public string $accent_color = '#22c55e';
    public bool $is_active = true;
    public $shield = null;

    public bool $showModal = false;
    public bool $isEditing = false;

    public function updatedSearch(): void
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
        $school = $this->schoolRepository()->findById($id);

        abort_if(! $school, 404);

        $this->schoolId = $school->id;
        $this->name = $school->name;
        $this->slug = $school->slug;
        $this->primary_color = $school->primary_color;
        $this->secondary_color = $school->secondary_color;
        $this->accent_color = $school->accent_color;
        $this->is_active = $school->is_active;
        $this->shield = null;

        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate($this->rules());

        if ($this->isEditing) {
            $school = $this->schoolRepository()->findById($this->schoolId);

            abort_if(! $school, 404);

            $this->schoolService()->updateSchool($school, $data, $this->shield);
            session()->flash('success', 'Colegio actualizado correctamente.');
        } else {
            $this->schoolService()->createSchool($data, $this->shield);
            session()->flash('success', 'Colegio creado correctamente.');
        }

        $this->resetForm();
        $this->showModal = false;
    }

    public function delete(int $id): void
    {
        $school = $this->schoolRepository()->findById($id);

        abort_if(! $school, 404);

        $this->schoolService()->deleteSchool($school);

        session()->flash('success', 'Colegio eliminado correctamente.');
    }

    public function closeModal(): void
    {
        $this->resetValidation();
        $this->showModal = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->resetValidation();

        $this->reset([
            'schoolId',
            'name',
            'slug',
            'shield',
        ]);

        $this->primary_color = '#1d4ed8';
        $this->secondary_color = '#0f172a';
        $this->accent_color = '#22c55e';
        $this->is_active = true;
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('schools', 'slug')->ignore($this->schoolId),
            ],
            'primary_color' => ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'secondary_color' => ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'accent_color' => ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'is_active' => ['required', 'boolean'],
            'shield' => ['nullable', 'image', 'max:2048'],
        ];
    }

    protected function schoolRepository(): SchoolRepositoryInterface
    {
        return app(SchoolRepositoryInterface::class);
    }

    protected function schoolService(): SchoolService
    {
        return app(SchoolService::class);
    }

    public function render()
    {
        return view('livewire.admin.schools.index', [
            'schools' => $this->schoolRepository()->paginateWithFilters($this->search, $this->perPage),
        ]);
    }
}