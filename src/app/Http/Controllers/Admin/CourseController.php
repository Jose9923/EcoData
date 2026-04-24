<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseRequest;
use App\Http\Requests\Admin\UpdateCourseRequest;
use App\Models\Course;
use App\Models\Grade;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $authUser = $request->user();

        $search = (string) $request->string('search')->toString();
        $perPage = (int) $request->integer('per_page', 10);

        if (! in_array($perPage, [10, 15, 25, 50], true)) {
            $perPage = 10;
        }

        $courses = Course::query()
            ->with(['school', 'grade'])
            ->when(! $authUser->hasRole('super_admin'), function ($query) use ($authUser) {
                $query->where('school_id', $authUser->school_id);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('label', 'like', '%' . $search . '%')
                        ->orWhereHas('school', fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
                        ->orWhereHas('grade', fn ($q) => $q->where('name', 'like', '%' . $search . '%')
                            ->orWhere('label', 'like', '%' . $search . '%'));
                });
            })
            ->orderBy('school_id')
            ->orderBy('grade_id')
            ->orderBy('name')
            ->paginate($perPage)
            ->appends($request->query());

        return view('admin.courses.index', compact('courses', 'search', 'perPage'));
    }

    public function create(Request $request): View
    {
        $authUser = $request->user();

        $selectedSchoolId = $this->resolveSelectedSchoolId($request);

        return view('admin.courses.create', [
            'schools' => $this->visibleSchools($authUser),
            'grades' => $this->visibleGrades($selectedSchoolId),
            'selectedSchoolId' => $selectedSchoolId,
            'selectedGradeId' => $request->integer('grade_id') ?: null,
        ]);
    }

    public function store(StoreCourseRequest $request): RedirectResponse
    {
        $authUser = $request->user();
        $data = $request->validated();

        $schoolId = $authUser->hasRole('super_admin')
            ? (int) $data['school_id']
            : (int) $authUser->school_id;

        Course::create([
            'school_id' => $schoolId,
            'grade_id' => (int) $data['grade_id'],
            'name' => trim($data['name']),
            'label' => filled($data['label'] ?? null) ? trim($data['label']) : null,
            'is_active' => (bool) $data['is_active'],
        ]);

        return redirect()
            ->route('admin.courses.index')
            ->with('success', 'Curso creado correctamente.');
    }

    public function edit(Request $request, int $course): View
    {
        $authUser = $request->user();

        $course = Course::findOrFail($course);

        $this->authorizeSchoolScope($authUser, $course->school_id);

        $selectedSchoolId = $authUser->hasRole('super_admin')
            ? old('school_id', $request->integer('school_id') ?: $course->school_id)
            : $authUser->school_id;

        $selectedGradeId = old('grade_id', $request->integer('grade_id') ?: $course->grade_id);

        return view('admin.courses.edit', [
            'course' => $course,
            'schools' => $this->visibleSchools($authUser),
            'grades' => $this->visibleGrades((int) $selectedSchoolId),
            'selectedSchoolId' => $selectedSchoolId,
            'selectedGradeId' => $selectedGradeId,
        ]);
    }

    public function update(UpdateCourseRequest $request, int $course): RedirectResponse
    {
        $authUser = $request->user();

        $course = Course::findOrFail($course);

        $this->authorizeSchoolScope($authUser, $course->school_id);

        $data = $request->validated();

        $schoolId = $authUser->hasRole('super_admin')
            ? (int) $data['school_id']
            : (int) $authUser->school_id;

        $course->update([
            'school_id' => $schoolId,
            'grade_id' => (int) $data['grade_id'],
            'name' => trim($data['name']),
            'label' => filled($data['label'] ?? null) ? trim($data['label']) : null,
            'is_active' => (bool) $data['is_active'],
        ]);

        return redirect()
            ->route('admin.courses.index')
            ->with('success', 'Curso actualizado correctamente.');
    }

    public function destroy(Request $request, int $course): RedirectResponse
    {
        $authUser = $request->user();

        $course = Course::findOrFail($course);

        $this->authorizeSchoolScope($authUser, $course->school_id);

        $course->delete();

        return redirect()
            ->route('admin.courses.index')
            ->with('success', 'Curso eliminado correctamente.');
    }

    public function getGrades(Request $request): JsonResponse
    {
        $schoolId = $this->resolveSelectedSchoolId($request);

        $grades = $this->visibleGrades($schoolId)
            ->map(fn ($grade) => [
                'id' => $grade->id,
                'label' => $grade->label ?: $grade->name,
            ])
            ->values();

        return response()->json($grades);
    }

    private function visibleSchools(User $authUser)
    {
        return School::query()
            ->when(! $authUser->hasRole('super_admin'), fn ($query) => $query->where('id', $authUser->school_id))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function visibleGrades(?int $schoolId)
    {
        return Grade::query()
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'label']);
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