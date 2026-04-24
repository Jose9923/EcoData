<?php

namespace App\Http\Controllers\Admin;

use App\Exports\UserImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportUsersRequest;
use App\Services\UserImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
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
        return Excel::download(
            new UserImportTemplateExport(auth()->user()),
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

        $headerInfo = $this->detectHeaderRow($rows);

        if (! $headerInfo) {
            return back()->withErrors([
                'file' => 'No se encontró una fila de encabezados válida. Verifica que la hoja contenga las columnas name, email, document_type, document_number, role, school, grade, course, password e is_active.',
            ]);
        }

        [$headerRowIndex, $headers] = $headerInfo;

        $dataRows = array_slice($rows, $headerRowIndex + 1);

        $normalizedRows = collect($dataRows)
            ->filter(fn ($row) => collect($row)->filter(fn ($value) => $value !== null && trim((string) $value) !== '')->isNotEmpty())
            ->map(function ($row) use ($headers) {
                $assoc = [];

                foreach ($headers as $index => $header) {
                    if ($header === null || $header === '') {
                        continue;
                    }

                    $assoc[$header] = $row[$index] ?? null;
                }

                return $assoc;
            })
            ->filter(fn ($row) => collect($row)->filter(fn ($value) => $value !== null && trim((string) $value) !== '')->isNotEmpty())
            ->values()
            ->all();

        if (empty($normalizedRows)) {
            return back()->withErrors([
                'file' => 'El archivo no contiene filas de usuarios para importar.',
            ]);
        }

        $summary = $service->process(
            rows: $normalizedRows,
            mode: $request->validated()['mode'],
            authUser: $request->user()
        );

        return back()->with('import_summary', $summary);
    }

    private function detectHeaderRow(array $rows): ?array
    {
        $requiredHeaders = [
            'name',
            'email',
            'document_type',
            'document_number',
            'role',
        ];

        foreach ($rows as $rowIndex => $row) {
            $headers = array_map(function ($header) {
                $header = strtolower(trim((string) $header));
                $header = Str::ascii($header);
                $header = str_replace([' ', '-', '.'], '_', $header);

                return $header;
            }, $row);

            $presentHeaders = array_filter($headers, fn ($header) => $header !== '');

            $hasRequiredHeaders = collect($requiredHeaders)
                ->every(fn ($requiredHeader) => in_array($requiredHeader, $presentHeaders, true));

            if ($hasRequiredHeaders) {
                return [$rowIndex, $headers];
            }
        }

        return null;
    }
}