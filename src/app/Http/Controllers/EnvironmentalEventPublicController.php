<?php

namespace App\Http\Controllers;

use App\Models\EnvironmentalEvent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnvironmentalEventPublicController extends Controller
{
    public function index(Request $request): View
    {
        $authUser = $request->user();

        abort_if(! $authUser->school_id && ! $authUser->hasRole('super_admin'), 403, 'Tu usuario no tiene un colegio asignado.');

        $today = now()->toDateString();

        $events = EnvironmentalEvent::query()
            ->with('school')
            ->where('is_active', true)
            ->when(! $authUser->hasRole('super_admin'), fn ($query) => $query->where('school_id', $authUser->school_id))
            ->orderBy('starts_at')
            ->get();

        $todayEvents = $events
            ->filter(fn ($event) => $event->starts_at->toDateString() <= $today && $event->ends_at->toDateString() >= $today)
            ->values();

        $upcomingEvents = $events
            ->filter(fn ($event) => $event->starts_at->toDateString() > $today)
            ->values();

        $pastEvents = $events
            ->filter(fn ($event) => $event->ends_at->toDateString() < $today)
            ->sortByDesc('starts_at')
            ->values();

        $pendingEnvironmentalEvents = EnvironmentalEvent::query()
            ->with('school')
            ->where('is_active', true)
            ->whereDate('starts_at', '<=', $today)
            ->whereDate('ends_at', '>=', $today)
            ->when(! $authUser->hasRole('super_admin'), function ($query) use ($authUser) {
                $query->where('school_id', $authUser->school_id);
            })
            ->whereDoesntHave('acknowledgements', function ($query) use ($authUser) {
                $query->where('user_id', $authUser->id);
            })
            ->orderBy('starts_at')
            ->get();

        return view('environmental-events.index', [
            'todayEvents' => $todayEvents,
            'upcomingEvents' => $upcomingEvents,
            'pastEvents' => $pastEvents,
            'pendingEnvironmentalEvents' => $pendingEnvironmentalEvents,
        ]);
    }

    public function show(Request $request, EnvironmentalEvent $environmental_event): View
    {
        $authUser = $request->user();

        abort_if(! $environmental_event->is_active, 404);

        if (! $authUser->hasRole('super_admin')) {
            abort_if((int) $environmental_event->school_id !== (int) $authUser->school_id, 403);
        }

        $environmental_event->load('school');

        return view('environmental-events.show', [
            'event' => $environmental_event,
        ]);
    }
}