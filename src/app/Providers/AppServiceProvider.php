<?php

namespace App\Providers;

use App\Repositories\Contracts\SchoolRepositoryInterface;
use App\Repositories\Eloquent\SchoolRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SchoolRepositoryInterface::class, SchoolRepository::class);
    }

    public function boot(): void
    {
        //
    }
}