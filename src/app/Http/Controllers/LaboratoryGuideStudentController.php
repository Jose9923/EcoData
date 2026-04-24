<?php

namespace App\Http\Controllers;

use App\Models\LaboratoryGuide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LaboratoryGuideStudentController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();

        $guides = LaboratoryGuide::query()
            ->with(['school', 'grade', 'course'])
            ->where('is_active', true)
            ->where('school_id', $user->school_id)
            ->where(function ($query) use ($user) {
                $query->whereNull('grade_id')
                    ->orWhere('grade_id', $user->grade_id);
            })
            ->where(function ($query) use ($user) {
                $query->whereNull('course_id')
                    ->orWhere('course_id', $user->course_id);
            })
            ->latest('published_at')
            ->paginate(10);

        return view('student.laboratory-guides.index', compact('guides'));
    }

    public function download(LaboratoryGuide $laboratory_guide)
    {
        $user = auth()->user();

        abort_unless(
            $laboratory_guide->is_active &&
            $laboratory_guide->school_id === $user->school_id &&
            ($laboratory_guide->grade_id === null || $laboratory_guide->grade_id === $user->grade_id) &&
            ($laboratory_guide->course_id === null || $laboratory_guide->course_id === $user->course_id),
            403
        );

        return Storage::disk('public')->download(
            $laboratory_guide->pdf_path,
            $laboratory_guide->title . '.pdf'
        );
    }
}