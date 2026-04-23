<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseRequest;
use App\Http\Requests\Admin\UpdateCourseRequest;
use App\Models\Course;
use App\Models\Grade;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $search = (string) $request->string('search')->toString();
        $perPage = (int) $request->integer('per_page', 10);

        if (! in_array($perPage, [10, 15, 25, 50], true)) {
            $perPage = 10;
        }

        $courses = Course::query()
            ->with(['school', 'grade'])
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
        $selectedSchoolId = $request->integer('school_id') ?: null;

        return view('admin.courses.create', [
            'schools' => School::query()->orderBy('name')->get(['id', 'name']),
            'grades' => Grade::query()
                ->when($selectedSchoolId, fn ($q) => $q->where('school_id', $selectedSchoolId))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'label']),
            'selectedSchoolId' => $selectedSchoolId,
        ]);
    }

    public function store(StoreCourseRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Course::create([
            'school_id' => $data['school_id'],
            'grade_id' => $data['grade_id'],
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
        $course = Course::findOrFail($course);

        $selectedSchoolId = old('school_id', $request->integer('school_id') ?: $course->school_id);

        return view('admin.courses.edit', [
            'course' => $course,
            'schools' => School::query()->orderBy('name')->get(['id', 'name']),
            'grades' => Grade::query()
                ->when($selectedSchoolId, fn ($q) => $q->where('school_id', $selectedSchoolId))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'label']),
            'selectedSchoolId' => $selectedSchoolId,
        ]);
    }

    public function update(UpdateCourseRequest $request, int $course): RedirectResponse
    {
        $course = Course::findOrFail($course);
        $data = $request->validated();

        $course->update([
            'school_id' => $data['school_id'],
            'grade_id' => $data['grade_id'],
            'name' => trim($data['name']),
            'label' => filled($data['label'] ?? null) ? trim($data['label']) : null,
            'is_active' => (bool) $data['is_active'],
        ]);

        return redirect()
            ->route('admin.courses.index')
            ->with('success', 'Curso actualizado correctamente.');
    }

    public function destroy(int $course): RedirectResponse
    {
        $course = Course::findOrFail($course);
        $course->delete();

        return redirect()
            ->route('admin.courses.index')
            ->with('success', 'Curso eliminado correctamente.');
    }

    public function getGrades(Request $request): JsonResponse
    {
        $schoolId = $request->integer('school_id');

        $grades = Grade::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'label'])
            ->map(fn ($grade) => [
                'id' => $grade->id,
                'label' => $grade->label ?: $grade->name,
            ])
            ->values();

        return response()->json($grades);
    }
}