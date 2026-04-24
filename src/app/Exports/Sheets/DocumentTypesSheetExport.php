<?php

namespace App\Exports\Sheets;

use App\Exports\Concerns\AppliesSchoolExcelBranding;
use App\Models\School;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DocumentTypesSheetExport implements FromArray, WithTitle, WithStyles, ShouldAutoSize
{
    use AppliesSchoolExcelBranding;

    public function __construct(
        protected ?School $school = null
    ) {}

    public function title(): string
    {
        return 'tipos_documento';
    }

    public function array(): array
    {
        return [
            ['Código'],
            ['CC'],
            ['TI'],
            ['CE'],
            ['PPT'],
            ['NIT'],
            ['PAS'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $this->applyAuxiliaryHeaderStyle($sheet, 'A1:A1', $this->school);
        $this->applyBodyStyle($sheet, 'A2:A20', $this->school);

        return [];
    }
}