<?php

use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\GradeController;
use App\Http\Controllers\Admin\LaboratoryGuideController;
use App\Http\Controllers\Admin\PhysicalVariableCategoryController;
use App\Http\Controllers\Admin\PhysicalVariableController;
use App\Http\Controllers\Admin\PhysicalVariableRecordController;
use App\Http\Controllers\Admin\SchoolController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserImportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LaboratoryGuideestudianteController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    /*
    |--------------------------------------------------------------------------
    | Administración total solo super_admin
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')
        ->name('admin.')
        ->middleware('role:super_admin')
        ->group(function () {
            Route::resource('schools', SchoolController::class)->except(['show']);
        });

    /*
    |--------------------------------------------------------------------------
    | Administración académica por colegio
    | super_admin + admin_colegio
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')
        ->name('admin.')
        ->middleware('role:super_admin|admin_colegio')
        ->group(function () {
            Route::resource('users', UserController::class)->except(['show']);
            Route::resource('grades', GradeController::class)->except(['show']);
            Route::resource('courses', CourseController::class)->except(['show']);
            Route::resource('physical-variable-categories', PhysicalVariableCategoryController::class)->except(['show']);
            Route::resource('physical-variables', PhysicalVariableController::class)->except(['show']);

            Route::get('users/import', [UserImportController::class, 'create'])->name('users.import');
            Route::post('users/import', [UserImportController::class, 'store'])->name('users.import.store');
            Route::get('users/import/template', [UserImportController::class, 'template'])->name('users.import.template');

            Route::get('users/ajax/grades', [UserController::class, 'getGrades'])->name('users.ajax.grades');
            Route::get('users/ajax/courses', [UserController::class, 'getCourses'])->name('users.ajax.courses');
            Route::get('courses/ajax/grades', [CourseController::class, 'getGrades'])->name('courses.ajax.grades');
        });

    /*
    |--------------------------------------------------------------------------
    | Registros físicos
    | super_admin + admin_colegio + docente
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')
        ->name('admin.')
        ->middleware('role:super_admin|admin_colegio|docente')
        ->group(function () {
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
        });

    /*
    |--------------------------------------------------------------------------
    | Guías de laboratorio admin
    | super_admin + admin_colegio + docente
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')
        ->name('admin.')
        ->middleware('role:super_admin|admin_colegio|docente')
        ->group(function () {
            Route::get('laboratory-guides/ajax/grades', [LaboratoryGuideController::class, 'getGrades'])
            ->name('laboratory-guides.ajax.grades');

            Route::get('laboratory-guides/ajax/courses', [LaboratoryGuideController::class, 'getCourses'])
                ->name('laboratory-guides.ajax.courses');

            Route::get('laboratory-guides/{laboratory_guide}/download', [LaboratoryGuideController::class, 'download'])
                ->name('laboratory-guides.download');

            Route::resource('laboratory-guides', LaboratoryGuideController::class)->except(['show']);
        });

    /*
    |--------------------------------------------------------------------------
    | Vista estudiante de guías
    | estudiante
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:estudiante')->group(function () {
        Route::get('/estudiante/laboratory-guides', [LaboratoryGuideestudianteController::class, 'index'])
            ->name('estudiante.laboratory-guides.index');

        Route::get('/estudiante/laboratory-guides/{laboratory_guide}/view', [LaboratoryGuideestudianteController::class, 'view'])
            ->name('estudiante.laboratory-guides.view');

        Route::get('/estudiante/laboratory-guides/{laboratory_guide}/download', [LaboratoryGuideestudianteController::class, 'download'])
            ->name('estudiante.laboratory-guides.download');
            });
});

Route::post('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
})->middleware('auth')->name('logout');

require __DIR__ . '/auth.php';