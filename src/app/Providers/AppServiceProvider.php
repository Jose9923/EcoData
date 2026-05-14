<?php

namespace App\Providers;

use App\Repositories\Contracts\SchoolRepositoryInterface;
use App\Repositories\Eloquent\SchoolRepository;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use App\Models\EnvironmentalEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SchoolRepositoryInterface::class, SchoolRepository::class);
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();
        View::composer('layouts.app', function ($view) {
            $user = Auth::user();

            $todayEnvironmentalEvent = null;

            if ($user) {
                $today = now()->toDateString();

                $todayEnvironmentalEvent = EnvironmentalEvent::query()
                    ->where('is_active', true)
                    ->whereDate('starts_at', '<=', $today)
                    ->whereDate('ends_at', '>=', $today)
                    ->when(! $user->hasRole('super_admin'), function ($query) use ($user) {
                        $query->where('school_id', $user->school_id);
                    })
                    ->whereDoesntHave('acknowledgements', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    })
                    ->with('school')
                    ->orderBy('starts_at')
                    ->first();
            }

            $view->with('todayEnvironmentalEvent', $todayEnvironmentalEvent);
        });
    }
}