<?php

namespace App\Exports\Sheets;

use App\Exports\Concerns\AppliesSchoolExcelBranding;
use App\Models\School;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersTemplateSheetExport implements FromArray, WithTitle, WithStyles, WithEvents, WithDrawings, ShouldAutoSize, WithCustomStartCell
{
    use AppliesSchoolExcelBranding;

    public function __construct(
        protected User $authUser,
        protected ?School $school = null,
        protected ?string $generatedBy = null
    ) {}

    public function title(): string
    {
        return 'usuarios';
    }

    public function startCell(): string
    {
        return 'B8';
    }

    public function array(): array
    {
        $exampleSchool = $this->authUser->hasRole('super_admin')
            ? 'Nombre exacto del colegio'
            : ($this->authUser->school?->name ?? 'Colegio asignado');

        return [
            ['name', 'email', 'document_type', 'document_number', 'role', 'school', 'grade', 'course', 'password', 'is_active'],
            [
                'Juan Pérez',
                'juan.perez@ecodata.test',
                'TI',
                '123456789',
                'estudiante',
                $this->school?->name ?? 'Nombre exacto del colegio',
                '6',
                '6-1',
                'Cambio123*',
                '1',
            ],
        ];
    }

    public function drawings()
    {
        if (! $this->school?->shield_path) {
            return [];
        }

        $fullPath = storage_path('app/public/' . $this->school->shield_path);

        if (! file_exists($fullPath)) {
            return [];
        }

        $drawing = new Drawing();
        $drawing->setName('Escudo');
        $drawing->setDescription('Escudo del colegio');
        $drawing->setPath($fullPath);
        $drawing->setHeight(65);
        $drawing->setCoordinates('B2');

        return [$drawing];
    }

    public function styles(Worksheet $sheet)
    {
        $this->applyHeaderBlock(
            $sheet,
            $this->school,
            'Plantilla de cargue masivo de usuarios',
            'Diligencia la hoja "usuarios" y usa las hojas auxiliares como referencia.',
            $this->generatedBy
        );

        $this->applyTableHeaderStyle($sheet, 'B8:K8', $this->school);
        $this->applyBodyStyle($sheet, 'B9:K5000', $this->school);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->freezePane('B9');
                $sheet->setAutoFilter('B8:K8');

                foreach (range('B', 'K') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                $sheet->getRowDimension(8)->setRowHeight(24);
                $sheet->getRowDimension(9)->setRowHeight(22);

                $sheet->setCellValue('J4', 'Modo de uso');
                $sheet->setCellValue('K4', 'Completa desde la fila 10 usando las hojas auxiliares. No modifiques los encabezados.');

                $this->applyInfoPanelStyle($sheet, 'J2:K4', $this->school);
                $this->applyExampleRowStyle($sheet, 'B9:K9', $this->school);
                $this->applyZebraRows($sheet, 10, 200, 2, 11);
            },
        ];
    }
}