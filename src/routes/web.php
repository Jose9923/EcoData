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
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\LaboratoryGuideController;
use App\Http\Controllers\LaboratoryGuideStudentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\UserImportController;

Route::view('/', 'welcome');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
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
            Route::get('users/import', [UserImportController::class, 'create'])->name('users.import');
            Route::post('users/import', [UserImportController::class, 'store'])->name('users.import.store');
            Route::get('users/import/template', [UserImportController::class, 'template'])->name('users.import.template');
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
    Route::prefix('admin')
    ->name('admin.')
    ->middleware('role:super_admin|teacher')
    ->group(function () {
        Route::resource('laboratory-guides', LaboratoryGuideController::class)->except(['show']);
    });

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('/student/laboratory-guides', [LaboratoryGuideStudentController::class, 'index'])
            ->name('student.laboratory-guides.index');

        Route::get('/student/laboratory-guides/{laboratory_guide}/download', [LaboratoryGuideStudentController::class, 'download'])
            ->name('student.laboratory-guides.download');
    });
});

Route::post('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
})->middleware('auth')->name('logout');

require __DIR__ . '/auth.php';