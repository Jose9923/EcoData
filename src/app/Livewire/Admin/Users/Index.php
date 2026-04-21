<?php

namespace App\Livewire\Admin\Users;

use App\Models\Course;
use App\Models\Grade;
use App\Models\School;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public int $perPage = 10;

    public ?int $userId = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public ?int $school_id = null;
    public ?int $grade_id = null;
    public ?int $course_id = null;
    public string $role = '';
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
        $this->course_id = null;
    }

    public function updatedGradeId($value): void
    {
        $this->course_id = null;
    }

    public function create(): void
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $user = User::with('roles')->findOrFail($id);

        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->password_confirmation = '';
        $this->school_id = $user->school_id;
        $this->grade_id = $user->grade_id;
        $this->course_id = $user->course_id;
        $this->role = $user->roles->first()?->name ?? '';
        $this->is_active = (bool) $user->is_active;

        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate($this->rules());

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'school_id' => $data['school_id'] ?: null,
            'grade_id' => $data['grade_id'] ?: null,
            'course_id' => $data['course_id'] ?: null,
            'is_active' => (bool) $data['is_active'],
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        if ($this->isEditing) {
            $user = User::findOrFail($this->userId);
            $user->update($payload);
            $user->syncRoles([$data['role']]);

            session()->flash('success', 'Usuario actualizado correctamente.');
        } else {
            $user = User::create($payload);
            $user->syncRoles([$data['role']]);

            session()->flash('success', 'Usuario creado correctamente.');
        }

        $this->resetForm();
        $this->showModal = false;
    }

    public function delete(int $id): void
    {
        if ((int) auth()->id() === $id) {
            session()->flash('warning', 'No puedes eliminar tu propio usuario desde este módulo.');
            return;
        }

        $user = User::findOrFail($id);
        $user->delete();

        session()->flash('success', 'Usuario eliminado correctamente.');
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
            'userId',
            'name',
            'email',
            'password',
            'password_confirmation',
            'school_id',
            'grade_id',
            'course_id',
            'role',
        ]);

        $this->is_active = true;
        $this->isEditing = false;
    }

    protected function rules(): array
    {
        $passwordRules = $this->isEditing
            ? ['nullable', 'string', 'min:8', 'confirmed']
            : ['required', 'string', 'min:8', 'confirmed'];

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->userId),
            ],
            'password' => $passwordRules,
            'school_id' => ['nullable', 'integer', 'exists:schools,id'],
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
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function render()
    {
        $users = User::query()
            ->with(['school', 'grade', 'course', 'roles'])
            ->when($this->search, function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhereHas('school', fn ($q) => $q->where('name', 'like', '%' . $this->search . '%'))
                        ->orWhereHas('grade', fn ($q) => $q->where('name', 'like', '%' . $this->search . '%')->orWhere('label', 'like', '%' . $this->search . '%'))
                        ->orWhereHas('course', fn ($q) => $q->where('name', 'like', '%' . $this->search . '%')->orWhere('label', 'like', '%' . $this->search . '%'))
                        ->orWhereHas('roles', fn ($q) => $q->where('name', 'like', '%' . $this->search . '%'));
                });
            })
            ->latest()
            ->paginate($this->perPage);

        $schools = School::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $roles = Role::query()
            ->orderBy('name')
            ->get(['name']);

        $grades = Grade::query()
            ->when($this->school_id, fn ($query) => $query->where('school_id', $this->school_id))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'label']);

        $courses = Course::query()
            ->when($this->school_id, fn ($query) => $query->where('school_id', $this->school_id))
            ->when($this->grade_id, fn ($query) => $query->where('grade_id', $this->grade_id))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'label']);

        return view('livewire.admin.users.index', [
            'users' => $users,
            'schools' => $schools,
            'roles' => $roles,
            'grades' => $grades,
            'courses' => $courses,
        ])->layout('components.layouts.app');
    }
}