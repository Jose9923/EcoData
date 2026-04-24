<?php

namespace App\Exports\Concerns;

use App\Models\School;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

trait AppliesSchoolExcelBranding
{
    protected function hexToRgb(?string $hex, string $fallback = '1D4ED8'): string
    {
        $hex = ltrim((string) $hex, '#');

        if (! preg_match('/^[A-Fa-f0-9]{6}$/', $hex)) {
            return strtoupper($fallback);
        }

        return strtoupper($hex);
    }

    protected function schoolColors(?School $school): array
    {
        return [
            'primary' => $this->hexToRgb($school?->primary_color, '1D4ED8'),
            'secondary' => $this->hexToRgb($school?->secondary_color, '0F172A'),
            'accent' => $this->hexToRgb($school?->accent_color, '22C55E'),
            'white' => 'FFFFFF',
            'text' => '334155',
            'muted' => '64748B',
            'border' => 'CBD5E1',
            'soft' => 'F8FAFC',
            'sheetHeader' => 'E2E8F0',
        ];
    }

    protected function applyHeaderBlock(
        Worksheet $sheet,
        ?School $school,
        string $title,
        ?string $subtitle = null,
        ?string $generatedBy = null,
        ?string $filtersText = null
    ): void {
        $colors = $this->schoolColors($school);

        $sheet->mergeCells('B2:H2');
        $sheet->mergeCells('B3:H3');
        $sheet->mergeCells('B4:H4');
        $sheet->mergeCells('B5:H5');

        $sheet->setCellValue('B2', $school?->name ?? config('app.name', 'EcoData'));
        $sheet->setCellValue('B3', 'EcoData');
        $sheet->setCellValue('B4', $title);
        $sheet->setCellValue('B5', $subtitle ?: 'Generado el ' . now()->format('Y-m-d H:i'));

        if ($generatedBy) {
            $sheet->setCellValue('J2', 'Generado por');
            $sheet->setCellValue('K2', $generatedBy);
        }

        if ($filtersText) {
            $sheet->setCellValue('J3', 'Filtros');
            $sheet->setCellValue('K3', $filtersText);
        }

        $sheet->getStyle('B2:H2')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => $colors['secondary']],
            ],
        ]);

        $sheet->getStyle('B3:H3')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => $colors['accent']],
            ],
        ]);

        $sheet->getStyle('B4:H4')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 13,
                'color' => ['rgb' => $colors['primary']],
            ],
        ]);

        $sheet->getStyle('B5:H5')->applyFromArray([
            'font' => [
                'size' => 10,
                'color' => ['rgb' => $colors['muted']],
            ],
        ]);

        $sheet->getStyle('J2:J3')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 10,
                'color' => ['rgb' => $colors['secondary']],
            ],
        ]);

        $sheet->getStyle('K2:K3')->applyFromArray([
            'font' => [
                'size' => 10,
                'color' => ['rgb' => $colors['text']],
            ],
        ]);

        $sheet->getRowDimension(2)->setRowHeight(24);
        $sheet->getRowDimension(3)->setRowHeight(20);
        $sheet->getRowDimension(4)->setRowHeight(22);
        $sheet->getRowDimension(5)->setRowHeight(18);
    }

    protected function applyTableHeaderStyle(Worksheet $sheet, string $range, ?School $school): void
    {
        $colors = $this->schoolColors($school);

        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 10,
                'color' => ['rgb' => $colors['white']],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => $colors['primary']],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => $colors['primary']],
                ],
            ],
        ]);
    }

    protected function applyBodyStyle(Worksheet $sheet, string $range, ?School $school): void
    {
        $colors = $this->schoolColors($school);

        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'size' => 10,
                'color' => ['rgb' => $colors['text']],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => $colors['border']],
                ],
            ],
        ]);
    }

    protected function applyAuxiliaryHeaderStyle(Worksheet $sheet, string $range, ?School $school): void
    {
        $colors = $this->schoolColors($school);

        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 10,
                'color' => ['rgb' => $colors['secondary']],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => $colors['sheetHeader']],
            ],
        ]);
    }

    protected function applyExampleRowStyle(Worksheet $sheet, string $range, ?School $school): void
    {
        $colors = $this->schoolColors($school);

        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => false,
                'size' => 10,
                'color' => ['rgb' => $colors['text']],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => 'FEF3C7'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => $colors['border']],
                ],
            ],
        ]);
    }

    protected function applyInfoPanelStyle(Worksheet $sheet, string $range, ?School $school): void
    {
        $colors = $this->schoolColors($school);

        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'size' => 10,
                'color' => ['rgb' => $colors['text']],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => $colors['soft']],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => $colors['border']],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
    }

    protected function applyZebraRows(Worksheet $sheet, int $startRow, int $endRow, int $startColIndex, int $endColIndex): void
    {
        for ($row = $startRow; $row <= $endRow; $row++) {
            if ($row % 2 === 0) {
                $range = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($startColIndex)
                    . $row . ':'
                    . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($endColIndex)
                    . $row;

                $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID);
                $sheet->getStyle($range)->getFill()->getStartColor()->setRGB('F8FAFC');
            }
        }
    }

}