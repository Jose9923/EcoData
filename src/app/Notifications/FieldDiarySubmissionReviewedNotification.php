<?php

namespace App\Notifications;

use App\Models\FieldDiarySubmission;
use Illuminate\Notifications\Messages\MailMessage;

class FieldDiarySubmissionReviewedNotification extends QueuedMailNotification
{
    public function __construct(
        protected FieldDiarySubmission $submission
    ) {
        $this->afterCommit();
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        $submission = $this->submission->fresh();
        $notifiable->refresh()->unsetRelation('roles');
        if (! $submission) {
            return false;
        }
        $this->submission = $submission;

        return $notifiable->is_active && $notifiable->hasVerifiedEmail()
            && $notifiable->hasRole('estudiante')
            && (int) $submission->user_id === (int) $notifiable->id
            && (int) $submission->school_id === (int) $notifiable->school_id
            && in_array($submission->status, ['revisado', 'devuelto'], true);
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
