<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Course;
use App\Models\Grade;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $authUser = $request->user();

        $search = (string) $request->string('search')->toString();
        $perPage = (int) $request->integer('per_page', 10);

        if (! in_array($perPage, [10, 15, 25, 50], true)) {
            $perPage = 10;
        }

        $users = User::query()
            ->with(['school', 'grade', 'course', 'roles'])
            ->when(! $authUser->hasRole('super_admin'), function ($query) use ($authUser) {
                $query->where('school_id', $authUser->school_id)
                    ->whereDoesntHave('roles', fn ($roleQuery) => $roleQuery->where('name', 'super_admin'));
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('document_number', 'like', '%' . $search . '%')
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
        $authUser = $request->user();

        $selectedSchoolId = $this->resolveSelectedSchoolId($request);
        $selectedGradeId = $request->integer('grade_id') ?: null;

        return view('admin.users.create', [
            'schools' => $this->visibleSchools($authUser),
            'roles' => $this->visibleRoles($authUser),
            'grades' => $this->visibleGrades($selectedSchoolId),
            'courses' => $this->visibleCourses($selectedSchoolId, $selectedGradeId),
            'selectedSchoolId' => $selectedSchoolId,
            'selectedGradeId' => $selectedGradeId,
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $authUser = $request->user();
        $data = $request->validated();

        $schoolId = $authUser->hasRole('super_admin')
            ? ($data['school_id'] ?: null)
            : $authUser->school_id;

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'document_type' => $data['document_type'],
            'document_number' => trim($data['document_number']),
            'password' => Hash::make($data['password']),
            'school_id' => $schoolId,
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
        $authUser = $request->user();

        $user = User::with('roles')->findOrFail($user);

        $this->authorizeUserScope($authUser, $user);

        $selectedSchoolId = old('school_id', $request->integer('school_id') ?: $user->school_id);
        $selectedGradeId = old('grade_id', $request->integer('grade_id') ?: $user->grade_id);

        if (! $authUser->hasRole('super_admin')) {
            $selectedSchoolId = $authUser->school_id;
        }

        return view('admin.users.edit', [
            'user' => $user,
            'schools' => $this->visibleSchools($authUser),
            'roles' => $this->visibleRoles($authUser),
            'grades' => $this->visibleGrades($selectedSchoolId),
            'courses' => $this->visibleCourses($selectedSchoolId, $selectedGradeId),
            'selectedSchoolId' => $selectedSchoolId,
            'selectedGradeId' => $selectedGradeId,
        ]);
    }

    public function update(UpdateUserRequest $request, int $user): RedirectResponse
    {
        $authUser = $request->user();

        $user = User::with('roles')->findOrFail($user);

        $this->authorizeUserScope($authUser, $user);

        $data = $request->validated();

        $schoolId = $authUser->hasRole('super_admin')
            ? ($data['school_id'] ?: null)
            : $authUser->school_id;

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'document_type' => $data['document_type'],
            'document_number' => trim($data['document_number']),
            'school_id' => $schoolId,
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

    public function destroy(Request $request, int $user): RedirectResponse
    {
        $authUser = $request->user();

        if ((int) $authUser->id === $user) {
            return redirect()
                ->route('admin.users.index')
                ->with('warning', 'No puedes eliminar tu propio usuario desde este módulo.');
        }

        $user = User::with('roles')->findOrFail($user);

        $this->authorizeUserScope($authUser, $user);

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }

    public function getGrades(Request $request): JsonResponse
    {
        $schoolId = $this->resolveSelectedSchoolId($request);

        $grades = Grade::query()
            ->where('school_id', $schoolId)
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
        $schoolId = $this->resolveSelectedSchoolId($request);
        $gradeId = $request->integer('grade_id');

        $courses = Course::query()
            ->where('school_id', $schoolId)
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

    private function visibleSchools(User $authUser)
    {
        return School::query()
            ->when(! $authUser->hasRole('super_admin'), fn ($query) => $query->where('id', $authUser->school_id))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function visibleRoles(User $authUser)
    {
        return Role::query()
            ->when(! $authUser->hasRole('super_admin'), fn ($query) => $query->where('name', '!=', 'super_admin'))
            ->orderBy('name')
            ->get(['name']);
    }

    private function visibleGrades(?int $schoolId)
    {
        return Grade::query()
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'label']);
    }

    private function visibleCourses(?int $schoolId, ?int $gradeId)
    {
        return Course::query()
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->when($gradeId, fn ($query) => $query->where('grade_id', $gradeId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'label']);
    }

    private function resolveSelectedSchoolId(Request $request): ?int
    {
        $authUser = $request->user();

        if (! $authUser->hasRole('super_admin')) {
            abort_if(! $authUser->school_id, 403, 'Tu usuario no tiene un colegio asignado.');
            return $authUser->school_id;
        }

        return $request->integer('school_id') ?: null;
    }

    private function authorizeUserScope(User $authUser, User $targetUser): void
    {
        if ($authUser->hasRole('super_admin')) {
            return;
        }

        abort_if(! $authUser->school_id, 403, 'Tu usuario no tiene un colegio asignado.');

        abort_if(
            (int) $targetUser->school_id !== (int) $authUser->school_id,
            403,
            'No tienes autorización para gestionar usuarios de otro colegio.'
        );

        abort_if(
            $targetUser->hasRole('super_admin'),
            403,
            'No tienes autorización para gestionar usuarios superadministradores.'
        );
    }
}