<?php

namespace App\Exports\Sheets;

use App\Exports\Concerns\AppliesSchoolExcelBranding;
use App\Models\Course;
use App\Models\School;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CoursesSheetExport implements FromArray, WithTitle, WithStyles, ShouldAutoSize
{
    use AppliesSchoolExcelBranding;

    public function __construct(
        protected ?School $school = null
    ) {}

    public function title(): string
    {
        return 'courses';
    }

    public function array(): array
    {
        $rows = [['ID', 'Colegio', 'Grado', 'Curso', 'Etiqueta']];

        foreach (Course::query()->with(['school', 'grade'])->orderBy('school_id')->orderBy('grade_id')->orderBy('name')->get() as $course) {
            $rows[] = [
                $course->id,
                $course->school?->name,
                $course->grade?->label ?: $course->grade?->name,
                $course->name,
                $course->label,
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
}