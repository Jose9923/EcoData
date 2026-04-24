<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGradeRequest;
use App\Http\Requests\Admin\UpdateGradeRequest;
use App\Models\Grade;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GradeController extends Controller
{
    public function index(Request $request): View
    {
        $authUser = $request->user();

        $search = (string) $request->string('search')->toString();
        $perPage = (int) $request->integer('per_page', 10);

        if (! in_array($perPage, [10, 15, 25, 50], true)) {
            $perPage = 10;
        }

        $grades = Grade::query()
            ->with('school')
            ->when(! $authUser->hasRole('super_admin'), function ($query) use ($authUser) {
                $query->where('school_id', $authUser->school_id);
            })
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

    public function create(Request $request): View
    {
        $authUser = $request->user();
        $selectedSchoolId = $this->resolveSelectedSchoolId($request);

        return view('admin.grades.create', [
            'schools' => $this->visibleSchools($authUser),
            'selectedSchoolId' => $selectedSchoolId,
        ]);
    }

    public function store(StoreGradeRequest $request): RedirectResponse
    {
        $authUser = $request->user();
        $data = $request->validated();

        $schoolId = $authUser->hasRole('super_admin')
            ? (int) $data['school_id']
            : (int) $authUser->school_id;

        Grade::create([
            'school_id' => $schoolId,
            'name' => trim($data['name']),
            'label' => filled($data['label'] ?? null) ? trim($data['label']) : null,
            'is_active' => (bool) $data['is_active'],
        ]);

        return redirect()
            ->route('admin.grades.index')
            ->with('success', 'Grado creado correctamente.');
    }

    public function edit(Request $request, int $grade): View
    {
        $authUser = $request->user();

        $grade = Grade::findOrFail($grade);

        $this->authorizeSchoolScope($authUser, $grade->school_id);

        $selectedSchoolId = $authUser->hasRole('super_admin')
            ? old('school_id', $grade->school_id)
            : $authUser->school_id;

        return view('admin.grades.edit', [
            'grade' => $grade,
            'schools' => $this->visibleSchools($authUser),
            'selectedSchoolId' => $selectedSchoolId,
        ]);
    }

    public function update(UpdateGradeRequest $request, int $grade): RedirectResponse
    {
        $authUser = $request->user();

        $grade = Grade::findOrFail($grade);

        $this->authorizeSchoolScope($authUser, $grade->school_id);

        $data = $request->validated();

        $schoolId = $authUser->hasRole('super_admin')
            ? (int) $data['school_id']
            : (int) $authUser->school_id;

        $grade->update([
            'school_id' => $schoolId,
            'name' => trim($data['name']),
            'label' => filled($data['label'] ?? null) ? trim($data['label']) : null,
            'is_active' => (bool) $data['is_active'],
        ]);

        return redirect()
            ->route('admin.grades.index')
            ->with('success', 'Grado actualizado correctamente.');
    }

    public function destroy(Request $request, int $grade): RedirectResponse
    {
        $authUser = $request->user();

        $grade = Grade::findOrFail($grade);

        $this->authorizeSchoolScope($authUser, $grade->school_id);

        $grade->delete();

        return redirect()
            ->route('admin.grades.index')
            ->with('success', 'Grado eliminado correctamente.');
    }

    private function visibleSchools(User $authUser)
    {
        return School::query()
            ->when(! $authUser->hasRole('super_admin'), fn ($query) => $query->where('id', $authUser->school_id))
            ->orderBy('name')
            ->get(['id', 'name']);
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
            'No tienes autorización para gestionar información de otro colegio.'
        );
    }
}