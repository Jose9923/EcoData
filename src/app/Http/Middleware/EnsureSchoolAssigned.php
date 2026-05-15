<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchoolAssigned
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $requiresSchool = $user->hasAnyRole([
            'admin_colegio',
            'docente',
            'estudiante',
        ]);

        if ($requiresSchool && ! $user->school_id) {
            if ($request->expectsJson()) {
                abort(403, 'Tu usuario no tiene un colegio asignado.');
            }

            if (! $request->routeIs('account.school-required', 'logout')) {
                return redirect()->route('account.school-required');
            }
        }

        return $next($request);
    }
}