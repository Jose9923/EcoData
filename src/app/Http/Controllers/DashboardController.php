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
        $stats = [
            'schools' => School::count(),
            'users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'physical_variables' => PhysicalVariable::where('is_active', true)->count(),
            'physical_records' => PhysicalVariableRecord::count(),
            'laboratory_guides' => class_exists(LaboratoryGuide::class)
                ? LaboratoryGuide::where('is_active', true)->count()
                : 0,
        ];

        $recentUsers = User::query()
            ->with(['school', 'grade', 'course'])
            ->latest()
            ->take(5)
            ->get();

        $recentRecords = PhysicalVariableRecord::query()
            ->with(['school', 'grade', 'course', 'user'])
            ->latest('recorded_at')
            ->take(5)
            ->get();

        $recentGuides = class_exists(LaboratoryGuide::class)
            ? LaboratoryGuide::query()
                ->with(['school', 'grade', 'course', 'creator'])
                ->latest()
                ->take(5)
                ->get()
            : collect();

        $pending = [
            'users_without_document' => User::query()
                ->where(function ($query) {
                    $query->whereNull('document_type')
                        ->orWhere('document_type', '')
                        ->orWhereNull('document_number')
                        ->orWhere('document_number', '');
                })
                ->count(),

            'users_without_assignment' => User::query()
                ->where(function ($query) {
                    $query->whereNull('school_id')
                        ->orWhereNull('grade_id')
                        ->orWhereNull('course_id');
                })
                ->count(),

            'inactive_users' => User::where('is_active', false)->count(),
        ];

        return view('dashboard', compact(
            'stats',
            'recentUsers',
            'recentRecords',
            'recentGuides',
            'pending'
        ));
    }
}