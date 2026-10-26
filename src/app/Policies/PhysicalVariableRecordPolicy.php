<?php

namespace App\Policies;

use App\Models\PhysicalVariableRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class PhysicalVariableRecordPolicy
{
    public static function scopeQuery(Builder $query, User $user): Builder
    {
        if (! $user->hasAnyRole(['super_admin', 'admin_colegio', 'docente', 'estudiante'])) {
            return $query->whereRaw('1 = 0');
        }
        if ($user->hasRole('super_admin')) {
            return $query;
        }
        $query->where('physical_variable_records.school_id', $user->school_id ?: -1);
        if ($user->hasRole('estudiante')) {
            $query->where(function (Builder $visibility) use ($user) {
                $visibility->where('physical_variable_records.user_id', $user->id)
                    ->orWhere(function (Builder $assigned) use ($user) {
                        $assigned->where(function (Builder $grade) use ($user) {
                            $grade->whereNull('physical_variable_records.grade_id');
                            if ($user->grade_id) {
                                $grade->orWhere('physical_variable_records.grade_id', $user->grade_id);
                            }
                        })->where(function (Builder $course) use ($user) {
                            $course->whereNull('physical_variable_records.course_id');
                            if ($user->course_id) {
                                $course->orWhere('physical_variable_records.course_id', $user->course_id);
                            }
                        });
                    });
            });
        }

        return $query;
    }

    public function view(User $user, PhysicalVariableRecord $record): bool
    {
        return self::scopeQuery(PhysicalVariableRecord::query(), $user)->whereKey($record->id)->exists();
    }

    public function update(User $user, PhysicalVariableRecord $record): bool
    {
        return $this->view($user, $record) && (! $user->hasRole('estudiante') || (int) $record->user_id === (int) $user->id);
    }

    public function viewHistory(User $user, PhysicalVariableRecord $record): bool
    {
        return $this->update($user, $record);
    }
}
