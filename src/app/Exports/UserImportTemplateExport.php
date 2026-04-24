<?php

namespace App\Exports;

use App\Exports\Sheets\CoursesSheetExport;
use App\Exports\Sheets\DocumentTypesSheetExport;
use App\Exports\Sheets\GradesSheetExport;
use App\Exports\Sheets\InstructionsSheetExport;
use App\Exports\Sheets\RolesSheetExport;
use App\Exports\Sheets\SchoolsSheetExport;
use App\Exports\Sheets\UsersTemplateSheetExport;
use App\Models\School;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class UserImportTemplateExport implements WithMultipleSheets
{
    public function __construct(
        protected ?School $school = null
    ) {}

    public function sheets(): array
    {
        $generatedBy = Auth::user()?->name;

        return [
            new UsersTemplateSheetExport($this->school, $generatedBy),
            new InstructionsSheetExport($this->school, $generatedBy),
            new DocumentTypesSheetExport($this->school),
            new RolesSheetExport($this->school),
            new SchoolsSheetExport($this->school),
            new GradesSheetExport($this->school),
            new CoursesSheetExport($this->school),
        ];
    }
}