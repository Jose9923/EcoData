<?php

use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\EnvironmentalEventController;
use App\Http\Controllers\Admin\FieldDiaryActivityController;
use App\Http\Controllers\Admin\FieldDiarySubmissionController as AdminFieldDiarySubmissionController;
use App\Http\Controllers\Admin\GradeController;
use App\Http\Controllers\Admin\LaboratoryGuideController;
use App\Http\Controllers\Admin\PhysicalVariableCategoryController;
use App\Http\Controllers\Admin\PhysicalVariableController;
use App\Http\Controllers\Admin\PhysicalVariableRecordController;
use App\Http\Controllers\Admin\PhysicalVariableRecordImportController;
use App\Http\Controllers\Admin\ReportMailController;
use App\Http\Controllers\Admin\SchoolController;
use App\Http\Controllers\Admin\SensorController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserImportController;
use App\Http\Controllers\Admin\WeatherStationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnvironmentalEventAcknowledgementController;
use App\Http\Controllers\EnvironmentalEventImageController;
use App\Http\Controllers\EnvironmentalEventPublicController;
use App\Http\Controllers\FieldDiaryAnswerFileController;
use App\Http\Controllers\FieldDiarySubmissionController;
use App\Http\Controllers\LaboratoryGuideStudentController;
use App\Http\Controllers\MailRedirectController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\EnsureSchoolAssigned;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Ruta de bloqueo para usuarios sin colegio
    |--------------------------------------------------------------------------
    */
    Route::get('/cuenta/colegio-requerido', function () {
        return view('account.school-required');
    })->name('account.school-required');

    /*
    |--------------------------------------------------------------------------
    | Perfil
    | Se deja fuera de EnsureSchoolAssigned para que el usuario pueda consultar
    | su cuenta aunque esté bloqueado por falta de colegio.
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    /*
    |--------------------------------------------------------------------------
    | Rutas internas protegidas por colegio asignado
    |--------------------------------------------------------------------------
    */
    Route::middleware(EnsureSchoolAssigned::class)->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::get('/field-diaries/answers/{field_diary_answer}/file', [FieldDiaryAnswerFileController::class, 'show'])
            ->name('field-diaries.answers.file');

        Route::get('/environmental-events/{environmental_event}/image', [EnvironmentalEventImageController::class, 'show'])
            ->name('environmental-events.image');

        /*
        |--------------------------------------------------------------------------
        | Redirecciones seguras desde correos
        |--------------------------------------------------------------------------
        */
        Route::prefix('mail')
            ->name('mail.')
            ->group(function () {
                Route::get('laboratory-guides/redirect', [MailRedirectController::class, 'laboratoryGuides'])
                    ->name('laboratory-guides.redirect');

                Route::get('field-diaries/redirect', [MailRedirectController::class, 'fieldDiaries'])
                    ->name('field-diaries.redirect');

                Route::get('environmental-events/redirect', [MailRedirectController::class, 'environmentalEvents'])
                    ->name('environmental-events.redirect');

                Route::get('reports/redirect', [MailRedirectController::class, 'reports'])
                    ->name('reports.redirect');
            });

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

                Route::resource('weather-stations', WeatherStationController::class);

                Route::get('weather-stations/ajax/responsibles', [WeatherStationController::class, 'getResponsibles'])
                    ->name('weather-stations.ajax.responsibles');

                Route::resource('weather-stations', WeatherStationController::class);
                Route::resource('sensors', SensorController::class);

                Route::get('users/import', [UserImportController::class, 'create'])
                    ->name('users.import');

                Route::post('users/import', [UserImportController::class, 'store'])
                    ->name('users.import.store');

                Route::get('users/import/template', [UserImportController::class, 'template'])
                    ->name('users.import.template');

                Route::get('users/ajax/grades', [UserController::class, 'getGrades'])
                    ->name('users.ajax.grades');

                Route::get('users/ajax/courses', [UserController::class, 'getCourses'])
                    ->name('users.ajax.courses');

                Route::get('courses/ajax/grades', [CourseController::class, 'getGrades'])
                    ->name('courses.ajax.grades');
            });

        /*
        |--------------------------------------------------------------------------
        | Registros físicos
        | super_admin + admin_colegio + docente + estudiante
        |--------------------------------------------------------------------------
        */
        Route::prefix('admin')
            ->name('admin.')
            ->middleware('role:super_admin|admin_colegio|docente|estudiante')
            ->group(function () {

                Route::get('physical-variable-records/{physical_variable_record}/edit', [PhysicalVariableRecordController::class, 'edit'])
                    ->name('physical-variable-records.edit');

                Route::put('physical-variable-records/{physical_variable_record}', [PhysicalVariableRecordController::class, 'update'])
                    ->name('physical-variable-records.update');

                Route::patch('physical-variable-records/{physical_variable_record}', [PhysicalVariableRecordController::class, 'update'])
                    ->name('physical-variable-records.patch');
                
                Route::get('physical-variable-records', [PhysicalVariableRecordController::class, 'index'])
                    ->name('physical-variable-records.index');

                Route::get('physical-variable-records/create', [PhysicalVariableRecordController::class, 'create'])
                    ->name('physical-variable-records.create');

                Route::post('physical-variable-records', [PhysicalVariableRecordController::class, 'store'])
                    ->name('physical-variable-records.store');

                Route::get('physical-variable-records/ajax/grades', [PhysicalVariableRecordController::class, 'getGrades'])
                    ->name('physical-variable-records.ajax.grades');

                Route::get('physical-variable-records/ajax/courses', [PhysicalVariableRecordController::class, 'getCourses'])
                    ->name('physical-variable-records.ajax.courses');

                Route::get('physical-variable-records/ajax/variables', [PhysicalVariableRecordController::class, 'getVariables'])
                    ->name('physical-variable-records.ajax.variables');

                Route::get('physical-variable-records/{physical_variable_record}', [PhysicalVariableRecordController::class, 'show'])
                    ->name('physical-variable-records.show');
                
                Route::get('physical-variable-records-export', [PhysicalVariableRecordController::class, 'export'])
                    ->name('physical-variable-records.export');

            });

        /*
        |--------------------------------------------------------------------------
        | Administración de registros físicos
        | super_admin + admin_colegio + docente
        |--------------------------------------------------------------------------
        */
        Route::prefix('admin')
            ->name('admin.')
            ->middleware('role:super_admin|admin_colegio|docente')
            ->group(function () {

                Route::get('physical-variable-record-imports/create', [PhysicalVariableRecordImportController::class, 'create'])
                    ->name('physical-variable-record-imports.create');

                Route::resource('environmental-events', EnvironmentalEventController::class);

                Route::get('laboratory-guides/ajax/grades', [LaboratoryGuideController::class, 'getGrades'])
                    ->name('laboratory-guides.ajax.grades');

                Route::get('laboratory-guides/ajax/courses', [LaboratoryGuideController::class, 'getCourses'])
                    ->name('laboratory-guides.ajax.courses');

                Route::get('laboratory-guides/{laboratory_guide}/download', [LaboratoryGuideController::class, 'download'])
                    ->name('laboratory-guides.download');

                Route::resource('laboratory-guides', LaboratoryGuideController::class)->except(['show']);

                Route::get('field-diary-activities/ajax/grades', [FieldDiaryActivityController::class, 'getGrades'])
                    ->name('field-diary-activities.ajax.grades');

                Route::get('field-diary-activities/ajax/courses', [FieldDiaryActivityController::class, 'getCourses'])
                    ->name('field-diary-activities.ajax.courses');

                Route::get('field-diary-activities/ajax/weather-stations', [FieldDiaryActivityController::class, 'getWeatherStations'])
                    ->name('field-diary-activities.ajax.weather-stations');

                Route::resource('field-diary-activities', FieldDiaryActivityController::class);

                Route::get('field-diary-submissions', [AdminFieldDiarySubmissionController::class, 'index'])
                    ->name('field-diary-submissions.index');

                Route::get('field-diary-submissions-export', [AdminFieldDiarySubmissionController::class, 'export'])
                    ->name('field-diary-submissions.export');

                Route::get('field-diary-submissions/ajax/grades', [AdminFieldDiarySubmissionController::class, 'getGrades'])
                    ->name('field-diary-submissions.ajax.grades');

                Route::get('field-diary-submissions/ajax/courses', [AdminFieldDiarySubmissionController::class, 'getCourses'])
                    ->name('field-diary-submissions.ajax.courses');

                Route::get('field-diary-submissions/ajax/activities', [AdminFieldDiarySubmissionController::class, 'getActivities'])
                    ->name('field-diary-submissions.ajax.activities');

                Route::get('field-diary-submissions/ajax/students', [AdminFieldDiarySubmissionController::class, 'getStudents'])
                    ->name('field-diary-submissions.ajax.students');

                Route::get('field-diary-submissions/{field_diary_submission}', [AdminFieldDiarySubmissionController::class, 'show'])
                    ->name('field-diary-submissions.show');

                Route::post('field-diary-submissions/{field_diary_submission}/review', [AdminFieldDiarySubmissionController::class, 'review'])
                    ->name('field-diary-submissions.review');
            });

        /*
        |--------------------------------------------------------------------------
        | Vista estudiante de guías y Diario de Campo
        | estudiante
        |--------------------------------------------------------------------------
        */
        Route::middleware('role:estudiante')->group(function () {
            Route::get('/estudiante/laboratory-guides', [LaboratoryGuideStudentController::class, 'index'])
                ->name('estudiante.laboratory-guides.index');

            Route::get('/estudiante/laboratory-guides/{laboratory_guide}/view', [LaboratoryGuideStudentController::class, 'view'])
                ->name('estudiante.laboratory-guides.view');

            Route::get('/estudiante/laboratory-guides/{laboratory_guide}/download', [LaboratoryGuideStudentController::class, 'download'])
                ->name('estudiante.laboratory-guides.download');

            Route::get('/estudiante/field-diaries', [FieldDiarySubmissionController::class, 'index'])
                ->name('estudiante.field-diaries.index');

            Route::get('/estudiante/field-diaries/{field_diary_activity}', [FieldDiarySubmissionController::class, 'show'])
                ->name('estudiante.field-diaries.show');

            Route::post('/estudiante/field-diaries/{field_diary_activity}/save', [FieldDiarySubmissionController::class, 'save'])
                ->name('estudiante.field-diaries.save');

            Route::post('/estudiante/field-diaries/{field_diary_activity}/submit', [FieldDiarySubmissionController::class, 'submit'])
                ->name('estudiante.field-diaries.submit');

        });

        /*
        |--------------------------------------------------------------------------
        | Reportes por correo
        |--------------------------------------------------------------------------
        */
        Route::get('/reports/mail', [ReportMailController::class, 'create'])
            ->name('reports.mail');

        Route::post('/reports/mail/send', [ReportMailController::class, 'send'])
            ->name('reports.mail.send');

        /*
        |--------------------------------------------------------------------------
        | Calendario ambiental público interno
        | usuarios autenticados
        |--------------------------------------------------------------------------
        */
        Route::get('calendario-ambiental', [EnvironmentalEventPublicController::class, 'index'])
            ->name('environmental-events.index');

        Route::get('calendario-ambiental/{environmental_event}', [EnvironmentalEventPublicController::class, 'show'])
            ->name('environmental-events.show');

        Route::post('/environmental-events/{environmental_event}/acknowledge', [EnvironmentalEventAcknowledgementController::class, 'store'])
            ->name('environmental-events.acknowledge');
        Route::post('/environmental-events/acknowledge-all', [EnvironmentalEventAcknowledgementController::class, 'storeMany'])
            ->name('environmental-events.acknowledge-all');
    });
});

Route::post('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
})->middleware('auth')->name('logout');

require __DIR__ . '/auth.php';
