<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

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
            Iniciar sesión
        </h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            Accede a la plataforma para consultar, registrar y analizar datos ambientales escolares.
        </p>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-xl shadow-slate-200/50 backdrop-blur">
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form wire:submit="login" class="space-y-5">
            <div>
                <x-input-label for="email" value="Correo electrónico" />
                <x-text-input
                    wire:model="form.email"
                    id="email"
                    class="mt-1 block w-full"
                    type="email"
                    name="email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="tu-correo@institucion.edu"
                />
                <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password" value="Contraseña" />
                <x-text-input
                    wire:model="form.password"
                    id="password"
                    class="mt-1 block w-full"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Ingresa tu contraseña"
                />
                <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
            </div>

            <div class="flex items-center justify-between gap-4">
                <label for="remember" class="inline-flex items-center gap-2">
                    <input
                        wire:model="form.remember"
                        id="remember"
                        type="checkbox"
                        class="rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500"
                        name="remember"
                    >
                    <span class="text-sm text-slate-600">Recordar sesión</span>
                </label>

                @if (Route::has('password.request'))
                    <a
                        class="text-sm font-medium text-emerald-700 transition hover:text-emerald-800"
                        href="{{ route('password.request') }}"
                        wire:navigate
                    >
                        ¿Olvidaste tu contraseña?
                    </a>
                @endif
            </div>

            <div class="pt-2">
                <x-primary-button class="w-full justify-center rounded-2xl bg-emerald-600 py-3 text-sm font-semibold hover:bg-emerald-700 focus:bg-emerald-700 active:bg-emerald-800">
                    Entrar a EcoData
                </x-primary-button>
            </div>
        </form>
    </div>
</div>