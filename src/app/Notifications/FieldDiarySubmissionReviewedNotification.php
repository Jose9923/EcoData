<?php

namespace App\Notifications;

use App\Models\FieldDiarySubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FieldDiarySubmissionReviewedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected FieldDiarySubmission $submission
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu Diario de Campo fue revisado')
            ->markdown('emails.field-diaries.submission-reviewed', [
                'user' => $notifiable,
                'submission' => $this->submission,
                'activity' => $this->submission->activity,
                'url' => route('estudiante.field-diaries.index'),
            ]);
    }
}