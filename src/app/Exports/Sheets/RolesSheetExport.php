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
use Spatie\Permission\Models\Role;

class RolesSheetExport implements FromArray, WithTitle, WithStyles, WithEvents, ShouldAutoSize
{
    use AppliesSchoolExcelBranding;

    public function __construct(
        protected User $authUser,
        protected ?School $school = null
    ) {}

    public function title(): string
    {
        return 'roles';
    }

    public function array(): array
    {
        $rows = [['Rol', 'Descripción']];

        $roles = Role::query()
            ->when(
                ! $this->authUser->hasRole('super_admin'),
                fn ($query) => $query->where('name', '!=', 'super_admin')
            )
            ->orderBy('name')
            ->get();

        foreach ($roles as $role) {
            $rows[] = [
                $role->name,
                $this->roleDescription($role->name),
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

                $this->applyZebraRows($sheet, 2, 200, 1, 2);
            },
        ];
    }

    private function roleDescription(string $role): string
    {
        return match ($role) {
            'super_admin' => 'Acceso total al sistema',
            'admin_colegio' => 'Administrador del colegio asignado',
            'docente' => 'Docente',
            'estudiante' => 'Estudiante',
            default => 'Rol del sistema',
        };
    }
}