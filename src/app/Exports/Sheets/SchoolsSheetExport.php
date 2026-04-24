<?php

namespace App\Exports\Sheets;

use App\Exports\Concerns\AppliesSchoolExcelBranding;
use App\Models\School;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SchoolsSheetExport implements FromArray, WithTitle, WithStyles, ShouldAutoSize
{
    use AppliesSchoolExcelBranding;

    public function __construct(
        protected ?School $school = null
    ) {}

    public function title(): string
    {
        return 'schools';
    }

    public function array(): array
    {
        $rows = [['ID', 'Nombre', 'Slug']];

        foreach (School::query()->orderBy('name')->get() as $school) {
            $rows[] = [
                $school->id,
                $school->name,
                $school->slug,
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $this->applyAuxiliaryHeaderStyle($sheet, 'A1:C1', $this->school);
        $this->applyBodyStyle($sheet, 'A2:C500', $this->school);

        return [];
    }
}