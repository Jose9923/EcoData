<?php

namespace App\Exports\Sheets;

use App\Exports\Concerns\AppliesSchoolExcelBranding;
use App\Models\School;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SchoolsSheetExport implements FromArray, WithTitle, WithStyles, WithEvents, ShouldAutoSize
{
    use AppliesSchoolExcelBranding;

    public function __construct(
        protected User $authUser,
        protected ?School $school = null
    ) {}

    public function title(): string
    {
        return 'schools';
    }

    public function array(): array
    {
        $rows = [['Colegio', 'Slug']];

        $schools = School::query()
            ->when(
                ! $this->authUser->hasRole('super_admin'),
                fn ($query) => $query->where('id', $this->authUser->school_id)
            )
            ->orderBy('name')
            ->get();

        foreach ($schools as $school) {
            $rows[] = [
                $school->name,
                $school->slug,
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $this->applyAuxiliaryHeaderStyle($sheet, 'A1:B1', $this->school);
        $this->applyBodyStyle($sheet, 'A2:B500', $this->school);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->freezePane('A2');
                $sheet->setAutoFilter('A1:B1');
                $sheet->getRowDimension(1)->setRowHeight(22);

                $this->applyZebraRows($sheet, 2, 500, 1, 2);
            },
        ];
    }
}