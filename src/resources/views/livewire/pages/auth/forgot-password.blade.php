<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $email = '';

    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $status = Password::sendResetLink(
            $this->only('email')
        );

        if ($status != Password::RESET_LINK_SENT) {
            $this->addError('email', __($status));
            return;
        }

        $this->reset('email');

        session()->flash('status', __($status));
    }
}; ?>

<div class="w-full">
    <div class="mb-6 text-center">
        <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-3xl border border-amber-100 bg-gradient-to-br from-emerald-50 to-sky-50 shadow-sm">
            <span class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">
                Logo
            </span>
        </div>

        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-700">
            EcoData
        </p>
        <h1 class="mt-2 text-3xl font-extrabold text-slate-900">
            Recuperar acceso
        </h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña.
        </p>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-xl shadow-slate-200/50 backdrop-blur">
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form wire:submit="sendPasswordResetLink" class="space-y-5">
            <div>
                <x-input-label for="email" value="Correo electrónico" />
                <x-text-input
                    wire:model="email"
                    id="email"
                    class="mt-1 block w-full"
                    type="email"
                    name="email"
                    required
                    autofocus
                    placeholder="tu-correo@institucion.edu"
                />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div class="pt-2">
                <x-primary-button class="w-full justify-center rounded-2xl bg-emerald-600 py-3 text-sm font-semibold hover:bg-emerald-700 focus:bg-emerald-700 active:bg-emerald-800">
                    Enviar enlace de recuperación
                </x-primary-button>
            </div>
        </form>
    </div>
</div>