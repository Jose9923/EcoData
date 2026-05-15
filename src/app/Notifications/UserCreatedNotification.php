<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $temporaryPassword,
        protected ?string $roleName = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Bienvenido a EcoData')
            ->markdown('emails.users.created', [
                'user' => $notifiable,
                'temporaryPassword' => $this->temporaryPassword,
                'roleName' => $this->roleName,
                'loginUrl' => url('/login'),
            ]);
    }
}