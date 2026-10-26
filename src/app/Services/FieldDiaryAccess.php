<?php

namespace App\Services;

use App\Models\FieldDiaryActivity;
use App\Models\FieldDiarySubmission;
use App\Models\User;

class FieldDiaryAccess
{
    public function canManageSchool(?User $user, ?int $schoolId): bool
    {
        return $user && $user->hasAnyRole(['super_admin', 'admin_colegio', 'docente'])
            && ($user->hasRole('super_admin') || ($user->school_id && (int) $user->school_id === $schoolId));
    }

    public function authorizeManageSchool(User $user, ?int $schoolId): void
    {
        abort_unless($this->canManageSchool($user, $schoolId), 403, 'No puedes gestionar el diario de campo de este colegio.');
    }

    public function authorizeStudent(User $user, FieldDiaryActivity $activity): void
    {
        abort_unless($user->hasRole('estudiante') && $user->school_id, 403);
        abort_unless((int) $activity->school_id === (int) $user->school_id, 403);
        abort_if($activity->grade_id !== null && (int) $activity->grade_id !== (int) $user->grade_id, 403);
        abort_if($activity->course_id !== null && (int) $activity->course_id !== (int) $user->course_id, 403);
    }

    public function isOpen(FieldDiaryActivity $activity): bool
    {
        $today = today()->toDateString();

        return $activity->is_active
            && (! $activity->starts_at || $activity->starts_at->toDateString() <= $today)
            && (! $activity->ends_at || $activity->ends_at->toDateString() >= $today);
    }

    public function authorizeWrite(User $user, FieldDiaryActivity $activity): void
    {
        $this->authorizeStudent($user, $activity);
        abort_unless($this->isOpen($activity), 403, 'La actividad no está disponible para guardar respuestas.');
    }

    public function authorizeReview(User $user, FieldDiarySubmission $submission): void
    {
        $this->authorizeManageSchool($user, (int) $submission->school_id);
        abort_unless((int) $submission->activity?->school_id === (int) $submission->school_id, 403);
        abort_unless((int) $submission->student?->school_id === (int) $submission->school_id, 403);
    }
}
