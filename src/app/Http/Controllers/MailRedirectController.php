<?php

namespace App\Http\Controllers;

use App\Models\EnvironmentalEvent;
use App\Models\FieldDiaryActivity;
use App\Models\FieldDiarySubmission;
use App\Models\LaboratoryGuide;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MailRedirectController extends Controller
{
    public function laboratoryGuides(Request $request): RedirectResponse
    {
        $user = $request->user();
        $guide = $this->laboratoryGuideFromRequest($request);

        if ($guide) {
            $this->authorizeLaboratoryGuide($user, $guide);
        }

        if ($user->hasRole('estudiante')) {
            return redirect()->route('estudiante.laboratory-guides.index');
        }

        if ($this->hasAdminContentRole($user)) {
            return redirect()->route('admin.laboratory-guides.index');
        }

        abort(403, 'No tienes permisos para consultar guías de laboratorio.');
    }

    public function fieldDiaries(Request $request): RedirectResponse
    {
        $user = $request->user();
        $activity = $this->fieldDiaryActivityFromRequest($request);
        $submission = $this->fieldDiarySubmissionFromRequest($request);

        if ($activity) {
            $this->authorizeFieldDiaryActivity($user, $activity);
        }

        if ($submission) {
            $this->authorizeFieldDiarySubmission($user, $submission);
        }

        if ($user->hasRole('estudiante')) {
            return redirect()->route('estudiante.field-diaries.index');
        }

        if ($this->hasAdminContentRole($user)) {
            if ($submission) {
                return redirect()->route('admin.field-diary-submissions.show', $submission);
            }

            if ($activity) {
                return redirect()->route('admin.field-diary-activities.show', $activity);
            }

            $context = (string) $request->query('context');

            return redirect()->route(
                $context === 'activity'
                    ? 'admin.field-diary-activities.index'
                    : 'admin.field-diary-submissions.index'
            );
        }

        abort(403, 'No tienes permisos para consultar diarios de campo.');
    }

    public function environmentalEvents(Request $request): RedirectResponse
    {
        $user = $request->user();
        $event = $this->environmentalEventFromRequest($request);

        if (! $user->hasRole('super_admin') && ! $user->school_id) {
            return redirect()->route('account.school-required');
        }

        if ($event) {
            $this->authorizeEnvironmentalEvent($user, $event);

            return redirect()->route('environmental-events.show', $event);
        }

        return redirect()->route('environmental-events.index');
    }

    public function reports(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasAnyRole(['super_admin', 'admin_colegio', 'docente', 'estudiante'])) {
            return redirect()->route('admin.physical-variable-records.index');
        }

        abort(403, 'No tienes permisos para consultar reportes.');
    }

    private function laboratoryGuideFromRequest(Request $request): ?LaboratoryGuide
    {
        if (! $request->filled('guide')) {
            return null;
        }

        return LaboratoryGuide::findOrFail((int) $request->query('guide'));
    }

    private function fieldDiaryActivityFromRequest(Request $request): ?FieldDiaryActivity
    {
        if (! $request->filled('activity')) {
            return null;
        }

        return FieldDiaryActivity::findOrFail((int) $request->query('activity'));
    }

    private function fieldDiarySubmissionFromRequest(Request $request): ?FieldDiarySubmission
    {
        if (! $request->filled('submission')) {
            return null;
        }

        return FieldDiarySubmission::with('activity')->findOrFail((int) $request->query('submission'));
    }

    private function environmentalEventFromRequest(Request $request): ?EnvironmentalEvent
    {
        if (! $request->filled('event')) {
            return null;
        }

        return EnvironmentalEvent::findOrFail((int) $request->query('event'));
    }

    private function authorizeLaboratoryGuide(User $user, LaboratoryGuide $guide): void
    {
        if ($user->hasRole('super_admin')) {
            return;
        }

        $this->authorizeSchoolScope($user, $guide->school_id, 'No tienes autorización para consultar guías de otro colegio.');

        if (! $user->hasRole('estudiante')) {
            return;
        }

        abort_unless($guide->is_active, 403, 'La guía no está activa.');

        abort_if(
            $guide->published_at && $guide->published_at->isFuture(),
            403,
            'La guía aún no está disponible.'
        );

        abort_if(
            $guide->grade_id !== null && (int) $guide->grade_id !== (int) $user->grade_id,
            403,
            'No tienes autorización para consultar guías de otro grado.'
        );

        abort_if(
            $guide->course_id !== null && (int) $guide->course_id !== (int) $user->course_id,
            403,
            'No tienes autorización para consultar guías de otro curso.'
        );
    }

    private function authorizeFieldDiaryActivity(User $user, FieldDiaryActivity $activity): void
    {
        if ($user->hasRole('super_admin')) {
            return;
        }

        $this->authorizeSchoolScope($user, $activity->school_id, 'No tienes autorización para consultar diarios de otro colegio.');

        if (! $user->hasRole('estudiante')) {
            return;
        }

        $today = now()->toDateString();

        abort_unless($activity->is_active, 403, 'La actividad de diario no está activa.');

        abort_if(
            $activity->grade_id !== null && (int) $activity->grade_id !== (int) $user->grade_id,
            403,
            'No tienes autorización para consultar diarios de otro grado.'
        );

        abort_if(
            $activity->course_id !== null && (int) $activity->course_id !== (int) $user->course_id,
            403,
            'No tienes autorización para consultar diarios de otro curso.'
        );

        abort_if(
            $activity->starts_at && $activity->starts_at->toDateString() > $today,
            403,
            'La actividad de diario aún no está disponible.'
        );

        abort_if(
            $activity->ends_at && $activity->ends_at->toDateString() < $today,
            403,
            'La actividad de diario ya no está disponible.'
        );
    }

    private function authorizeFieldDiarySubmission(User $user, FieldDiarySubmission $submission): void
    {
        if ($user->hasRole('super_admin')) {
            return;
        }

        $submission->loadMissing('activity');

        $this->authorizeSchoolScope($user, $submission->school_id, 'No tienes autorización para consultar entregas de otro colegio.');

        abort_if(
            (int) $submission->activity?->school_id !== (int) $user->school_id,
            403,
            'No tienes autorización para consultar entregas de otro colegio.'
        );

        if ($user->hasRole('estudiante')) {
            abort_if(
                (int) $submission->user_id !== (int) $user->id,
                403,
                'No tienes autorización para consultar entregas de otro estudiante.'
            );
        }
    }

    private function authorizeEnvironmentalEvent(User $user, EnvironmentalEvent $event): void
    {
        abort_unless($event->is_active, 404);

        if ($user->hasRole('super_admin')) {
            return;
        }

        $this->authorizeSchoolScope($user, $event->school_id, 'No tienes autorización para consultar eventos de otro colegio.');
    }

    private function authorizeSchoolScope(User $user, ?int $schoolId, string $message): void
    {
        abort_if(! $user->school_id, 403, 'Tu usuario no tiene un colegio asignado.');

        abort_if((int) $schoolId !== (int) $user->school_id, 403, $message);
    }

    private function hasAdminContentRole(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_colegio', 'docente']);
    }
}
