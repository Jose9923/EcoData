<?php

namespace App\Notifications;

use App\Models\FieldDiaryActivity;
use Illuminate\Notifications\Messages\MailMessage;

class NewFieldDiaryActivityNotification extends QueuedMailNotification
{
    public function __construct(
        protected FieldDiaryActivity $activity
    ) {
        $this->afterCommit();
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        $activity = $this->activity->fresh();
        $notifiable->refresh()->unsetRelation('roles');
        if (! $activity) {
            return false;
        }
        $this->activity = $activity;

        return $notifiable->is_active && $notifiable->hasVerifiedEmail()
            && $notifiable->hasRole('estudiante') && $activity->is_active
            && (int) $notifiable->school_id === (int) $activity->school_id
            && (! $activity->grade_id || (int) $activity->grade_id === (int) $notifiable->grade_id)
            && (! $activity->course_id || (int) $activity->course_id === (int) $notifiable->course_id);
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nueva actividad de Diario de Campo')
            ->markdown('emails.field-diaries.activity-created', [
                'user' => $notifiable,
                'activity' => $this->activity,
                'url' => route('mail.field-diaries.redirect', [
                    'activity' => $this->activity->id,
                    'context' => 'activity',
                ]),
            ]);
    }
}
