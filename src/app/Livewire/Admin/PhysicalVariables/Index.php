<?php

namespace App\Livewire\Admin\PhysicalVariables;

use App\Models\PhysicalVariable;
use App\Models\PhysicalVariableCategory;
use App\Models\School;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public int $perPage = 10;

    public ?int $variableId = null;
    public ?int $school_id = null;
    public ?int $category_id = null;
    public string $name = '';
    public string $slug = '';
    public string $unit = '';
    public string $data_type = 'decimal';
    public string $min_value = '';
    public string $max_value = '';
    public int $decimals = 2;
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

    public function updatedDataType(string $value): void
    {
        if (in_array($value, ['text', 'boolean', 'date', 'integer'], true)) {
            $this->decimals = 0;
        }

        if (in_array($value, ['text', 'boolean', 'date'], true)) {
            $this->min_value = '';
            $this->max_value = '';
        }
    }

    public function create(): void
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $variable = PhysicalVariable::findOrFail($id);

        $this->variableId = $variable->id;
        $this->school_id = $variable->school_id;
        $this->category_id = $variable->category_id;
        $this->name = $variable->name;
        $this->slug = $variable->slug;
        $this->unit = $variable->unit ?? '';
        $this->data_type = $variable->data_type;
        $this->min_value = $variable->min_value !== null ? (string) $variable->min_value : '';
        $this->max_value = $variable->max_value !== null ? (string) $variable->max_value : '';
        $this->decimals = (int) $variable->decimals;
        $this->description = $variable->description ?? '';
        $this->is_active = (bool) $variable->is_active;

        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate($this->rules());

        $resolvedSlug = trim($data['slug']) !== ''
            ? Str::slug($data['slug'])
            : Str::slug($data['name']);

        $normalized = $this->normalizePayload($data, $resolvedSlug);

        if (
            $normalized['min_value'] !== null &&
            $normalized['max_value'] !== null &&
            $normalized['min_value'] > $normalized['max_value']
        ) {
            $this->addError('min_value', 'El valor mínimo no puede ser mayor que el valor máximo.');
            return;
        }

        if ($this->isEditing) {
            $variable = PhysicalVariable::findOrFail($this->variableId);
            $variable->update($normalized);

            session()->flash('success', 'Variable física actualizada correctamente.');
        } else {
            PhysicalVariable::create($normalized);

            session()->flash('success', 'Variable física creada correctamente.');
        }

        $this->resetForm();
        $this->showModal = false;
    }

    public function delete(int $id): void
    {
        $variable = PhysicalVariable::findOrFail($id);

        if ($variable->recordValues()->exists()) {
            session()->flash('error', 'No puedes eliminar esta variable porque ya tiene registros asociados.');
            return;
        }

        $variable->delete();

        session()->flash('success', 'Variable física eliminada correctamente.');
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
            'variableId',
            'school_id',
            'category_id',
            'name',
            'slug',
            'unit',
            'min_value',
            'max_value',
            'description',
        ]);

        $this->data_type = 'decimal';
        $this->decimals = 2;
        $this->is_active = true;
        $this->isEditing = false;
    }

    protected function rules(): array
    {
        return [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'category_id' => ['required', 'integer', 'exists:physical_variable_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('physical_variables', 'slug')
                    ->where(fn ($query) => $query->where('school_id', $this->school_id))
                    ->ignore($this->variableId),
            ],
            'unit' => ['nullable', 'string', 'max:50'],
            'data_type' => ['required', Rule::in(PhysicalVariable::DATA_TYPES)],
            'min_value' => ['nullable', 'numeric'],
            'max_value' => ['nullable', 'numeric'],
            'decimals' => ['nullable', 'integer', 'min:0', 'max:6'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function normalizePayload(array $data, string $resolvedSlug): array
    {
        $dataType = $data['data_type'];

        $minValue = trim((string) ($data['min_value'] ?? '')) !== '' ? (float) $data['min_value'] : null;
        $maxValue = trim((string) ($data['max_value'] ?? '')) !== '' ? (float) $data['max_value'] : null;
        $decimals = isset($data['decimals']) ? (int) $data['decimals'] : 0;

        if (in_array($dataType, ['text', 'boolean', 'date'], true)) {
            $minValue = null;
            $maxValue = null;
            $decimals = 0;
        }

        if ($dataType === 'integer') {
            $decimals = 0;
        }

        if ($dataType === 'decimal' && $decimals < 0) {
            $decimals = 2;
        }

        return [
            'school_id' => $data['school_id'],
            'category_id' => $data['category_id'],
            'name' => trim($data['name']),
            'slug' => $resolvedSlug,
            'unit' => trim((string) ($data['unit'] ?? '')) !== '' ? trim($data['unit']) : null,
            'data_type' => $dataType,
            'min_value' => $minValue,
            'max_value' => $maxValue,
            'decimals' => $decimals,
            'description' => trim((string) ($data['description'] ?? '')) !== '' ? trim($data['description']) : null,
            'is_active' => (bool) $data['is_active'],
        ];
    }

    public function render()
    {
        $variables = PhysicalVariable::query()
            ->with(['school', 'category'])
            ->when($this->search, function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('slug', 'like', '%' . $this->search . '%')
                        ->orWhere('unit', 'like', '%' . $this->search . '%')
                        ->orWhere('data_type', 'like', '%' . $this->search . '%')
                        ->orWhereHas('school', fn ($q) => $q->where('name', 'like', '%' . $this->search . '%'))
                        ->orWhereHas('category', fn ($q) => $q->where('name', 'like', '%' . $this->search . '%'));
                });
            })
            ->orderBy('school_id')
            ->orderBy('category_id')
            ->orderBy('name')
            ->paginate($this->perPage);

        $schools = School::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $categories = PhysicalVariableCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.admin.physical-variables.index', [
            'variables' => $variables,
            'schools' => $schools,
            'categories' => $categories,
            'dataTypes' => PhysicalVariable::DATA_TYPES,
        ])->layout('components.layouts.app');
    }
}