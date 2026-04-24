<?php

namespace App\Exports\Sheets;

use App\Exports\Concerns\AppliesSchoolExcelBranding;
use App\Models\Grade;
use App\Models\School;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GradesSheetExport implements FromArray, WithTitle, WithStyles, WithEvents, ShouldAutoSize
{
    use AppliesSchoolExcelBranding;

    public function __construct(
        protected User $authUser,
        protected ?School $school = null
    ) {}

    public function title(): string
    {
        return 'grades';
    }

    public function array(): array
    {
        $rows = [['Colegio', 'Grado', 'Etiqueta', 'Estado']];

        $grades = Grade::query()
            ->with('school')
            ->when(
                ! $this->authUser->hasRole('super_admin'),
                fn ($query) => $query->where('school_id', $this->authUser->school_id)
            )
            ->orderBy('school_id')
            ->orderBy('name')
            ->get();

        foreach ($grades as $grade) {
            $rows[] = [
                $grade->school?->name,
                $grade->name,
                $grade->label,
                $grade->is_active ? 'Activo' : 'Inactivo',
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

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->freezePane('A2');
                $sheet->setAutoFilter('A1:D1');
                $sheet->getRowDimension(1)->setRowHeight(22);

                $this->applyZebraRows($sheet, 2, 1000, 1, 4);
            },
        ];
    }
}