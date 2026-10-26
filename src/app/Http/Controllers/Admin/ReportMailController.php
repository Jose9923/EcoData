<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Notifications\PhysicalRecordsReportNotification;
use App\Services\PhysicalRecordReports;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReportMailController extends Controller
{
    public function create(Request $request, PhysicalRecordReports $reports): View
    {
        $filters = $reports->filters($request);

        return view('reports.mail', [
            'filters' => $filters,
            'variables' => $reports->variables($request->user())->with('school')->orderBy('name')->get(),
            'stations' => $reports->stations($request->user())->with('school')->orderBy('name')->get(),
            'count' => $reports->query($request->user(), $filters)->count(),
        ]);
    }

    public function send(Request $request, PhysicalRecordReports $reports): RedirectResponse
    {
        $filters = $reports->filters($request);
        $user = $request->user();
        abort_unless($user->is_active && $user->hasVerifiedEmail(), 403);
        $count = $reports->query($user, $filters)->count();
        if ($count === 0 || $count > 2500) {
            throw ValidationException::withMessages([
                'date_from' => $count === 0
                    ? 'No hay registros en el período seleccionado.'
                    : 'El reporte por correo admite hasta 2500 registros. Reduce el período o selecciona una estación.',
            ]);
        }
        $user->notify(new PhysicalRecordsReportNotification($filters, $user->email, $user->school_id));

        return redirect()->route('reports.mail', $filters)
            ->with('success', 'El reporte quedó en cola para tu correo verificado. Si falla el envío, el sistema lo reintentará.');
    }
}
