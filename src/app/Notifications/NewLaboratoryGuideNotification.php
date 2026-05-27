<?php

namespace App\Notifications;

use App\Models\LaboratoryGuide;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        $mail = (new MailMessage)
            ->subject('Nueva guía de laboratorio disponible')
            ->markdown('emails.laboratory-guides.created', [
                'user' => $notifiable,
                'guide' => $this->guide,
                'url' => route('estudiante.laboratory-guides.index'),
            ]);

        if ($this->shouldAttachPdf()) {
            $disk = $this->resolveStorageDisk($this->guide->pdf_path);

            if ($disk) {
                $mail->attach(
                    Storage::disk($disk)->path($this->guide->pdf_path),
                    [
                        'as' => Str::slug($this->guide->title) . '.pdf',
                        'mime' => 'application/pdf',
                    ]
                );
            }
        }

        return $mail;
    }

    private function shouldAttachPdf(): bool
    {
        if (! $this->guide->pdf_path) {
            return false;
        }

        $disk = $this->resolveStorageDisk($this->guide->pdf_path);

        if (! $disk) {
            return false;
        }

        $sizeInBytes = Storage::disk($disk)->size($this->guide->pdf_path);

        /*
        |--------------------------------------------------------------------------
        | Límite recomendado para adjuntar
        |--------------------------------------------------------------------------
        | 10 MB = 10 * 1024 * 1024 bytes
        | Puedes subirlo si quieres, pero para correos masivos no conviene.
        */
        return $sizeInBytes <= 10 * 1024 * 1024;
    }

    private function resolveStorageDisk(string $path): ?string
    {
        if (Storage::disk('local')->exists($path)) {
            return 'local';
        }

        if (Storage::disk('public')->exists($path)) {
            return 'public';
        }

        return null;
    }
}
