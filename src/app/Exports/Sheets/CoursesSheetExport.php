<?php

namespace App\Exports\Sheets;

use App\Exports\Concerns\AppliesSchoolExcelBranding;
use App\Models\Course;
use App\Models\School;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CoursesSheetExport implements FromArray, WithTitle, WithStyles, WithEvents, ShouldAutoSize
{
    use AppliesSchoolExcelBranding;

    public function __construct(
        protected User $authUser,
        protected ?School $school = null
    ) {}

    public function title(): string
    {
        return 'courses';
    }

    public function array(): array
    {
        $rows = [['Colegio', 'Grado', 'Curso', 'Etiqueta', 'Estado']];

        $courses = Course::query()
            ->with(['school', 'grade'])
            ->when(
                ! $this->authUser->hasRole('super_admin'),
                fn ($query) => $query->where('school_id', $this->authUser->school_id)
            )
            ->orderBy('school_id')
            ->orderBy('grade_id')
            ->orderBy('name')
            ->get();

        foreach ($courses as $course) {
            $rows[] = [
                $course->school?->name,
                $course->grade?->label ?: $course->grade?->name,
                $course->name,
                $course->label,
                $course->is_active ? 'Activo' : 'Inactivo',
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $this->applyAuxiliaryHeaderStyle($sheet, 'A1:E1', $this->school);
        $this->applyBodyStyle($sheet, 'A2:E2000', $this->school);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->freezePane('A2');
                $sheet->setAutoFilter('A1:E1');
                $sheet->getRowDimension(1)->setRowHeight(22);

                $this->applyZebraRows($sheet, 2, 2000, 1, 5);
            },
        ];
    }
}