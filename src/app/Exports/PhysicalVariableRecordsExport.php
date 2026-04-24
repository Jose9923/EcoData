<?php

namespace App\Exports;

use App\Models\PhysicalVariableRecord;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PhysicalVariableRecordsExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Builder $query
    ) {}

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
                            $resolved = \Illuminate\Support\Carbon::parse($resolved)->format('Y-m-d');
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
                    'ID' => $record->id,
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
            'ID',
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
}