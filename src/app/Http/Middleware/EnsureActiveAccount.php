<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! User::whereKey($user->getAuthIdentifier())->where('is_active', true)->exists()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Tu cuenta no está habilitada. Contacta al administrador.'], 403);
            }

            return redirect()->route('login')->withErrors([
                'email' => 'Tu cuenta no está habilitada. Contacta al administrador.',
            ]);
        }

        return $next($request);
    }
}
