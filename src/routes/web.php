<?php

use App\Http\Controllers\Admin\SchoolController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::view('/profile', 'profile')->name('profile');

    Route::prefix('admin')
        ->name('admin.')
        ->middleware('role:super_admin')
        ->group(function () {
            Route::resource('schools', SchoolController::class)->except(['show']);
        });
});

Route::post('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
})->middleware('auth')->name('logout');

require __DIR__ . '/auth.php';