<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGradeRequest;
use App\Http\Requests\Admin\UpdateGradeRequest;
use App\Models\Grade;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GradeController extends Controller
{
    public function index(Request $request): View
    {
        $search = (string) $request->string('search')->toString();
        $perPage = (int) $request->integer('per_page', 10);

        if (! in_array($perPage, [10, 15, 25, 50], true)) {
            $perPage = 10;
        }

        $grades = Grade::query()
            ->with('school')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('label', 'like', '%' . $search . '%')
                        ->orWhereHas('school', fn ($q) => $q->where('name', 'like', '%' . $search . '%'));
                });
            })
            ->orderBy('school_id')
            ->orderByRaw('CAST(name AS UNSIGNED) ASC')
            ->orderBy('name')
            ->paginate($perPage)
            ->appends($request->query());

        return view('admin.grades.index', compact('grades', 'search', 'perPage'));
    }

    public function create(): View
    {
        return view('admin.grades.create', [
            'schools' => School::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreGradeRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Grade::create([
            'school_id' => $data['school_id'],
            'name' => trim($data['name']),
            'label' => filled($data['label'] ?? null) ? trim($data['label']) : null,
            'is_active' => (bool) $data['is_active'],
        ]);

        return redirect()
            ->route('admin.grades.index')
            ->with('success', 'Grado creado correctamente.');
    }

    public function edit(int $grade): View
    {
        $grade = Grade::findOrFail($grade);

        return view('admin.grades.edit', [
            'grade' => $grade,
            'schools' => School::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateGradeRequest $request, int $grade): RedirectResponse
    {
        $grade = Grade::findOrFail($grade);
        $data = $request->validated();

        $grade->update([
            'school_id' => $data['school_id'],
            'name' => trim($data['name']),
            'label' => filled($data['label'] ?? null) ? trim($data['label']) : null,
            'is_active' => (bool) $data['is_active'],
        ]);

        return redirect()
            ->route('admin.grades.index')
            ->with('success', 'Grado actualizado correctamente.');
    }

    public function destroy(int $grade): RedirectResponse
    {
        $grade = Grade::findOrFail($grade);
        $grade->delete();

        return redirect()
            ->route('admin.grades.index')
            ->with('success', 'Grado eliminado correctamente.');
    }
}