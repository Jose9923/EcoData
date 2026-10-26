<?php

namespace App\Notifications;

use App\Models\LaboratoryGuide;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NewLaboratoryGuideNotification extends QueuedMailNotification
{
    public function __construct(
        protected LaboratoryGuide $guide
    ) {
        $this->afterCommit();
        if ($guide->published_at?->isFuture()) {
            $this->delay($guide->published_at);
        }
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        $guide = $this->guide->fresh();
        $notifiable->refresh()->unsetRelation('roles');
        if (! $guide) {
            return false;
        }
        $this->guide = $guide;

        return $notifiable->is_active && $notifiable->hasVerifiedEmail()
            && $notifiable->hasRole('estudiante') && $guide->is_active
            && (! $guide->published_at || $guide->published_at->isPast())
            && (int) $notifiable->school_id === (int) $guide->school_id
            && (! $guide->grade_id || (int) $guide->grade_id === (int) $notifiable->grade_id)
            && (! $guide->course_id || (int) $guide->course_id === (int) $notifiable->course_id);
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
                'url' => route('mail.laboratory-guides.redirect', ['guide' => $this->guide->id]),
            ]);

        if ($this->shouldAttachPdf()) {
            $disk = $this->resolveStorageDisk($this->guide->pdf_path);

            if ($disk) {
                $mail->attach(
                    Storage::disk($disk)->path($this->guide->pdf_path),
                    [
                        'as' => Str::slug($this->guide->title).'.pdf',
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
