<?php

namespace App\Http\Controllers;

use App\Models\EnvironmentalEvent;
use App\Models\EnvironmentalEventAcknowledgement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EnvironmentalEventAcknowledgementController extends Controller
{
    public function store(Request $request, EnvironmentalEvent $environmental_event): RedirectResponse
    {
        $authUser = $request->user();

        abort_if(! $environmental_event->is_active, 404);

        if (! $authUser->hasRole('super_admin')) {
            abort_if((int) $environmental_event->school_id !== (int) $authUser->school_id, 403);
        }

        EnvironmentalEventAcknowledgement::updateOrCreate(
            [
                'environmental_event_id' => $environmental_event->id,
                'user_id' => $authUser->id,
            ],
            [
                'acknowledged_at' => now(),
            ]
        );

        if ($request->input('redirect_to') === 'show') {
            return redirect()->route('environmental-events.show', $environmental_event);
        }

        return back();
    }
}
