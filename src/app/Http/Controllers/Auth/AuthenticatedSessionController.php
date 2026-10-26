<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        if (! $request->user()->hasVerifiedEmail()) {
            try {
                RateLimiter::attempt('verification-login:'.$request->user()->id, 1, function () use ($request): void {
                    $request->user()->sendEmailVerificationNotification();
                }, 60);
            } catch (\Throwable $exception) {
                report($exception);
            }

            return redirect()->route('verification.notice');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
