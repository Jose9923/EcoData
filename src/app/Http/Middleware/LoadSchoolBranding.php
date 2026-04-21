<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class LoadSchoolBranding
{
    public function handle(Request $request, Closure $next): Response
    {
        $school = auth()->check() ? auth()->user()->school : null;

        View::share('currentSchool', $school);

        return $next($request);
    }
}