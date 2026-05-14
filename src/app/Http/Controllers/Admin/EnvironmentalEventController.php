<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEnvironmentalEventRequest;
use App\Http\Requests\Admin\UpdateEnvironmentalEventRequest;
use App\Models\EnvironmentalEvent;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EnvironmentalEventController extends Controller
{
    public function index(Request $request): View
    {
        $authUser = $request->user();

        $search = (string) $request->string('search')->toString();
        $perPage = (int) $request->integer('per_page', 10);

        if (! in_array($perPage, [10, 15, 25, 50], true)) {
            $perPage = 10;
        }

        $events = EnvironmentalEvent::query()
            ->with(['school', 'creator'])
            ->when(! $authUser->hasRole('super_admin'), function ($query) use ($authUser) {
                $query->where('school_id', $authUser->school_id);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('title', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%')
                        ->orWhereHas('school', fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
                        ->orWhereHas('creator', fn ($q) => $q->where('name', 'like', '%' . $search . '%'));
                });
            })
            ->orderByDesc('starts_at')
            ->paginate($perPage)
            ->appends($request->query());

        return view('admin.environmental-events.index', compact('events', 'search', 'perPage'));
    }

    public function create(Request $request): View
    {
        $authUser = $request->user();

        return view('admin.environmental-events.create', [
            'event' => null,
            'schools' => $this->visibleSchools($authUser),
            'selectedSchoolId' => $this->resolveSelectedSchoolId($request),
        ]);
    }

    public function store(StoreEnvironmentalEventRequest $request): RedirectResponse
    {
        $authUser = $request->user();
        $data = $request->validated();

        $schoolId = $authUser->hasRole('super_admin')
            ? (int) $data['school_id']
            : (int) $authUser->school_id;

        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $this->storeImage($request->file('image'), $schoolId, $data['title']);
        }

        EnvironmentalEvent::create([
            'school_id' => $schoolId,
            'created_by' => $authUser->id,
            'title' => trim($data['title']),
            'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
            'image_path' => $imagePath,
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'is_active' => (bool) $data['is_active'],
        ]);

        return redirect()
            ->route('admin.environmental-events.index')
            ->with('success', 'Evento ambiental creado correctamente.');
    }

    public function show(Request $request, EnvironmentalEvent $environmental_event): View
    {
        $authUser = $request->user();

        $this->authorizeSchoolScope($authUser, $environmental_event->school_id);

        $environmental_event->load(['school', 'creator']);

        return view('admin.environmental-events.show', [
            'event' => $environmental_event,
        ]);
    }

    public function edit(Request $request, EnvironmentalEvent $environmental_event): View
    {
        $authUser = $request->user();

        $this->authorizeSchoolScope($authUser, $environmental_event->school_id);

        $selectedSchoolId = $authUser->hasRole('super_admin')
            ? old('school_id', $request->integer('school_id') ?: $environmental_event->school_id)
            : $authUser->school_id;

        return view('admin.environmental-events.edit', [
            'event' => $environmental_event,
            'schools' => $this->visibleSchools($authUser),
            'selectedSchoolId' => $selectedSchoolId,
        ]);
    }

    public function update(UpdateEnvironmentalEventRequest $request, EnvironmentalEvent $environmental_event): RedirectResponse
    {
        $authUser = $request->user();

        $this->authorizeSchoolScope($authUser, $environmental_event->school_id);

        $data = $request->validated();

        $schoolId = $authUser->hasRole('super_admin')
            ? (int) $data['school_id']
            : (int) $authUser->school_id;

        if ($request->hasFile('image')) {
            if ($environmental_event->image_path && Storage::disk('public')->exists($environmental_event->image_path)) {
                Storage::disk('public')->delete($environmental_event->image_path);
            }

            $environmental_event->image_path = $this->storeImage($request->file('image'), $schoolId, $data['title']);
        }

        $environmental_event->fill([
            'school_id' => $schoolId,
            'title' => trim($data['title']),
            'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'is_active' => (bool) $data['is_active'],
        ])->save();

        return redirect()
            ->route('admin.environmental-events.index')
            ->with('success', 'Evento ambiental actualizado correctamente.');
    }

    public function destroy(Request $request, EnvironmentalEvent $environmental_event): RedirectResponse
    {
        $authUser = $request->user();

        $this->authorizeSchoolScope($authUser, $environmental_event->school_id);

        if ($environmental_event->image_path && Storage::disk('public')->exists($environmental_event->image_path)) {
            Storage::disk('public')->delete($environmental_event->image_path);
        }

        $environmental_event->delete();

        return redirect()
            ->route('admin.environmental-events.index')
            ->with('success', 'Evento ambiental eliminado correctamente.');
    }

    private function visibleSchools(User $authUser)
    {
        return School::query()
            ->where('is_active', true)
            ->when(! $authUser->hasRole('super_admin'), fn ($query) => $query->where('id', $authUser->school_id))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function resolveSelectedSchoolId(Request $request): ?int
    {
        $authUser = $request->user();

        if (! $authUser->hasRole('super_admin')) {
            abort_if(! $authUser->school_id, 403, 'Tu usuario no tiene un colegio asignado.');

            return (int) $authUser->school_id;
        }

        return $request->integer('school_id') ?: null;
    }

    private function authorizeSchoolScope(User $authUser, ?int $schoolId): void
    {
        if ($authUser->hasRole('super_admin')) {
            return;
        }

        abort_if(! $authUser->school_id, 403, 'Tu usuario no tiene un colegio asignado.');

        abort_if(
            (int) $schoolId !== (int) $authUser->school_id,
            403,
            'No tienes autorización para gestionar eventos ambientales de otro colegio.'
        );
    }

    private function storeImage($file, int $schoolId, string $title): string
    {
        $fileName = now()->format('Ymd_His') . '_' . Str::slug($title) . '.' . $file->getClientOriginalExtension();

        return $file->storeAs(
            "environmental-events/school-{$schoolId}",
            $fileName,
            'public'
        );
    }
}