<?php

namespace App\Http\Controllers;

use App\Models\FieldDiaryAnswer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FieldDiaryAnswerFileController extends Controller
{
    public function show(Request $request, FieldDiaryAnswer $field_diary_answer): StreamedResponse
    {
        $authUser = $request->user();

        $field_diary_answer->loadMissing(['submission.activity', 'question']);

        $this->authorizeAnswerFile($authUser, $field_diary_answer);

        abort_if(! $field_diary_answer->answer_file_path, 404, 'La evidencia no tiene archivo asociado.');

        $disk = $this->resolveStorageDisk($field_diary_answer->answer_file_path);

        abort_if(! $disk, 404, 'El archivo de evidencia no existe.');

        return Storage::disk($disk)->download(
            $field_diary_answer->answer_file_path,
            basename($field_diary_answer->answer_file_path)
        );
    }

    private function authorizeAnswerFile(User $authUser, FieldDiaryAnswer $answer): void
    {
        $submission = $answer->submission;

        abort_if(! $submission, 404);

        abort_if(
            ! $submission->activity
                || ! $answer->question
                || (int) $answer->question->field_diary_activity_id !== (int) $submission->field_diary_activity_id,
            404
        );

        if ($authUser->hasRole('super_admin')) {
            return;
        }

        abort_unless(
            $authUser->hasAnyRole(['admin_colegio', 'docente', 'estudiante']),
            403,
            'No tienes autorización para consultar evidencias de diario de campo.'
        );

        abort_if(! $authUser->school_id, 403, 'Tu usuario no tiene un colegio asignado.');

        abort_if(
            (int) $submission->school_id !== (int) $authUser->school_id,
            403,
            'No tienes autorización para consultar evidencias de otro colegio.'
        );

        abort_if(
            (int) $submission->activity->school_id !== (int) $authUser->school_id,
            403,
            'No tienes autorizacion para consultar evidencias asociadas a otro colegio.'
        );

        if ($authUser->hasRole('estudiante')) {
            abort_if(
                (int) $submission->user_id !== (int) $authUser->id,
                403,
                'No tienes autorización para consultar evidencias de otro estudiante.'
            );
        }
    }

    private function resolveStorageDisk(string $path): ?string
    {
        if (Storage::disk('local')->exists($path)) {
            return 'local';
        }

        if (Storage::disk('public')->exists($path)) {
            return 'public';
        }

        return null;
    }
}
