<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePhysicalVariableCategoryRequest;
use App\Http\Requests\Admin\UpdatePhysicalVariableCategoryRequest;
use App\Models\PhysicalVariableCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PhysicalVariableCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = (string) $request->string('search')->toString();
        $perPage = (int) $request->integer('per_page', 10);

        if (! in_array($perPage, [10, 15, 25, 50], true)) {
            $perPage = 10;
        }

        $categories = PhysicalVariableCategory::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('slug', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%')
                        ->orWhere('icon', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->appends($request->query());

        return view('admin.physical-variable-categories.index', compact('categories', 'search', 'perPage'));
    }

    public function create(): View
    {
        return view('admin.physical-variable-categories.create');
    }

    public function store(StorePhysicalVariableCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();

        PhysicalVariableCategory::create([
            'name' => trim($data['name']),
            'slug' => filled($data['slug'] ?? null) ? Str::slug($data['slug']) : Str::slug($data['name']),
            'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
            'is_active' => (bool) $data['is_active'],
        ]);

        return redirect()
            ->route('admin.physical-variable-categories.index')
            ->with('success', 'Categoría creada correctamente.');
    }

    public function edit(int $physical_variable_category): View
    {
        $category = PhysicalVariableCategory::findOrFail($physical_variable_category);

        return view('admin.physical-variable-categories.edit', compact('category'));
    }

    public function update(UpdatePhysicalVariableCategoryRequest $request, int $physical_variable_category): RedirectResponse
    {
        $category = PhysicalVariableCategory::findOrFail($physical_variable_category);
        $data = $request->validated();

        $category->update([
            'name' => trim($data['name']),
            'slug' => filled($data['slug'] ?? null) ? Str::slug($data['slug']) : Str::slug($data['name']),
            'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
            'is_active' => (bool) $data['is_active'],
        ]);

        return redirect()
            ->route('admin.physical-variable-categories.index')
            ->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroy(int $physical_variable_category): RedirectResponse
    {
        $category = PhysicalVariableCategory::findOrFail($physical_variable_category);
        $category->delete();

        return redirect()
            ->route('admin.physical-variable-categories.index')
            ->with('success', 'Categoría eliminada correctamente.');
    }
}