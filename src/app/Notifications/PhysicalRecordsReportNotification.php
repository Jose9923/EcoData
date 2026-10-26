<?php

namespace App\Notifications;

use App\Exports\PhysicalVariableRecordsExport;
use App\Services\PhysicalRecordReports;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class PhysicalRecordsReportNotification extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public array $filters,
        public string $requestedEmail,
        public ?int $requestedSchoolId,
    ) {
        $this->afterCommit();
    }

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        $notifiable->refresh()->unsetRelation('roles');

        return $notifiable->is_active && $notifiable->hasVerifiedEmail()
            && $notifiable->email === $this->requestedEmail
            && $notifiable->school_id === $this->requestedSchoolId
            && $notifiable->hasAnyRole(['super_admin', 'admin_colegio', 'docente', 'estudiante']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $query = app(PhysicalRecordReports::class)->query($notifiable, $this->filters);
        $count = $query->count();
        $mail = (new MailMessage)
            ->subject('Tu reporte de mediciones EcoData')
            ->greeting('Hola, '.$notifiable->name)
            ->line('Período: '.$this->filters['date_from'].' a '.$this->filters['date_to'].'.')
            ->action('Consultar registros', route('mail.reports.redirect'));
        if ($count === 0 || $count > 2500) {
            return $mail->line('Los datos disponibles cambiaron desde la solicitud. Solicita nuevamente un reporte de hasta 2500 registros.');
        }
        $export = new PhysicalVariableRecordsExport(
            $query->orderBy('recorded_at'),
            $notifiable->hasRole('super_admin') ? null : $notifiable->school,
            $notifiable->name,
            $this->filters['date_from'].' a '.$this->filters['date_to'],
            ! $notifiable->hasRole('estudiante'),
        );

        return $mail->line('Se adjuntan '.$count.' registros visibles para tu cuenta al generar el archivo.')
            ->attachData(Excel::raw($export, ExcelFormat::XLSX), 'mediciones-ecodata.xlsx', [
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
    }
}
