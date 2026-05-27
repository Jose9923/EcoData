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

class FieldDiarySubmissionsExport implements
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
        return 'diario_de_campo';
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
                'activity.school',
                'activity.grade',
                'activity.course',
                'activity.weatherStation',
                'activity.questions',
                'student',
                'school',
                'grade',
                'course',
                'reviewer',
                'answers.question',
            ])
            ->get()
            ->flatMap(function ($submission) {
                $activity = $submission->activity;
                $student = $submission->student;

                $answers = $submission->answers
                    ->filter(fn ($answer) => (int) $answer->question?->field_diary_activity_id === (int) $submission->field_diary_activity_id);

                return $answers
                    ->sortBy(fn ($answer) => $answer->question?->order ?? 999)
                    ->map(function ($answer) use ($submission, $activity, $student) {
                        $question = $answer->question;

                        $answerText = $answer->answer_text;

                        if ($question?->question_type === 'checkbox' && $answerText) {
                            $decoded = json_decode($answerText, true);
                            $answerText = is_array($decoded) ? implode(', ', $decoded) : $answerText;
                        }

                        $fileUrl = $answer->answer_file_path
                            ? route('field-diaries.answers.file', $answer)
                            : null;

                        return [
                            'Fecha de envío' => $submission->submitted_at?->format('Y-m-d H:i'),
                            'Colegio' => $submission->school?->name,
                            'Grado' => $submission->grade?->label ?: $submission->grade?->name,
                            'Curso' => $submission->course?->label ?: $submission->course?->name,
                            'Actividad' => $activity?->title,
                            'Tipo de actividad' => $activity?->entry_type_label,
                            'Estación meteorológica' => $activity?->weatherStation?->name ?: 'Sin estación asociada',
                            'Código estación' => $activity?->weatherStation?->code ?: '—',
                            'Estudiante' => $student?->name,
                            'Tipo documento' => $student?->document_type,
                            'Número documento' => $student?->document_number,
                            'Correo estudiante' => $student?->email,
                            'Estado de entrega' => $submission->status_label,
                            'Nota' => $submission->score,
                            'Docente revisor' => $submission->reviewer?->name,
                            'Fecha de revisión' => $submission->reviewed_at?->format('Y-m-d H:i'),
                            'Retroalimentación' => $submission->teacher_feedback,
                            'Orden pregunta' => $question?->order,
                            'Tipo pregunta' => $question?->question_type_label,
                            'Pregunta' => $question?->question_text,
                            'Respuesta' => $answerText,
                            'Archivo evidencia' => $fileUrl,
                        ];
                    });
            })
            ->values();
    }

    public function headings(): array
    {
        return [
            'Fecha de envío',
            'Colegio',
            'Grado',
            'Curso',
            'Actividad',
            'Tipo de actividad',
            'Estación meteorológica',
            'Código estación',
            'Estudiante',
            'Tipo documento',
            'Número documento',
            'Correo estudiante',
            'Estado de entrega',
            'Nota',
            'Docente revisor',
            'Fecha de revisión',
            'Retroalimentación',
            'Orden pregunta',
            'Tipo pregunta',
            'Pregunta',
            'Respuesta',
            'Archivo evidencia',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $this->applyHeaderBlock(
            $sheet,
            $this->school,
            'Exportación de Diario de Campo EcoData',
            'Consulta consolidada de actividades, preguntas, respuestas, evidencias y revisión docente.',
            $this->generatedBy,
            $this->filtersText
        );

        $this->applyTableHeaderStyle($sheet, 'B8:W8', $this->school);
        $this->applyBodyStyle($sheet, 'B9:W5000', $this->school);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->freezePane('B9');
                $sheet->setAutoFilter('B8:W8');

                foreach (range('B', 'W') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                $sheet->getColumnDimension('F')->setWidth(34);
                $sheet->getColumnDimension('I')->setWidth(28);
                $sheet->getColumnDimension('L')->setWidth(32);
                $sheet->getColumnDimension('Q')->setWidth(45);
                $sheet->getColumnDimension('T')->setWidth(55);
                $sheet->getColumnDimension('U')->setWidth(60);
                $sheet->getColumnDimension('V')->setWidth(60);
                $sheet->getColumnDimension('W')->setWidth(55);

                $sheet->getRowDimension(8)->setRowHeight(28);

                $sheet->setCellValue('I4', 'Modo de lectura');
                $sheet->setCellValue('J4', 'Cada fila corresponde a una respuesta individual de un estudiante. Una misma entrega puede aparecer en varias filas si la actividad tiene varias preguntas.');

                $this->applyInfoPanelStyle($sheet, 'I2:J4', $this->school);
                $this->applyZebraRows($sheet, 9, 500, 2, 23);
            },
        ];
    }
}
