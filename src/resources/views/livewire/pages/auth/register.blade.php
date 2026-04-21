<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered($user = User::create($validated)));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="w-full">
    <div class="mb-6 text-center">
        <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-3xl border border-white/15 bg-white/95 shadow-xl shadow-black/10">
            <span class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">
                Logo
            </span>
        </div>

        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200">
            EcoData
        </p>
        <h1 class="mt-2 text-3xl font-extrabold text-white drop-shadow-sm">
            Crear cuenta
        </h1>
        <p class="mt-2 text-sm leading-6 text-slate-100">
            Registra un nuevo acceso para participar en la gestión y análisis de datos ambientales.
        </p>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-xl shadow-slate-200/50 backdrop-blur">
        <form wire:submit="register" class="space-y-5">
            <div>
                <x-input-label for="name" value="Nombre completo" />
                <x-text-input
                    wire:model="name"
                    id="name"
                    class="mt-1 block w-full"
                    type="text"
                    name="name"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Escribe tu nombre completo"
                />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="email" value="Correo electrónico" />
                <x-text-input
                    wire:model="email"
                    id="email"
                    class="mt-1 block w-full"
                    type="email"
                    name="email"
                    required
                    autocomplete="username"
                    placeholder="tu-correo@institucion.edu"
                />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password" value="Contraseña" />
                <x-text-input
                    wire:model="password"
                    id="password"
                    class="mt-1 block w-full"
                    type="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    placeholder="Crea una contraseña segura"
                />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password_confirmation" value="Confirmar contraseña" />
                <x-text-input
                    wire:model="password_confirmation"
                    id="password_confirmation"
                    class="mt-1 block w-full"
                    type="password"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Repite la contraseña"
                />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <div class="flex flex-col gap-3 pt-2 sm:flex-row sm:items-center sm:justify-between">
                <a
                    class="text-sm font-medium text-emerald-700 transition hover:text-emerald-800"
                    href="{{ route('login') }}"
                    wire:navigate
                >
                    ¿Ya tienes cuenta?
                </a>

                <x-primary-button class="justify-center rounded-2xl bg-emerald-600 px-6 py-3 text-sm font-semibold hover:bg-emerald-700 focus:bg-emerald-700 active:bg-emerald-800">
                    Crear cuenta
                </x-primary-button>
            </div>
        </form>
    </div>
</div>