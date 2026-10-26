<?php

namespace Tests\Feature;

use App\Models\FieldDiaryActivity;
use App\Models\FieldDiarySubmission;
use App\Models\School;
use App\Models\User;
use App\Services\FieldDiaryRevision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FieldDiaryIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $teacher;

    private User $student;

    private FieldDiaryActivity $activity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->withoutVite();
        Notification::fake();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->school = School::create(['name' => 'Colegio Diario', 'slug' => 'colegio-diario', 'is_active' => true]);
        $this->teacher = $this->user('docente', $this->school);
        $this->student = $this->user('estudiante', $this->school);
        $this->activity = FieldDiaryActivity::create([
            'school_id' => $this->school->id,
            'created_by' => $this->teacher->id,
            'title' => 'Observación ambiental',
            'entry_type' => 'observacion',
            'starts_at' => today()->subDay(),
            'ends_at' => today()->addDay(),
            'is_active' => true,
        ]);
        $this->activity->questions()->create([
            'question_text' => 'Describe el entorno', 'question_type' => 'textarea', 'order' => 1, 'is_required' => true,
        ]);
    }

    public function test_editing_title_dates_and_question_text_preserves_question_ids_and_answers(): void
    {
        $submission = $this->submission('enviado');
        $question = $this->activity->questions()->first();
        $answer = $submission->answers()->create(['field_diary_question_id' => $question->id, 'answer_text' => 'Árboles y aves']);
        $data = $this->activityPayload();
        $data['title'] = 'Observación corregida';
        $data['ends_at'] = today()->addDays(3)->toDateString();
        $data['questions'][0]['question_text'] = 'Describe el entorno observado';
        $data['questions'][0]['order'] = 2;

        $this->actingAs($this->teacher)->put(route('admin.field-diary-activities.update', $this->activity), $data)
            ->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('field_diary_questions', ['id' => $question->id, 'order' => 2]);
        $this->assertDatabaseHas('field_diary_answers', ['id' => $answer->id, 'answer_text' => 'Árboles y aves']);
        $this->assertSame('Observación corregida', $this->activity->fresh()->title);
        $this->assertSame(today()->addDays(3)->toDateString(), $this->activity->fresh()->ends_at->toDateString());
    }

    public function test_structure_changes_with_submissions_are_rejected_without_losing_answers(): void
    {
        $submission = $this->submission();
        $question = $this->activity->questions()->first();
        $submission->answers()->create(['field_diary_question_id' => $question->id, 'answer_text' => 'Borrador conservado']);
        $data = $this->activityPayload();
        $data['questions'][0]['question_type'] = 'number';
        $this->actingAs($this->teacher)->putJson(route('admin.field-diary-activities.update', $this->activity), $data)->assertUnprocessable();
        $this->assertDatabaseHas('field_diary_answers', ['field_diary_question_id' => $question->id, 'answer_text' => 'Borrador conservado']);
        $this->assertSame('textarea', $question->fresh()->question_type);
    }

    public function test_removing_or_adding_questions_after_a_draft_is_rejected(): void
    {
        $this->activity->questions()->create(['question_text' => 'Segunda', 'question_type' => 'text', 'order' => 2]);
        $this->submission();
        $data = $this->activityPayload();
        array_pop($data['questions']);
        $this->actingAs($this->teacher)->putJson(route('admin.field-diary-activities.update', $this->activity), $data)->assertUnprocessable();
        $data = $this->activityPayload();
        $data['questions'][] = ['question_text' => 'Nueva', 'question_type' => 'text', 'order' => 3];
        $this->putJson(route('admin.field-diary-activities.update', $this->activity), $data)->assertUnprocessable();
        $this->assertSame(2, $this->activity->questions()->count());
    }

    public function test_another_activity_question_id_cannot_be_injected(): void
    {
        $other = $this->activity->replicate();
        $other->save();
        $foreign = $other->questions()->create(['question_text' => 'Privada', 'question_type' => 'text', 'order' => 1]);
        $data = $this->activityPayload();
        $data['questions'][0]['id'] = $foreign->id;
        $this->actingAs($this->teacher)->putJson(route('admin.field-diary-activities.update', $this->activity), $data)->assertUnprocessable();
        $this->assertSame('Privada', $foreign->fresh()->question_text);
    }

    public function test_activity_with_submissions_cannot_be_deleted(): void
    {
        $this->submission();
        $this->actingAs($this->teacher)->deleteJson(route('admin.field-diary-activities.destroy', $this->activity))->assertUnprocessable();
        $this->assertDatabaseHas('field_diary_activities', ['id' => $this->activity->id]);
    }

    public function test_teacher_and_student_from_another_school_cannot_edit_save_or_review(): void
    {
        $school = School::create(['name' => 'Otro colegio', 'slug' => 'otro-colegio', 'is_active' => true]);
        $teacher = $this->user('docente', $school);
        $student = $this->user('estudiante', $school);
        $submission = $this->submission('enviado');
        $this->actingAs($teacher)->putJson(route('admin.field-diary-activities.update', $this->activity), $this->activityPayload())->assertForbidden();
        $this->postJson(route('admin.field-diary-submissions.review', $submission), ['revision' => $this->revision(), 'status' => 'revisado'])->assertForbidden();
        $this->actingAs($student)->get(route('estudiante.field-diaries.show', $this->activity))->assertForbidden();
        $this->postJson(route('estudiante.field-diaries.save', $this->activity), ['revision' => $this->revision(), 'autosave' => true, 'answers' => []])->assertForbidden();
        $this->assertSame('enviado', $submission->fresh()->status);
    }

    public function test_autosave_preserves_draft_and_rejects_a_stale_second_tab(): void
    {
        $question = $this->activity->questions()->first();
        $revision = $this->revision();
        $this->actingAs($this->student)->postJson(route('estudiante.field-diaries.save', $this->activity), [
            'revision' => $revision, 'autosave' => true, 'answers' => [$question->id => 'Primer borrador'],
        ])->assertOk()->assertJsonPath('status', 'borrador')->assertJsonStructure(['revision', 'saved_at']);
        $this->postJson(route('estudiante.field-diaries.save', $this->activity), [
            'revision' => $revision, 'autosave' => true, 'answers' => [$question->id => 'Contenido obsoleto'],
        ])->assertConflict()->assertJsonPath('reload_required', true);
        $this->assertDatabaseHas('field_diary_answers', ['field_diary_question_id' => $question->id, 'answer_text' => 'Primer borrador']);
    }

    public function test_autosave_can_save_partial_required_answers_but_submit_cannot(): void
    {
        $question = $this->activity->questions()->first();
        $this->actingAs($this->student)->postJson(route('estudiante.field-diaries.save', $this->activity), [
            'revision' => $this->revision(), 'autosave' => true, 'answers' => [$question->id => ''],
        ])->assertOk();
        $this->postJson(route('estudiante.field-diaries.submit', $this->activity), [
            'revision' => $this->revision(), 'answers' => [$question->id => ''],
        ])->assertUnprocessable();
        $this->assertSame('borrador', $this->activity->submissions()->first()->status);
    }

    public function test_delayed_autosave_does_not_overwrite_sent_or_reviewed_submission(): void
    {
        $question = $this->activity->questions()->first();
        $revision = $this->revision();
        $this->actingAs($this->student)->postJson(route('estudiante.field-diaries.submit', $this->activity), [
            'revision' => $revision, 'answers' => [$question->id => 'Entrega final'],
        ])->assertOk();
        $submission = $this->activity->submissions()->first();
        $lateDraft = ['revision' => $revision, 'autosave' => true, 'answers' => [$question->id => 'Respuesta antigua']];
        $this->postJson(route('estudiante.field-diaries.save', $this->activity), $lateDraft)->assertConflict();
        $this->actingAs($this->teacher)->postJson(route('admin.field-diary-submissions.review', $submission), [
            'revision' => $this->revision(), 'status' => 'revisado', 'score' => 90, 'teacher_feedback' => 'Correcto',
        ])->assertRedirect();
        $this->actingAs($this->student)->postJson(route('estudiante.field-diaries.save', $this->activity), $lateDraft)->assertConflict();
        $this->assertSame('revisado', $submission->fresh()->status);
        $this->assertSame('90.00', $submission->fresh()->score);
        $this->assertDatabaseHas('field_diary_answers', ['field_diary_question_id' => $question->id, 'answer_text' => 'Entrega final']);
    }

    public function test_teacher_cannot_review_draft_or_overwrite_another_review(): void
    {
        $submission = $this->submission();
        $this->actingAs($this->teacher)->postJson(route('admin.field-diary-submissions.review', $submission), [
            'revision' => $this->revision(), 'status' => 'revisado',
        ])->assertUnprocessable();
        $submission->update(['status' => 'enviado']);
        $revision = $this->revision();
        $this->postJson(route('admin.field-diary-submissions.review', $submission), ['revision' => $revision, 'status' => 'revisado', 'score' => 80])->assertRedirect();
        $this->postJson(route('admin.field-diary-submissions.review', $submission), ['revision' => $revision, 'status' => 'devuelto', 'score' => 10])->assertUnprocessable();
        $this->assertSame('revisado', $submission->fresh()->status);
        $this->assertSame('80.00', $submission->fresh()->score);
    }

    public function test_returned_submission_remains_returned_during_autosave_and_can_be_resubmitted(): void
    {
        $submission = $this->submission('devuelto');
        $submission->update(['score' => 30, 'reviewed_by' => $this->teacher->id, 'reviewed_at' => now()]);
        $question = $this->activity->questions()->first();
        $this->actingAs($this->student)->postJson(route('estudiante.field-diaries.save', $this->activity), [
            'revision' => $this->revision(), 'autosave' => true, 'answers' => [$question->id => 'Corrección'],
        ])->assertOk()->assertJsonPath('status', 'devuelto');
        $this->postJson(route('estudiante.field-diaries.submit', $this->activity), [
            'revision' => $this->revision(), 'answers' => [$question->id => 'Corrección terminada'],
        ])->assertOk()->assertJsonPath('status', 'enviado');
        $this->assertNull($submission->fresh()->score);
        $this->assertNull($submission->fresh()->reviewed_at);
    }

    public function test_closed_activity_rejects_autosave_but_keeps_own_history_readable(): void
    {
        $this->submission('revisado');
        $this->activity->update(['ends_at' => today()->subDay()]);
        $this->actingAs($this->student)->postJson(route('estudiante.field-diaries.save', $this->activity), [
            'revision' => $this->revision(), 'autosave' => true, 'answers' => [],
        ])->assertForbidden();
        $this->get(route('estudiante.field-diaries.show', $this->activity))->assertOk()->assertViewHas('canEdit', false);
    }

    public function test_question_option_values_are_validated_and_checkboxes_can_be_cleared(): void
    {
        $question = $this->activity->questions()->first();
        $question->update(['question_type' => 'checkbox', 'options' => ['Árbol', 'Ave'], 'is_required' => false]);
        $this->actingAs($this->student)->postJson(route('estudiante.field-diaries.save', $this->activity), [
            'revision' => $this->revision(), 'autosave' => true, 'answers' => [$question->id => ['Opción ajena']],
        ])->assertUnprocessable();
        $this->postJson(route('estudiante.field-diaries.save', $this->activity), [
            'revision' => $this->revision(), 'autosave' => true, 'answers' => [$question->id => ['Ave']],
        ])->assertOk();
        $this->postJson(route('estudiante.field-diaries.save', $this->activity), [
            'revision' => $this->revision(), 'autosave' => true, 'answers' => [$question->id => []],
        ])->assertOk();
        $this->assertDatabaseHas('field_diary_answers', ['field_diary_question_id' => $question->id, 'answer_text' => '[]']);
    }

    public function test_files_are_manual_unique_and_preserved_by_text_only_autosave(): void
    {
        Storage::fake('local');
        $question = $this->activity->questions()->first();
        $question->update(['question_type' => 'file', 'is_required' => false]);
        $other = $this->activity->questions()->create(['question_text' => 'Otra evidencia', 'question_type' => 'file', 'order' => 2]);
        $this->actingAs($this->student)->post(route('estudiante.field-diaries.save', $this->activity), [
            'revision' => $this->revision(),
            'files' => [$question->id => UploadedFile::fake()->create('evidencia.pdf', 2, 'application/pdf'), $other->id => UploadedFile::fake()->create('evidencia.pdf', 3, 'application/pdf')],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $paths = $this->activity->submissions()->first()->answers()->pluck('answer_file_path');
        $this->assertCount(2, $paths->unique());
        Storage::disk('local')->assertExists($paths->all());
        $this->postJson(route('estudiante.field-diaries.save', $this->activity), [
            'revision' => $this->revision(), 'autosave' => true, 'answers' => [],
        ])->assertOk();
        $this->assertSame($paths->all(), $this->activity->submissions()->first()->answers()->pluck('answer_file_path')->all());
    }

    public function test_activity_changes_invalidate_an_already_open_student_form(): void
    {
        $revision = $this->revision();
        $this->activity->update(['title' => 'Instrucciones actualizadas']);
        $this->actingAs($this->student)->postJson(route('estudiante.field-diaries.save', $this->activity), [
            'revision' => $revision, 'autosave' => true, 'answers' => [],
        ])->assertConflict();
        $this->assertSame(0, $this->activity->submissions()->count());
    }

    public function test_edit_form_keeps_question_ids_and_explains_structure_lock(): void
    {
        $this->submission();
        $this->actingAs($this->teacher)->get(route('admin.field-diary-activities.edit', $this->activity))
            ->assertOk()
            ->assertSee('Esta actividad ya tiene entregas.')
            ->assertSee('name="questions[0][id]"', false);
    }

    public function test_student_form_exposes_revision_and_manages_autosave_submission(): void
    {
        $this->actingAs($this->student)->get(route('estudiante.field-diaries.show', $this->activity))
            ->assertOk()
            ->assertViewHas('canEdit', true)
            ->assertViewHas('revision', $this->revision())
            ->assertSee('data-managed-submit="true"', false)
            ->assertSee('Guardado automático listo.');
    }

    public function test_even_superadmin_cannot_move_an_activity_with_submissions_to_another_school(): void
    {
        $this->submission('enviado');
        $other = School::create(['name' => 'Destino', 'slug' => 'destino', 'is_active' => true]);
        $data = $this->activityPayload();
        $data['school_id'] = $other->id;
        $this->actingAs($this->user('super_admin', $this->school))
            ->putJson(route('admin.field-diary-activities.update', $this->activity), $data)
            ->assertUnprocessable()->assertJsonValidationErrors('school_id');
        $this->assertSame($this->school->id, $this->activity->fresh()->school_id);
    }

    private function user(string $role, School $school): User
    {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        $user = User::factory()->create(['school_id' => $school->id, 'is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    private function submission(string $status = 'borrador'): FieldDiarySubmission
    {
        return $this->activity->submissions()->create([
            'user_id' => $this->student->id, 'school_id' => $this->school->id, 'status' => $status,
        ]);
    }

    private function revision(): string
    {
        return app(FieldDiaryRevision::class)->token($this->activity->fresh(), $this->activity->submissions()->where('user_id', $this->student->id)->first());
    }

    private function activityPayload(): array
    {
        return [
            'school_id' => $this->school->id,
            'title' => $this->activity->title,
            'entry_type' => $this->activity->entry_type,
            'starts_at' => $this->activity->starts_at->toDateString(),
            'ends_at' => $this->activity->ends_at->toDateString(),
            'is_active' => true,
            'questions' => $this->activity->questions()->get()->map(fn ($question) => [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'question_type' => $question->question_type,
                'options_text' => implode("\n", $question->options ?? []),
                'order' => $question->order,
                'is_required' => $question->is_required,
            ])->all(),
        ];
    }
}
