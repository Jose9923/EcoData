<?php

namespace App\Http\Controllers;

use App\Models\LaboratoryGuide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaboratoryGuideStudentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        abort_if(! $user->school_id, 403, 'Tu usuario no tiene un colegio asignado.');

        $guides = LaboratoryGuide::query()
            ->with(['school', 'grade', 'course'])
            ->where('is_active', true)
            ->where('school_id', $user->school_id)
            ->where(function ($query) {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->where(function ($query) use ($user) {
                $query->whereNull('grade_id')
                    ->orWhere('grade_id', $user->grade_id);
            })
            ->where(function ($query) use ($user) {
                $query->whereNull('course_id')
                    ->orWhere('course_id', $user->course_id);
            })
            ->latest('published_at')
            ->paginate(10)
            ->appends($request->query());

        return view('student.laboratory-guides.index', compact('guides'));
    }

    public function view(Request $request, LaboratoryGuide $laboratory_guide): StreamedResponse
    {
        $this->authorizeGuideForStudent($request, $laboratory_guide);

        $fileName = Str::slug($laboratory_guide->title) . '.pdf';

        return Storage::disk('public')->response(
            $laboratory_guide->pdf_path,
            $fileName,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            ]
        );
    }

    public function download(Request $request, LaboratoryGuide $laboratory_guide): StreamedResponse
    {
        $this->authorizeGuideForStudent($request, $laboratory_guide);

        $fileName = Str::slug($laboratory_guide->title) . '.pdf';

        return Storage::disk('public')->download(
            $laboratory_guide->pdf_path,
            $fileName
        );
    }

    private function authorizeGuideForStudent(Request $request, LaboratoryGuide $guide): void
    {
        $user = $request->user();

        abort_if(! $user->school_id, 403, 'Tu usuario no tiene un colegio asignado.');

        abort_unless($guide->is_active, 403, 'La guía no está activa.');

        abort_if(
            $guide->published_at && $guide->published_at->isFuture(),
            403,
            'La guía aún no está disponible.'
        );

        abort_if(
            (int) $guide->school_id !== (int) $user->school_id,
            403,
            'No tienes autorización para acceder a guías de otro colegio.'
        );

        abort_if(
            $guide->grade_id !== null && (int) $guide->grade_id !== (int) $user->grade_id,
            403,
            'No tienes autorización para acceder a guías de otro grado.'
        );

        abort_if(
            $guide->course_id !== null && (int) $guide->course_id !== (int) $user->course_id,
            403,
            'No tienes autorización para acceder a guías de otro curso.'
        );

        abort_if(! $guide->pdf_path, 404, 'La guía no tiene un archivo PDF asociado.');

        abort_if(
            ! Storage::disk('public')->exists($guide->pdf_path),
            404,
            'El archivo PDF no existe.'
        );
    }
}