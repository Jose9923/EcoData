<?php

namespace App\Exports\Sheets;

use App\Exports\Concerns\AppliesSchoolExcelBranding;
use App\Models\School;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Spatie\Permission\Models\Role;

class RolesSheetExport implements FromArray, WithTitle, WithStyles, ShouldAutoSize
{
    use AppliesSchoolExcelBranding;

    public function __construct(
        protected ?School $school = null
    ) {}

    public function title(): string
    {
        return 'roles';
    }

    public function array(): array
    {
        $rows = [['Rol']];

        foreach (Role::query()->orderBy('name')->get() as $role) {
            $rows[] = [$role->name];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $this->applyAuxiliaryHeaderStyle($sheet, 'A1:A1', $this->school);
        $this->applyBodyStyle($sheet, 'A2:A500', $this->school);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->freezePane('A2');
                $sheet->setAutoFilter('A1:A1');
                $sheet->getRowDimension(1)->setRowHeight(22);

                $this->applyZebraRows($sheet, 2, 200, 1, 1);
            },
        ];
    }
}