<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => Str::lower(trim((string) $this->input('email')))]);
    }

    public function rules(): array
    {
        return ['email' => ['required', 'email'], 'password' => ['required', 'string']];
    }

    public function authenticate(): void
    {
        foreach ([$this->throttleKey() => 5, $this->ipThrottleKey() => 30] as $key => $limit) {
            if (RateLimiter::tooManyAttempts($key, $limit)) {
                event(new Lockout($this));
                throw ValidationException::withMessages([
                    'email' => 'Demasiados intentos. Intenta de nuevo en '.RateLimiter::availableIn($key).' segundos.',
                ]);
            }
        }

        if (! Auth::attempt([...$this->validated(), 'is_active' => true], $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey(), 60);
            RateLimiter::hit($this->ipThrottleKey(), 60);
            throw ValidationException::withMessages([
                'email' => 'Las credenciales proporcionadas no son correctas o la cuenta no está habilitada.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    private function throttleKey(): string
    {
        return 'login:'.hash('sha256', $this->input('email').'|'.$this->ip());
    }

    private function ipThrottleKey(): string
    {
        return 'login-ip:'.hash('sha256', (string) $this->ip());
    }
}
