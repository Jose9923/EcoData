<?php

namespace App\Exports\Sheets;

use App\Exports\Concerns\AppliesSchoolExcelBranding;
use App\Models\Grade;
use App\Models\School;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GradesSheetExport implements FromArray, WithTitle, WithStyles, ShouldAutoSize
{
    use AppliesSchoolExcelBranding;

    public function __construct(
        protected ?School $school = null
    ) {}

    public function title(): string
    {
        return 'grades';
    }

    public function array(): array
    {
        $rows = [['ID', 'Colegio', 'Grado', 'Etiqueta']];

        foreach (Grade::query()->with('school')->orderBy('school_id')->orderBy('name')->get() as $grade) {
            $rows[] = [
                $grade->id,
                $grade->school?->name,
                $grade->name,
                $grade->label,
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $this->applyAuxiliaryHeaderStyle($sheet, 'A1:D1', $this->school);
        $this->applyBodyStyle($sheet, 'A2:D1000', $this->school);

        return [];
    }
}