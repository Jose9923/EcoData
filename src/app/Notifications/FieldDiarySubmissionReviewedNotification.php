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
        $this->submission->loadMissing('activity');

        return (new MailMessage)
            ->subject('Tu Diario de Campo fue revisado')
            ->markdown('emails.field-diaries.submission-reviewed', [
                'user' => $notifiable,
                'submission' => $this->submission,
                'activity' => $this->submission->activity,
                'url' => route('mail.field-diaries.redirect', [
                    'submission' => $this->submission->id,
                    'context' => 'review',
                ]),
            ]);
    }
}
