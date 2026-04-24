<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePhysicalVariableRequest;
use App\Http\Requests\Admin\UpdatePhysicalVariableRequest;
use App\Models\PhysicalVariable;
use App\Models\PhysicalVariableCategory;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PhysicalVariableController extends Controller
{
    public function index(Request $request): View
    {
        $authUser = $request->user();

        $search = (string) $request->string('search')->toString();
        $perPage = (int) $request->integer('per_page', 10);

        if (! in_array($perPage, [10, 15, 25, 50], true)) {
            $perPage = 10;
        }

        $variables = PhysicalVariable::query()
            ->with(['school', 'category'])
            ->when(! $authUser->hasRole('super_admin'), function ($query) use ($authUser) {
                $query->where('school_id', $authUser->school_id);
            })
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

    public function create(Request $request): View
    {
        $authUser = $request->user();

        return view('admin.physical-variables.create', [
            'schools' => $this->visibleSchools($authUser),
            'categories' => $this->visibleCategories(),
            'dataTypes' => $this->dataTypes(),
            'selectedSchoolId' => $this->resolveSelectedSchoolId($request),
        ]);
    }

    public function store(StorePhysicalVariableRequest $request): RedirectResponse
    {
        $authUser = $request->user();
        $data = $request->validated();

        $schoolId = $authUser->hasRole('super_admin')
            ? (int) $data['school_id']
            : (int) $authUser->school_id;

        $dataType = $data['data_type'];

        PhysicalVariable::create([
            'school_id' => $schoolId,
            'category_id' => (int) $data['category_id'],
            'name' => trim($data['name']),
            'slug' => filled($data['slug'] ?? null) ? Str::slug($data['slug']) : Str::slug($data['name']),
            'unit' => filled($data['unit'] ?? null) ? trim($data['unit']) : null,
            'data_type' => $dataType,
            'min_value' => in_array($dataType, ['integer', 'decimal'], true) ? ($data['min_value'] ?? null) : null,
            'max_value' => in_array($dataType, ['integer', 'decimal'], true) ? ($data['max_value'] ?? null) : null,
            'decimals' => $dataType === 'decimal' ? (int) $data['decimals'] : 0,
            'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
            'is_active' => (bool) $data['is_active'],
        ]);

        return redirect()
            ->route('admin.physical-variables.index')
            ->with('success', 'Variable física creada correctamente.');
    }

    public function edit(Request $request, int $physical_variable): View
    {
        $authUser = $request->user();

        $variable = PhysicalVariable::findOrFail($physical_variable);

        $this->authorizeSchoolScope($authUser, $variable->school_id);

        return view('admin.physical-variables.edit', [
            'variable' => $variable,
            'schools' => $this->visibleSchools($authUser),
            'categories' => $this->visibleCategories(),
            'dataTypes' => $this->dataTypes(),
            'selectedSchoolId' => $authUser->hasRole('super_admin')
                ? old('school_id', $request->integer('school_id') ?: $variable->school_id)
                : $authUser->school_id,
        ]);
    }

    public function update(UpdatePhysicalVariableRequest $request, int $physical_variable): RedirectResponse
    {
        $authUser = $request->user();

        $variable = PhysicalVariable::findOrFail($physical_variable);

        $this->authorizeSchoolScope($authUser, $variable->school_id);

        $data = $request->validated();

        $schoolId = $authUser->hasRole('super_admin')
            ? (int) $data['school_id']
            : (int) $authUser->school_id;

        $dataType = $data['data_type'];

        $variable->update([
            'school_id' => $schoolId,
            'category_id' => (int) $data['category_id'],
            'name' => trim($data['name']),
            'slug' => filled($data['slug'] ?? null) ? Str::slug($data['slug']) : Str::slug($data['name']),
            'unit' => filled($data['unit'] ?? null) ? trim($data['unit']) : null,
            'data_type' => $dataType,
            'min_value' => in_array($dataType, ['integer', 'decimal'], true) ? ($data['min_value'] ?? null) : null,
            'max_value' => in_array($dataType, ['integer', 'decimal'], true) ? ($data['max_value'] ?? null) : null,
            'decimals' => $dataType === 'decimal' ? (int) $data['decimals'] : 0,
            'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
            'is_active' => (bool) $data['is_active'],
        ]);

        return redirect()
            ->route('admin.physical-variables.index')
            ->with('success', 'Variable física actualizada correctamente.');
    }

    public function destroy(Request $request, int $physical_variable): RedirectResponse
    {
        $authUser = $request->user();

        $variable = PhysicalVariable::findOrFail($physical_variable);

        $this->authorizeSchoolScope($authUser, $variable->school_id);

        $variable->delete();

        return redirect()
            ->route('admin.physical-variables.index')
            ->with('success', 'Variable física eliminada correctamente.');
    }

    private function visibleSchools(User $authUser)
    {
        return School::query()
            ->where('is_active', true)
            ->when(! $authUser->hasRole('super_admin'), fn ($query) => $query->where('id', $authUser->school_id))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function visibleCategories()
    {
        return PhysicalVariableCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function dataTypes(): array
    {
        return [
            'integer' => 'Entero',
            'decimal' => 'Decimal',
            'text' => 'Texto',
            'boolean' => 'Booleano',
            'date' => 'Fecha',
        ];
    }

    private function resolveSelectedSchoolId(Request $request): ?int
    {
        $authUser = $request->user();

        if (! $authUser->hasRole('super_admin')) {
            abort_if(! $authUser->school_id, 403, 'Tu usuario no tiene un colegio asignado.');

            return (int) $authUser->school_id;
        }

        return $request->integer('school_id') ?: null;
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
            'No tienes autorización para gestionar variables físicas de otro colegio.'
        );
    }
}