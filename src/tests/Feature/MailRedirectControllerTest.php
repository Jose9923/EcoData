<?php

namespace Tests\Feature;

use App\Models\FieldDiaryActivity;
use App\Models\FieldDiarySubmission;
use App\Models\LaboratoryGuide;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MailRedirectControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_guest_is_redirected_to_login_before_mail_redirect(): void
    {
        $this->get(route('mail.laboratory-guides.redirect'))
            ->assertRedirect(route('login'));
    }

    public function test_laboratory_guide_mail_redirect_uses_authenticated_user_role(): void
    {
        $school = $this->createSchool('Colegio Verde');
        $student = $this->createUserWithRole('estudiante', $school);
        $teacher = $this->createUserWithRole('docente', $school);
        $guide = $this->createLaboratoryGuide($school, $teacher);

        $this->actingAs($student)
            ->get(route('mail.laboratory-guides.redirect', ['guide' => $guide->id]))
            ->assertRedirect(route('estudiante.laboratory-guides.index'));

        $this->actingAs($teacher)
            ->get(route('mail.laboratory-guides.redirect', ['guide' => $guide->id]))
            ->assertRedirect(route('admin.laboratory-guides.index'));
    }

    public function test_laboratory_guide_mail_redirect_blocks_other_school_resources(): void
    {
        $school = $this->createSchool('Colegio Verde');
        $otherSchool = $this->createSchool('Colegio Azul');
        $student = $this->createUserWithRole('estudiante', $school);
        $teacher = $this->createUserWithRole('docente', $otherSchool);
        $guide = $this->createLaboratoryGuide($otherSchool, $teacher);

        $this->actingAs($student)
            ->get(route('mail.laboratory-guides.redirect', ['guide' => $guide->id]))
            ->assertForbidden();
    }

    public function test_field_diary_review_redirect_respects_role_and_student_ownership(): void
    {
        $school = $this->createSchool('Colegio Verde');
        $student = $this->createUserWithRole('estudiante', $school);
        $teacher = $this->createUserWithRole('docente', $school);
        $otherStudent = $this->createUserWithRole('estudiante', $school);
        $activity = FieldDiaryActivity::create([
            'school_id' => $school->id,
            'created_by' => $teacher->id,
            'title' => 'Diario de prueba',
            'entry_type' => 'observacion',
            'is_active' => true,
        ]);
        $submission = FieldDiarySubmission::create([
            'field_diary_activity_id' => $activity->id,
            'user_id' => $student->id,
            'school_id' => $school->id,
            'status' => 'revisado',
        ]);

        $this->actingAs($student)
            ->get(route('mail.field-diaries.redirect', ['submission' => $submission->id, 'context' => 'review']))
            ->assertRedirect(route('estudiante.field-diaries.index'));

        $this->actingAs($teacher)
            ->get(route('mail.field-diaries.redirect', ['submission' => $submission->id, 'context' => 'review']))
            ->assertRedirect(route('admin.field-diary-submissions.show', $submission));

        $this->actingAs($otherStudent)
            ->get(route('mail.field-diaries.redirect', ['submission' => $submission->id, 'context' => 'review']))
            ->assertForbidden();
    }

    private function createSchool(string $name): School
    {
        return School::create([
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'is_active' => true,
        ]);
    }

    private function createUserWithRole(string $role, School $school): User
    {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);

        $user = User::create([
            'name' => ucfirst($role) . ' EcoData',
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'school_id' => $school->id,
            'is_active' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }

    private function createLaboratoryGuide(School $school, User $creator): LaboratoryGuide
    {
        return LaboratoryGuide::create([
            'school_id' => $school->id,
            'title' => 'Guía de laboratorio',
            'description' => 'Guía de prueba',
            'pdf_path' => 'laboratory-guides/test.pdf',
            'published_at' => now(),
            'is_active' => true,
            'created_by' => $creator->id,
        ]);
    }
}
