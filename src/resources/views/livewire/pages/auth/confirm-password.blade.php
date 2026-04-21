<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $password = '';

    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->validate([
            'email' => Auth::user()->email,
            'password' => $this->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
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
            Confirmar contraseña
        </h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            Esta es un área protegida. Confirma tu contraseña para continuar.
        </p>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-xl shadow-slate-200/50 backdrop-blur">
        <form wire:submit="confirmPassword" class="space-y-5">
            <div>
                <x-input-label for="password" value="Contraseña actual" />
                <x-text-input
                    wire:model="password"
                    id="password"
                    class="mt-1 block w-full"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Ingresa tu contraseña"
                />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="pt-2">
                <x-primary-button class="w-full justify-center rounded-2xl bg-emerald-600 py-3 text-sm font-semibold hover:bg-emerald-700 focus:bg-emerald-700 active:bg-emerald-800">
                    Confirmar acceso
                </x-primary-button>
            </div>
        </form>
    </div>
</div>