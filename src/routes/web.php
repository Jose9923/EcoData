<?php

use App\Http\Controllers\Admin\SchoolController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\GradeController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\PhysicalVariableCategoryController;
use App\Http\Controllers\Admin\PhysicalVariableController;
use App\Http\Controllers\Admin\PhysicalVariableRecordController;

Route::view('/', 'welcome');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::view('/profile', 'profile')->name('profile');

    Route::prefix('admin')
        ->name('admin.')
        ->middleware('role:super_admin')
        ->group(function () {
            Route::resource('schools', SchoolController::class)->except(['show']);
            Route::resource('users', UserController::class)->except(['show']);
            Route::resource('grades', GradeController::class)->except(['show']);
            Route::resource('courses', CourseController::class)->except(['show']);
            Route::resource('physical-variable-categories', PhysicalVariableCategoryController::class)->except(['show']);
            Route::resource('physical-variables', PhysicalVariableController::class)->except(['show']);
            Route::resource('physical-variable-records', PhysicalVariableRecordController::class)
                ->only(['index', 'create', 'store', 'show', 'edit', 'update']);

            Route::get('physical-variable-records-export', [PhysicalVariableRecordController::class, 'export'])
                ->name('physical-variable-records.export');

            Route::get('physical-variable-records/ajax/grades', [PhysicalVariableRecordController::class, 'getGrades'])
                ->name('physical-variable-records.ajax.grades');

            Route::get('physical-variable-records/ajax/courses', [PhysicalVariableRecordController::class, 'getCourses'])
                ->name('physical-variable-records.ajax.courses');

            Route::get('physical-variable-records/ajax/variables', [PhysicalVariableRecordController::class, 'getVariables'])
                ->name('physical-variable-records.ajax.variables');
            Route::get('users/ajax/grades', [UserController::class, 'getGrades'])
                ->name('users.ajax.grades');

            Route::get('users/ajax/courses', [UserController::class, 'getCourses'])
                ->name('users.ajax.courses');

            Route::get('courses/ajax/grades', [CourseController::class, 'getGrades'])
                ->name('courses.ajax.grades');
        });
    
});

Route::post('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
})->middleware('auth')->name('logout');

require __DIR__ . '/auth.php';