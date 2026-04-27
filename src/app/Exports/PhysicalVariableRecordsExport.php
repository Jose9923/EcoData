<?php

namespace App\Exports;

use App\Exports\Concerns\AppliesSchoolExcelBranding;
use App\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PhysicalVariableRecordsExport implements
    FromCollection,
    WithHeadings,
    WithTitle,
    WithStyles,
    WithEvents,
    WithDrawings,
    ShouldAutoSize,
    WithCustomStartCell
{
    use AppliesSchoolExcelBranding;

    public function __construct(
        protected Builder $query,
        protected ?School $school = null,
        protected ?string $generatedBy = null,
        protected ?string $filtersText = null
    ) {}

    public function title(): string
    {
        return 'registros_variables';
    }

    public function startCell(): string
    {
        return 'B8';
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

    public function collection()
    {
        return $this->query
            ->with([
                'school',
                'grade',
                'course',
                'user',
                'values.variable.category',
            ])
            ->get()
            ->map(function ($record) {
                $grade = $record->grade?->label ?: $record->grade?->name;
                $course = $record->course?->label ?: $record->course?->name;

                $values = $record->values
                    ->map(function ($value) {
                        $variable = $value->variable;
                        $resolved = $value->resolved_value;

                        if ($variable?->data_type === 'boolean') {
                            $resolved = $resolved === true ? 'Sí' : ($resolved === false ? 'No' : '—');
                        } elseif ($variable?->data_type === 'date' && $resolved) {
                            $resolved = Carbon::parse($resolved)->format('Y-m-d');
                        } elseif (in_array($variable?->data_type, ['integer', 'decimal'], true) && $resolved !== null) {
                            $resolved = number_format((float) $resolved, $variable->decimals ?? 0, '.', '');
                        }

                        if ($resolved !== null && $resolved !== '—' && $variable?->unit) {
                            $resolved .= ' ' . $variable->unit;
                        }

                        return ($variable?->category?->name ?: 'Sin categoría')
                            . ' / '
                            . ($variable?->name ?: 'Variable')
                            . ': '
                            . ($resolved ?? '—');
                    })
                    ->implode(' | ');

                return [
                    'Fecha' => optional($record->recorded_at)->format('Y-m-d H:i'),
                    'Colegio' => $record->school?->name,
                    'Grado' => $grade,
                    'Curso' => $course,
                    'Registrado por' => $record->user?->name,
                    'Tipo identificación usuario' => $record->user?->document_type,
                    'Número identificación usuario' => $record->user?->document_number,
                    'Correo usuario' => $record->user?->email,
                    'Observaciones' => $record->observations,
                    'Variables capturadas' => $values,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Colegio',
            'Grado',
            'Curso',
            'Registrado por',
            'Tipo identificación usuario',
            'Número identificación usuario',
            'Correo usuario',
            'Observaciones',
            'Variables capturadas',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $this->applyHeaderBlock(
            $sheet,
            $this->school,
            'Exportación de registros de variables físicas',
            'Consulta consolidada de registros capturados en EcoData.',
            $this->generatedBy,
            $this->filtersText
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

                $sheet->getColumnDimension('I')->setWidth(35);
                $sheet->getColumnDimension('J')->setWidth(55);
                $sheet->getColumnDimension('K')->setWidth(70);

                $sheet->getRowDimension(8)->setRowHeight(28);

                $sheet->setCellValue('I4', 'Modo de lectura');
                $sheet->setCellValue('J4', 'Cada fila corresponde a un registro físico capturado. La columna "Variables capturadas" resume las variables asociadas al registro.');

                $this->applyInfoPanelStyle($sheet, 'I2:J4', $this->school);
                $this->applyZebraRows($sheet, 9, 500, 2, 12);
            },
        ];
    }
}