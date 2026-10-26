<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = Str::lower(trim((string) $request->input('email')));
        $key = 'password-reset:'.hash('sha256', $email.'|'.$request->ip());

        try {
            RateLimiter::attempt($key, 1, function () use ($email): void {
                Password::sendResetLink(['email' => $email, 'is_active' => true]);
            }, 60);
        } catch (\Throwable $exception) {
            report($exception);
        }

        return back()->with('success', 'Si el correo corresponde a una cuenta habilitada, recibirás un enlace para establecer tu contraseña.');
    }
}
