<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePhysicalVariableRequest;
use App\Http\Requests\Admin\UpdatePhysicalVariableRequest;
use App\Models\PhysicalVariable;
use App\Models\PhysicalVariableCategory;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PhysicalVariableController extends Controller
{
    public function index(Request $request): View
    {
        $search = (string) $request->string('search')->toString();
        $perPage = (int) $request->integer('per_page', 10);

        if (! in_array($perPage, [10, 15, 25, 50], true)) {
            $perPage = 10;
        }

        $variables = PhysicalVariable::query()
            ->with(['school', 'category'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('slug', 'like', '%' . $search . '%')
                        ->orWhere('unit', 'like', '%' . $search . '%')
                        ->orWhere('data_type', 'like', '%' . $search . '%')
                        ->orWhereHas('school', fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
                        ->orWhereHas('category', fn ($q) => $q->where('name', 'like', '%' . $search . '%'));
                });
            })
            ->orderBy('school_id')
            ->orderBy('category_id')
            ->orderBy('name')
            ->paginate($perPage)
            ->appends($request->query());

        return view('admin.physical-variables.index', compact('variables', 'search', 'perPage'));
    }

    public function create(): View
    {
        return view('admin.physical-variables.create', [
            'schools' => School::query()->orderBy('name')->get(['id', 'name']),
            'categories' => PhysicalVariableCategory::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'dataTypes' => $this->dataTypes(),
        ]);
    }

    public function store(StorePhysicalVariableRequest $request): RedirectResponse
    {
        $data = $this->normalizeData($request->validated());

        PhysicalVariable::create($data);

        return redirect()
            ->route('admin.physical-variables.index')
            ->with('success', 'Variable física creada correctamente.');
    }

    public function edit(int $physical_variable): View
    {
        $variable = PhysicalVariable::findOrFail($physical_variable);

        return view('admin.physical-variables.edit', [
            'variable' => $variable,
            'schools' => School::query()->orderBy('name')->get(['id', 'name']),
            'categories' => PhysicalVariableCategory::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'dataTypes' => $this->dataTypes(),
        ]);
    }

    public function update(UpdatePhysicalVariableRequest $request, int $physical_variable): RedirectResponse
    {
        $variable = PhysicalVariable::findOrFail($physical_variable);
        $data = $this->normalizeData($request->validated());

        $variable->update($data);

        return redirect()
            ->route('admin.physical-variables.index')
            ->with('success', 'Variable física actualizada correctamente.');
    }

    public function destroy(int $physical_variable): RedirectResponse
    {
        $variable = PhysicalVariable::findOrFail($physical_variable);
        $variable->delete();

        return redirect()
            ->route('admin.physical-variables.index')
            ->with('success', 'Variable física eliminada correctamente.');
    }

    protected function dataTypes(): array
    {
        return [
            'decimal' => 'Decimal',
            'integer' => 'Entero',
            'text' => 'Texto',
            'boolean' => 'Booleano',
            'date' => 'Fecha',
        ];
    }

    protected function normalizeData(array $data): array
    {
        $data['name'] = trim($data['name']);
        $data['slug'] = filled($data['slug'] ?? null)
            ? Str::slug($data['slug'])
            : Str::slug($data['name']);

        $data['unit'] = filled($data['unit'] ?? null) ? trim($data['unit']) : null;
        $data['description'] = filled($data['description'] ?? null) ? trim($data['description']) : null;
        $data['is_active'] = (bool) $data['is_active'];

        if (in_array($data['data_type'], ['text', 'boolean', 'date'], true)) {
            $data['min_value'] = null;
            $data['max_value'] = null;
            $data['decimals'] = 0;
        } elseif ($data['data_type'] === 'integer') {
            $data['min_value'] = filled($data['min_value'] ?? null) ? (int) $data['min_value'] : null;
            $data['max_value'] = filled($data['max_value'] ?? null) ? (int) $data['max_value'] : null;
            $data['decimals'] = 0;
        } else {
            $data['min_value'] = filled($data['min_value'] ?? null) ? (float) $data['min_value'] : null;
            $data['max_value'] = filled($data['max_value'] ?? null) ? (float) $data['max_value'] : null;
            $data['decimals'] = (int) ($data['decimals'] ?? 2);
        }

        return $data;
    }
}