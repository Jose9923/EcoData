<?php

namespace App\Http\Controllers;

use App\Models\LaboratoryGuide;
use App\Models\PhysicalVariable;
use App\Models\PhysicalVariableRecord;
use App\Models\School;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $authUser = auth()->user();

        $isSuperAdmin = $authUser?->hasRole('super_admin') ?? false;
        $isSchoolAdmin = $authUser?->hasRole('admin_colegio') ?? false;
        $isdocente = $authUser?->hasRole('docente') ?? false;
        $isestudiante = $authUser?->hasRole('estudiante') ?? false;

        abort_if(! $isSuperAdmin && ! $authUser?->school_id, 403, 'Tu usuario no tiene un colegio asignado.');

        $schoolId = $isSuperAdmin ? null : $authUser->school_id;

        $stats = [
            'schools' => $isSuperAdmin ? School::count() : 1,

            'users' => User::query()
                ->when(! $isSuperAdmin, fn ($query) => $query->where('school_id', $schoolId))
                ->when($isestudiante, fn ($query) => $query->where('id', $authUser->id))
                ->count(),

            'active_users' => User::query()
                ->where('is_active', true)
                ->when(! $isSuperAdmin, fn ($query) => $query->where('school_id', $schoolId))
                ->when($isestudiante, fn ($query) => $query->where('id', $authUser->id))
                ->count(),

            'physical_variables' => PhysicalVariable::query()
                ->where('is_active', true)
                ->when(! $isSuperAdmin, fn ($query) => $query->where('school_id', $schoolId))
                ->count(),

            'physical_records' => PhysicalVariableRecord::query()
                ->when(! $isSuperAdmin, fn ($query) => $query->where('school_id', $schoolId))
                ->when($isestudiante, function ($query) use ($authUser) {
                    $query->where(function ($subQuery) use ($authUser) {
                        $subQuery->whereNull('grade_id')
                            ->orWhere('grade_id', $authUser->grade_id);
                    })->where(function ($subQuery) use ($authUser) {
                        $subQuery->whereNull('course_id')
                            ->orWhere('course_id', $authUser->course_id);
                    });
                })
                ->count(),

            'laboratory_guides' => LaboratoryGuide::query()
                ->where('is_active', true)
                ->when(! $isSuperAdmin, fn ($query) => $query->where('school_id', $schoolId))
                ->when($isestudiante, function ($query) use ($authUser) {
                    $query->where(function ($subQuery) {
                        $subQuery->whereNull('published_at')
                            ->orWhere('published_at', '<=', now());
                    })->where(function ($subQuery) use ($authUser) {
                        $subQuery->whereNull('grade_id')
                            ->orWhere('grade_id', $authUser->grade_id);
                    })->where(function ($subQuery) use ($authUser) {
                        $subQuery->whereNull('course_id')
                            ->orWhere('course_id', $authUser->course_id);
                    });
                })
                ->count(),
        ];

        $recentUsers = User::query()
            ->with(['school', 'grade', 'course'])
            ->when(! $isSuperAdmin, fn ($query) => $query->where('school_id', $schoolId))
            ->when($isestudiante, fn ($query) => $query->where('id', $authUser->id))
            ->latest()
            ->take(5)
            ->get();

        $recentRecords = PhysicalVariableRecord::query()
            ->with(['school', 'grade', 'course', 'user'])
            ->when(! $isSuperAdmin, fn ($query) => $query->where('school_id', $schoolId))
            ->when($isestudiante, function ($query) use ($authUser) {
                $query->where(function ($subQuery) use ($authUser) {
                    $subQuery->whereNull('grade_id')
                        ->orWhere('grade_id', $authUser->grade_id);
                })->where(function ($subQuery) use ($authUser) {
                    $subQuery->whereNull('course_id')
                        ->orWhere('course_id', $authUser->course_id);
                });
            })
            ->latest('recorded_at')
            ->take(5)
            ->get();

        $recentGuides = LaboratoryGuide::query()
            ->with(['school', 'grade', 'course', 'creator'])
            ->where('is_active', true)
            ->when(! $isSuperAdmin, fn ($query) => $query->where('school_id', $schoolId))
            ->when($isestudiante, function ($query) use ($authUser) {
                $query->where(function ($subQuery) {
                    $subQuery->whereNull('published_at')
                        ->orWhere('published_at', '<=', now());
                })->where(function ($subQuery) use ($authUser) {
                    $subQuery->whereNull('grade_id')
                        ->orWhere('grade_id', $authUser->grade_id);
                })->where(function ($subQuery) use ($authUser) {
                    $subQuery->whereNull('course_id')
                        ->orWhere('course_id', $authUser->course_id);
                });
            })
            ->latest()
            ->take(5)
            ->get();

        $pending = [
            'users_without_document' => User::query()
                ->when(! $isSuperAdmin, fn ($query) => $query->where('school_id', $schoolId))
                ->when($isestudiante, fn ($query) => $query->where('id', $authUser->id))
                ->where(function ($query) {
                    $query->whereNull('document_type')
                        ->orWhere('document_type', '')
                        ->orWhereNull('document_number')
                        ->orWhere('document_number', '');
                })
                ->count(),

            'users_without_assignment' => User::query()
                ->when(! $isSuperAdmin, fn ($query) => $query->where('school_id', $schoolId))
                ->when($isestudiante, fn ($query) => $query->where('id', $authUser->id))
                ->where(function ($query) {
                    $query->whereNull('school_id')
                        ->orWhereNull('grade_id')
                        ->orWhereNull('course_id');
                })
                ->count(),

            'inactive_users' => User::query()
                ->where('is_active', false)
                ->when(! $isSuperAdmin, fn ($query) => $query->where('school_id', $schoolId))
                ->when($isestudiante, fn ($query) => $query->where('id', $authUser->id))
                ->count(),
        ];

        return view('dashboard', compact(
            'stats',
            'recentUsers',
            'recentRecords',
            'recentGuides',
            'pending',
            'isSuperAdmin',
            'isSchoolAdmin',
            'isdocente',
            'isestudiante'
        ));
    }
}