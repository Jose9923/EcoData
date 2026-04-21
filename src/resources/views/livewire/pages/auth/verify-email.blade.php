<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div class="w-full">
    <div class="mb-6 text-center">
        <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-3xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-sky-50 shadow-sm">
            <span class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">
                Logo
            </span>
        </div>

        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-700">
            EcoData
        </p>
        <h1 class="mt-2 text-3xl font-extrabold text-slate-900">
            Verifica tu correo
        </h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            Antes de continuar, confirma tu dirección de correo usando el enlace que enviamos a tu bandeja de entrada.
        </p>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-xl shadow-slate-200/50 backdrop-blur">
        @if (session('status') == 'verification-link-sent')
            <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                Hemos enviado un nuevo enlace de verificación al correo registrado.
            </div>
        @endif

        <div class="flex flex-col gap-3 pt-2 sm:flex-row sm:items-center sm:justify-between">
            <x-primary-button
                wire:click="sendVerification"
                class="justify-center rounded-2xl bg-emerald-600 px-6 py-3 text-sm font-semibold hover:bg-emerald-700 focus:bg-emerald-700 active:bg-emerald-800"
            >
                Reenviar correo de verificación
            </x-primary-button>

            <button
                wire:click="logout"
                type="button"
                class="text-sm font-medium text-slate-600 transition hover:text-slate-900"
            >
                Cerrar sesión
            </button>
        </div>
    </div>
</div>