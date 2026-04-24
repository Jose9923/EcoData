<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLaboratoryGuideRequest;
use App\Http\Requests\Admin\UpdateLaboratoryGuideRequest;
use App\Models\Course;
use App\Models\Grade;
use App\Models\LaboratoryGuide;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaboratoryGuideController extends Controller
{
    public function index(Request $request): View
    {
        $authUser = $request->user();

        $search = (string) $request->string('search')->toString();

        $guides = LaboratoryGuide::query()
            ->with(['school', 'grade', 'course', 'creator'])
            ->when(! $authUser->hasRole('super_admin'), function ($query) use ($authUser) {
                $query->where('school_id', $authUser->school_id);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('school', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('grade', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('label', 'like', "%{$search}%"))
                        ->orWhereHas('course', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('label', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(10)
            ->appends($request->query());

        return view('admin.laboratory-guides.index', compact('guides', 'search'));
    }

    public function create(Request $request): View
    {
        $authUser = $request->user();

        $selectedSchoolId = $this->resolveSelectedSchoolId($request);
        $selectedGradeId = $request->integer('grade_id') ?: null;

        return view('admin.laboratory-guides.create', [
            'schools' => $this->visibleSchools($authUser),
            'grades' => $this->visibleGrades($selectedSchoolId),
            'courses' => $this->visibleCourses($selectedSchoolId, $selectedGradeId),
            'selectedSchoolId' => $selectedSchoolId,
            'selectedGradeId' => $selectedGradeId,
            'selectedCourseId' => $request->integer('course_id') ?: null,
        ]);
    }

    public function store(StoreLaboratoryGuideRequest $request): RedirectResponse
    {
        $authUser = $request->user();
        $data = $request->validated();

        $schoolId = $authUser->hasRole('super_admin')
            ? (int) $data['school_id']
            : (int) $authUser->school_id;

        $school = School::findOrFail($schoolId);
        $grade = ! empty($data['grade_id']) ? Grade::findOrFail($data['grade_id']) : null;
        $course = ! empty($data['course_id']) ? Course::findOrFail($data['course_id']) : null;

        $path = $this->storePdf(
            file: $request->file('pdf'),
            school: $school,
            grade: $grade,
            course: $course,
            title: $data['title']
        );

        LaboratoryGuide::create([
            'school_id' => $schoolId,
            'grade_id' => $data['grade_id'] ?? null,
            'course_id' => $data['course_id'] ?? null,
            'title' => trim($data['title']),
            'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
            'pdf_path' => $path,
            'published_at' => $data['published_at'] ?? now(),
            'is_active' => (bool) $data['is_active'],
            'created_by' => $authUser->id,
        ]);

        return redirect()
            ->route('admin.laboratory-guides.index')
            ->with('success', 'Guía de laboratorio cargada correctamente.');
    }

    public function edit(Request $request, LaboratoryGuide $laboratory_guide): View
    {
        $authUser = $request->user();

        $this->authorizeSchoolScope($authUser, $laboratory_guide->school_id);

        $selectedSchoolId = $authUser->hasRole('super_admin')
            ? old('school_id', $request->integer('school_id') ?: $laboratory_guide->school_id)
            : $authUser->school_id;

        $selectedGradeId = old('grade_id', $request->integer('grade_id') ?: $laboratory_guide->grade_id);
        $selectedCourseId = old('course_id', $request->integer('course_id') ?: $laboratory_guide->course_id);

        return view('admin.laboratory-guides.edit', [
            'guide' => $laboratory_guide,
            'schools' => $this->visibleSchools($authUser),
            'grades' => $this->visibleGrades((int) $selectedSchoolId),
            'courses' => $this->visibleCourses((int) $selectedSchoolId, $selectedGradeId ? (int) $selectedGradeId : null),
            'selectedSchoolId' => $selectedSchoolId,
            'selectedGradeId' => $selectedGradeId,
            'selectedCourseId' => $selectedCourseId,
        ]);
    }

    public function update(UpdateLaboratoryGuideRequest $request, LaboratoryGuide $laboratory_guide): RedirectResponse
    {
        $authUser = $request->user();

        $this->authorizeSchoolScope($authUser, $laboratory_guide->school_id);

        $data = $request->validated();

        $schoolId = $authUser->hasRole('super_admin')
            ? (int) $data['school_id']
            : (int) $authUser->school_id;

        $school = School::findOrFail($schoolId);
        $grade = ! empty($data['grade_id']) ? Grade::findOrFail($data['grade_id']) : null;
        $course = ! empty($data['course_id']) ? Course::findOrFail($data['course_id']) : null;

        if ($request->hasFile('pdf')) {
            if ($laboratory_guide->pdf_path && Storage::disk('public')->exists($laboratory_guide->pdf_path)) {
                Storage::disk('public')->delete($laboratory_guide->pdf_path);
            }

            $laboratory_guide->pdf_path = $this->storePdf(
                file: $request->file('pdf'),
                school: $school,
                grade: $grade,
                course: $course,
                title: $data['title']
            );
        }

        $laboratory_guide->fill([
            'school_id' => $schoolId,
            'grade_id' => $data['grade_id'] ?? null,
            'course_id' => $data['course_id'] ?? null,
            'title' => trim($data['title']),
            'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
            'published_at' => $data['published_at'] ?? null,
            'is_active' => (bool) $data['is_active'],
        ])->save();

        return redirect()
            ->route('admin.laboratory-guides.index')
            ->with('success', 'Guía de laboratorio actualizada correctamente.');
    }

    public function destroy(Request $request, LaboratoryGuide $laboratory_guide): RedirectResponse
    {
        $authUser = $request->user();

        $this->authorizeSchoolScope($authUser, $laboratory_guide->school_id);

        if ($laboratory_guide->pdf_path && Storage::disk('public')->exists($laboratory_guide->pdf_path)) {
            Storage::disk('public')->delete($laboratory_guide->pdf_path);
        }

        $laboratory_guide->delete();

        return redirect()
            ->route('admin.laboratory-guides.index')
            ->with('success', 'Guía de laboratorio eliminada correctamente.');
    }

    public function download(Request $request, LaboratoryGuide $laboratory_guide): StreamedResponse
    {
        $authUser = $request->user();

        $this->authorizeSchoolScope($authUser, $laboratory_guide->school_id);

        abort_if(! $laboratory_guide->pdf_path, 404, 'La guía no tiene un archivo PDF asociado.');
        abort_if(! Storage::disk('public')->exists($laboratory_guide->pdf_path), 404, 'El archivo PDF no existe.');

        $fileName = Str::slug($laboratory_guide->title) . '.pdf';

        return Storage::disk('public')->download($laboratory_guide->pdf_path, $fileName);
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

    public function getCourses(Request $request): JsonResponse
    {
        $schoolId = $this->resolveSelectedSchoolId($request);
        $gradeId = $request->integer('grade_id') ?: null;

        $courses = $this->visibleCourses($schoolId, $gradeId)
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
            ->where('is_active', true)
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

    private function visibleCourses(?int $schoolId, ?int $gradeId = null)
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
            'No tienes autorización para gestionar guías de laboratorio de otro colegio.'
        );
    }

    private function storePdf($file, School $school, ?Grade $grade, ?Course $course, string $title): string
    {
        $schoolFolder = Str::slug($school->slug ?: $school->name);
        $gradeFolder = $grade?->name ? Str::slug($grade->name) : 'general';
        $courseFolder = $course?->label ? Str::slug($course->label) : ($course?->name ? Str::slug($course->name) : 'general');

        $fileName = now()->format('Ymd_His') . '_' . Str::slug($title) . '.pdf';

        $directory = "laboratory-guides/{$schoolFolder}/{$gradeFolder}/{$courseFolder}";

        return $file->storeAs($directory, $fileName, 'public');
    }
}