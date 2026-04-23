<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Course;
use App\Models\Grade;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = (string) $request->string('search')->toString();
        $perPage = (int) $request->integer('per_page', 10);

        if (! in_array($perPage, [10, 15, 25, 50], true)) {
            $perPage = 10;
        }

        $users = User::query()
            ->with(['school', 'grade', 'course', 'roles'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhereHas('school', fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
                        ->orWhereHas('grade', fn ($q) => $q->where('name', 'like', '%' . $search . '%')->orWhere('label', 'like', '%' . $search . '%'))
                        ->orWhereHas('course', fn ($q) => $q->where('name', 'like', '%' . $search . '%')->orWhere('label', 'like', '%' . $search . '%'))
                        ->orWhereHas('roles', fn ($q) => $q->where('name', 'like', '%' . $search . '%'));
                });
            })
            ->latest()
            ->paginate($perPage)
            ->appends($request->query());

        return view('admin.users.index', compact('users', 'search', 'perPage'));
    }

    public function create(Request $request): View
    {
        $selectedSchoolId = $request->integer('school_id') ?: null;
        $selectedGradeId = $request->integer('grade_id') ?: null;

        return view('admin.users.create', [
            'schools' => School::query()->orderBy('name')->get(['id', 'name']),
            'roles' => Role::query()->orderBy('name')->get(['name']),
            'grades' => Grade::query()
                ->when($selectedSchoolId, fn ($q) => $q->where('school_id', $selectedSchoolId))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'label']),
            'courses' => Course::query()
                ->when($selectedSchoolId, fn ($q) => $q->where('school_id', $selectedSchoolId))
                ->when($selectedGradeId, fn ($q) => $q->where('grade_id', $selectedGradeId))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'label']),
            'selectedSchoolId' => $selectedSchoolId,
            'selectedGradeId' => $selectedGradeId,
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'school_id' => $data['school_id'] ?: null,
            'grade_id' => $data['grade_id'] ?: null,
            'course_id' => $data['course_id'] ?: null,
            'is_active' => (bool) $data['is_active'],
        ]);

        $user->syncRoles([$data['role']]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function edit(Request $request, int $user): View
    {
        $user = User::with('roles')->findOrFail($user);

        $selectedSchoolId = old('school_id', $request->integer('school_id') ?: $user->school_id);
        $selectedGradeId = old('grade_id', $request->integer('grade_id') ?: $user->grade_id);

        return view('admin.users.edit', [
            'user' => $user,
            'schools' => School::query()->orderBy('name')->get(['id', 'name']),
            'roles' => Role::query()->orderBy('name')->get(['name']),
            'grades' => Grade::query()
                ->when($selectedSchoolId, fn ($q) => $q->where('school_id', $selectedSchoolId))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'label']),
            'courses' => Course::query()
                ->when($selectedSchoolId, fn ($q) => $q->where('school_id', $selectedSchoolId))
                ->when($selectedGradeId, fn ($q) => $q->where('grade_id', $selectedGradeId))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'label']),
            'selectedSchoolId' => $selectedSchoolId,
            'selectedGradeId' => $selectedGradeId,
        ]);
    }

    public function update(UpdateUserRequest $request, int $user): RedirectResponse
    {
        $user = User::findOrFail($user);
        $data = $request->validated();

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'school_id' => $data['school_id'] ?: null,
            'grade_id' => $data['grade_id'] ?: null,
            'course_id' => $data['course_id'] ?: null,
            'is_active' => (bool) $data['is_active'],
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);
        $user->syncRoles([$data['role']]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(int $user): RedirectResponse
    {
        if ((int) auth()->id() === $user) {
            return redirect()
                ->route('admin.users.index')
                ->with('warning', 'No puedes eliminar tu propio usuario desde este módulo.');
        }

        $user = User::findOrFail($user);
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Usuario eliminado correctamente.');
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

    public function getCourses(Request $request): JsonResponse
    {
        $schoolId = $request->integer('school_id');
        $gradeId = $request->integer('grade_id');

        $courses = Course::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($gradeId, fn ($q) => $q->where('grade_id', $gradeId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'label'])
            ->map(fn ($course) => [
                'id' => $course->id,
                'label' => $course->label ?: $course->name,
            ])
            ->values();

        return response()->json($courses);
    }
}