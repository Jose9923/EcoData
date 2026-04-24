<?php

namespace App\Exports;

use App\Exports\Sheets\CoursesSheetExport;
use App\Exports\Sheets\DocumentTypesSheetExport;
use App\Exports\Sheets\GradesSheetExport;
use App\Exports\Sheets\InstructionsSheetExport;
use App\Exports\Sheets\RolesSheetExport;
use App\Exports\Sheets\SchoolsSheetExport;
use App\Exports\Sheets\UsersTemplateSheetExport;
use App\Models\User;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class UserImportTemplateExport implements WithMultipleSheets
{
    public function __construct(
        protected User $authUser
    ) {}

    public function sheets(): array
    {
        $school = $this->authUser->hasRole('super_admin')
            ? null
            : $this->authUser->school;

        $generatedBy = $this->authUser->name;

        return [
            new UsersTemplateSheetExport($this->authUser, $school, $generatedBy),
            new InstructionsSheetExport($school, $generatedBy),
            new DocumentTypesSheetExport($school),
            new RolesSheetExport($this->authUser, $school),
            new SchoolsSheetExport($this->authUser, $school),
            new GradesSheetExport($this->authUser, $school),
            new CoursesSheetExport($this->authUser, $school),
        ];
    }
}