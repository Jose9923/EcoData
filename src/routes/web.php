<?php

use App\Livewire\Admin\Schools\Index as SchoolIndex;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::view('/profile', 'profile')->name('profile');

    Route::get('/admin/schools', SchoolIndex::class)
        ->middleware('role:super_admin')
        ->name('admin.schools.index');
});

Route::post('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
})->middleware('auth')->name('logout');

require __DIR__ . '/auth.php';