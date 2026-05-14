<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WeatherStation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PhysicalVariableRecordImportController extends Controller
{
    public function create(Request $request): View
    {
        $authUser = $request->user();

        return view('admin.physical-variable-record-imports.create', [
            'weatherStations' => $this->visibleWeatherStations($authUser),
        ]);
    }

    private function visibleWeatherStations(User $authUser)
    {
        return WeatherStation::query()
            ->with('school:id,name')
            ->where('is_active', true)
            ->when(! $authUser->hasRole('super_admin'), function ($query) use ($authUser) {
                $query->where('school_id', $authUser->school_id);
            })
            ->orderBy('school_id')
            ->orderBy('name')
            ->get(['id', 'school_id', 'name', 'code']);
    }
}