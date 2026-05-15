<?php

namespace App\Notifications;

use App\Models\LaboratoryGuide;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewLaboratoryGuideNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected LaboratoryGuide $guide
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nueva guía de laboratorio disponible')
            ->markdown('emails.laboratory-guides.created', [
                'user' => $notifiable,
                'guide' => $this->guide,
                'url' => route('estudiante.laboratory-guides.index'),
            ]);
    }
}