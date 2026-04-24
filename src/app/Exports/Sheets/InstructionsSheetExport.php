<?php

namespace App\Exports\Sheets;

use App\Exports\Concerns\AppliesSchoolExcelBranding;
use App\Models\School;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InstructionsSheetExport implements FromArray, WithTitle, WithStyles, WithDrawings, ShouldAutoSize
{
    use AppliesSchoolExcelBranding;

    public function __construct(
        protected ?School $school = null,
        protected ?string $generatedBy = null
    ) {}

    public function title(): string
    {
        return 'instrucciones';
    }

    public function array(): array
    {
        return [
            ['Campo', 'Regla', 'Ejemplo', 'Observación'],
            ['name', 'Obligatorio', 'Juan Pérez', 'Nombre completo del usuario'],
            ['email', 'Obligatorio y válido', 'juan@ecodata.test', 'Debe ser único'],
            ['document_type', 'Usar catálogo', 'TI', 'Consulta la hoja tipos_documento'],
            ['document_number', 'Obligatorio', '123456789', 'Debe ser único'],
            ['role', 'Usar catálogo', 'student', 'Consulta la hoja roles. No escribas roles que no aparezcan allí.'],
            ['school', 'Usar catálogo', $this->school?->name ?? 'Nombre exacto del colegio', 'Consulta la hoja schools. Si eres admin de colegio, usa únicamente tu colegio.'],
            ['grade', 'Opcional según contexto', '6', 'Consulta la hoja grades'],
            ['course', 'Opcional según contexto', '6-1', 'Consulta la hoja courses'],
            ['password', 'Opcional', 'Cambio123*', 'Si no se informa, el sistema asignará una contraseña temporal'],
            ['is_active', '1 o 0', '1', '1 = activo, 0 = inactivo'],
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
        $drawing->setPath($fullPath);
        $drawing->setHeight(65);
        $drawing->setCoordinates('A2');

        return [$drawing];
    }

    public function styles(Worksheet $sheet)
    {
        $this->applyHeaderBlock(
            $sheet,
            $this->school,
            'Instrucciones de diligenciamiento',
            'Lee esta hoja antes de completar la plantilla.',
            $this->generatedBy
        );

        $this->applyAuxiliaryHeaderStyle($sheet, 'A8:D8', $this->school);
        $this->applyBodyStyle($sheet, 'A9:D100', $this->school);

        return [];
    }
}