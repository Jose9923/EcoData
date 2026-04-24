<?php

namespace App\Http\Controllers\Admin;

use App\Exports\UserImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportUsersRequest;
use App\Services\UserImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class UserImportController extends Controller
{
    public function create(): View
    {
        return view('admin.users.import');
    }

    public function template()
    {
        $school = auth()->user()?->school;

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\UserImportTemplateExport($school),
            'plantilla_cargue_usuarios.xlsx'
        );
    }

    public function store(ImportUsersRequest $request, UserImportService $service): RedirectResponse
    {
        $file = $request->file('file');
        $rows = Excel::toArray([], $file)[0] ?? [];

        if (count($rows) < 2) {
            return back()->withErrors([
                'file' => 'El archivo no contiene filas de datos.',
            ]);
        }

        $headers = array_map(fn ($header) => strtolower(trim((string) $header)), $rows[0]);
        $dataRows = array_slice($rows, 1);

        $normalizedRows = collect($dataRows)
            ->filter(fn ($row) => collect($row)->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty())
            ->map(function ($row) use ($headers) {
                $assoc = [];
                foreach ($headers as $index => $header) {
                    $assoc[$header] = $row[$index] ?? null;
                }
                return $assoc;
            })
            ->values()
            ->all();

        $summary = $service->process($normalizedRows, $request->validated()['mode']);

        return back()->with('import_summary', $summary);
    }
}