<?php

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    #[Locked]
    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = request()->string('email');
    }

    public function resetPassword(): void
    {
        $this->validate([
            'token' => ['required'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $this->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) {
                $user->forceFill([
                    'password' => Hash::make($this->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status != Password::PASSWORD_RESET) {
            $this->addError('email', __($status));
            return;
        }

        Session::flash('status', __($status));

        $this->redirectRoute('login', navigate: true);
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
            Restablecer contraseña
        </h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            Define una nueva contraseña para volver a ingresar a la plataforma.
        </p>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-xl shadow-slate-200/50 backdrop-blur">
        <form wire:submit="resetPassword" class="space-y-5">
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
                    autocomplete="username"
                />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password" value="Nueva contraseña" />
                <x-text-input
                    wire:model="password"
                    id="password"
                    class="mt-1 block w-full"
                    type="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    placeholder="Escribe tu nueva contraseña"
                />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password_confirmation" value="Confirmar nueva contraseña" />
                <x-text-input
                    wire:model="password_confirmation"
                    id="password_confirmation"
                    class="mt-1 block w-full"
                    type="password"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Repite la nueva contraseña"
                />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <div class="pt-2">
                <x-primary-button class="w-full justify-center rounded-2xl bg-emerald-600 py-3 text-sm font-semibold hover:bg-emerald-700 focus:bg-emerald-700 active:bg-emerald-800">
                    Guardar nueva contraseña
                </x-primary-button>
            </div>
        </form>
    </div>
</div>