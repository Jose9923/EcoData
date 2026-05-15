<?php

namespace App\Notifications;

use App\Models\FieldDiaryActivity;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewFieldDiaryActivityNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected FieldDiaryActivity $activity
    ) {
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
                'url' => route('estudiante.field-diaries.index'),
            ]);
    }
}