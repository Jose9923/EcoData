<?php

namespace App\Livewire\Admin\PhysicalVariableCategories;

use App\Models\PhysicalVariableCategory;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public int $perPage = 10;

    public ?int $categoryId = null;
    public string $name = '';
    public string $slug = '';
    public string $description = '';
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
        $category = PhysicalVariableCategory::findOrFail($id);

        $this->categoryId = $category->id;
        $this->name = $category->name;
        $this->slug = $category->slug;
        $this->description = $category->description ?? '';
        $this->is_active = (bool) $category->is_active;

        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate($this->rules());

        $resolvedSlug = trim($data['slug']) !== ''
            ? Str::slug($data['slug'])
            : Str::slug($data['name']);

        $payload = [
            'name' => trim($data['name']),
            'slug' => $resolvedSlug,
            'description' => trim($data['description']) !== '' ? trim($data['description']) : null,
            'is_active' => (bool) $data['is_active'],
        ];

        if ($this->isEditing) {
            $category = PhysicalVariableCategory::findOrFail($this->categoryId);
            $category->update($payload);

            session()->flash('success', 'Categoría actualizada correctamente.');
        } else {
            PhysicalVariableCategory::create($payload);

            session()->flash('success', 'Categoría creada correctamente.');
        }

        $this->resetForm();
        $this->showModal = false;
    }

    public function delete(int $id): void
    {
        $category = PhysicalVariableCategory::findOrFail($id);

        if ($category->variables()->exists()) {
            session()->flash('error', 'No puedes eliminar esta categoría porque tiene variables físicas asociadas.');
            return;
        }

        $category->delete();

        session()->flash('success', 'Categoría eliminada correctamente.');
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
            'categoryId',
            'name',
            'slug',
            'description',
        ]);

        $this->is_active = true;
        $this->isEditing = false;
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('physical_variable_categories', 'slug')->ignore($this->categoryId),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function render()
    {
        $categories = PhysicalVariableCategory::query()
            ->withCount('variables')
            ->when($this->search, function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('slug', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.admin.physical-variable-categories.index', [
            'categories' => $categories,
        ])->layout('components.layouts.app');
    }
}