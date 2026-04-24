<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLaboratoryGuideRequest;
use App\Http\Requests\Admin\UpdateLaboratoryGuideRequest;
use App\Models\Course;
use App\Models\Grade;
use App\Models\LaboratoryGuide;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LaboratoryGuideController extends Controller
{
    public function index(Request $request): View
    {
        $search = (string) $request->string('search')->toString();

        $guides = LaboratoryGuide::query()
            ->with(['school', 'grade', 'course', 'creator'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->appends($request->query());

        return view('admin.laboratory-guides.index', compact('guides', 'search'));
    }

    public function create(): View
    {
        return view('admin.laboratory-guides.create', [
            'schools' => School::where('is_active', true)->orderBy('name')->get(),
            'grades' => Grade::where('is_active', true)->orderBy('name')->get(),
            'courses' => Course::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreLaboratoryGuideRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $path = $request->file('pdf')->store('laboratory-guides', 'public');

        LaboratoryGuide::create([
            'school_id' => $data['school_id'],
            'grade_id' => $data['grade_id'] ?? null,
            'course_id' => $data['course_id'] ?? null,
            'title' => trim($data['title']),
            'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
            'pdf_path' => $path,
            'published_at' => $data['published_at'] ?? now(),
            'is_active' => (bool) $data['is_active'],
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.laboratory-guides.index')
            ->with('success', 'Guía de laboratorio cargada correctamente.');
    }

    public function edit(LaboratoryGuide $laboratory_guide): View
    {
        return view('admin.laboratory-guides.edit', [
            'guide' => $laboratory_guide,
            'schools' => School::where('is_active', true)->orderBy('name')->get(),
            'grades' => Grade::where('is_active', true)->orderBy('name')->get(),
            'courses' => Course::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateLaboratoryGuideRequest $request, LaboratoryGuide $laboratory_guide): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('pdf')) {
            if ($laboratory_guide->pdf_path && Storage::disk('public')->exists($laboratory_guide->pdf_path)) {
                Storage::disk('public')->delete($laboratory_guide->pdf_path);
            }

            $laboratory_guide->pdf_path = $request->file('pdf')->store('laboratory-guides', 'public');
        }

        $laboratory_guide->fill([
            'school_id' => $data['school_id'],
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

    public function destroy(LaboratoryGuide $laboratory_guide): RedirectResponse
    {
        if ($laboratory_guide->pdf_path && Storage::disk('public')->exists($laboratory_guide->pdf_path)) {
            Storage::disk('public')->delete($laboratory_guide->pdf_path);
        }

        $laboratory_guide->delete();

        return redirect()
            ->route('admin.laboratory-guides.index')
            ->with('success', 'Guía de laboratorio eliminada correctamente.');
    }
}