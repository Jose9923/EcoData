<?php

use App\Livewire\Admin\Schools\Index as SchoolIndex;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Livewire\Admin\Users\Index as UserIndex;
use App\Livewire\Admin\Grades\Index as GradeIndex;
use App\Livewire\Admin\Courses\Index as CourseIndex;
use App\Livewire\Admin\PhysicalVariableCategories\Index as PhysicalVariableCategoryIndex;
use App\Livewire\Admin\PhysicalVariables\Index as PhysicalVariableIndex;
use App\Livewire\Admin\PhysicalVariableRecords\Create as PhysicalVariableRecordCreate;
use App\Livewire\Admin\PhysicalVariableRecords\Index as PhysicalVariableRecordIndex;
Route::view('/', 'welcome');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::view('/profile', 'profile')->name('profile');

    Route::get('/admin/schools', SchoolIndex::class)
        ->middleware('role:super_admin')
        ->name('admin.schools.index');
    
    Route::get('/admin/users', UserIndex::class)
    ->middleware('role:super_admin')
    ->name('admin.users.index');

    Route::get('/admin/grades', GradeIndex::class)
    ->middleware('role:super_admin')
    ->name('admin.grades.index');

    Route::get('/admin/courses', CourseIndex::class)
    ->middleware('role:super_admin')
    ->name('admin.courses.index');

    Route::get('/admin/physical-variable-categories', PhysicalVariableCategoryIndex::class)
    ->middleware(['auth', 'permission:physical_variables.manage'])
    ->name('admin.physical-variable-categories.index');

    Route::get('/admin/physical-variables', PhysicalVariableIndex::class)
    ->middleware(['auth', 'permission:physical_variables.manage'])
    ->name('admin.physical-variables.index');

    Route::get('/admin/physical-variable-records/create', PhysicalVariableRecordCreate::class)
    ->middleware(['auth', 'permission:physical_records.create'])
    ->name('admin.physical-variable-records.create');

    Route::get('/admin/physical-variable-records', PhysicalVariableRecordIndex::class)
    ->middleware(['auth', 'permission:physical_records.view'])
    ->name('admin.physical-variable-records.index');
});

Route::post('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
})->middleware('auth')->name('logout');

require __DIR__ . '/auth.php';