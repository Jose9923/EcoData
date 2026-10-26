<?php

namespace Tests\Feature\Auth;

use App\Exports\Sheets\UsersTemplateSheetExport;
use App\Models\Course;
use App\Models\Grade;
use App\Models\School;
use App\Models\User;
use App\Notifications\UserCreatedNotification;
use App\Services\UserImportService;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserInvitationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private School $otherSchool;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        foreach (['super_admin', 'admin_colegio', 'estudiante'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        $this->school = School::create(['name' => 'School A', 'slug' => 'school-a', 'is_active' => true]);
        $this->otherSchool = School::create(['name' => 'School B', 'slug' => 'school-b', 'is_active' => true]);
        $this->admin = User::factory()->create(['school_id' => $this->school->id]);
        $this->admin->assignRole('admin_colegio');
    }

    private function row(string $email = 'student@example.test', string $document = '1001'): array
    {
        return ['name' => 'Test Student', 'email' => $email, 'document_type' => 'TI',
            'document_number' => $document, 'role' => 'estudiante',
            'school' => $this->school->name, 'is_active' => true];
    }

    private function form(): array
    {
        $form = $this->row();
        unset($form['school']);

        return $form + ['school_id' => $this->school->id, 'grade_id' => null, 'course_id' => null];
    }

    public function test_admin_creates_unverified_account_with_individual_expiring_invitation(): void
    {
        $this->actingAs($this->admin)->post(route('admin.users.store'), $this->form())
            ->assertRedirect(route('admin.users.index'))->assertSessionHasNoErrors();

        $user = User::where('email', 'student@example.test')->firstOrFail();
        $this->assertTrue($user->hasRole('estudiante'));
        $this->assertSame($this->school->id, $user->school_id);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, UserCreatedNotification::class, function ($notification) use ($user) {
            $this->assertInstanceOf(ShouldQueue::class, $notification);
            $this->assertInstanceOf(ShouldBeEncrypted::class, $notification);
            $this->assertTrue($notification->afterCommit);
            $this->assertTrue(Password::broker()->tokenExists($user, $notification->token));
            $mail = $notification->toMail($user);
            $this->assertArrayNotHasKey('temporaryPassword', $mail->viewData);
            $this->assertStringContainsString($notification->token, $mail->viewData['activationUrl']);
            $this->assertSame(config('auth.passwords.users.expire'), $mail->viewData['expiresInMinutes']);

            return true;
        });
    }

    public function test_admin_cannot_supply_shared_creation_password_or_foreign_school(): void
    {
        $this->actingAs($this->admin)->post(route('admin.users.store'),
            $this->form() + ['password' => 'unsafe-password', 'password_confirmation' => 'unsafe-password'])
            ->assertSessionHasErrors('password');
        $this->post(route('admin.users.store'),
            array_replace($this->form(), ['school_id' => $this->otherSchool->id]))
            ->assertSessionHasErrors('school_id');
        $this->assertDatabaseMissing('users', ['email' => 'student@example.test']);
        Notification::assertNothingSent();
    }

    public function test_non_super_account_requires_school_and_matching_grade_and_course(): void
    {
        $super = User::factory()->create();
        $super->assignRole('super_admin');
        $this->actingAs($super)->post(route('admin.users.store'),
            array_replace($this->form(), ['school_id' => null]))
            ->assertSessionHasErrors('school_id');

        $foreignGrade = Grade::create(['school_id' => $this->otherSchool->id, 'name' => '6', 'is_active' => true]);
        $foreignCourse = Course::create(['school_id' => $this->otherSchool->id, 'grade_id' => $foreignGrade->id,
            'name' => '6-A', 'is_active' => true]);
        $this->actingAs($this->admin)->post(route('admin.users.store'),
            array_replace($this->form(), ['grade_id' => $foreignGrade->id, 'course_id' => $foreignCourse->id]))
            ->assertSessionHasErrors(['grade_id', 'course_id']);
        $this->assertDatabaseMissing('users', ['email' => 'student@example.test']);
    }

    public function test_pending_registration_is_approved_by_assigning_school_role_and_invitation(): void
    {
        $pending = User::factory()->unverified()->create(['is_active' => false]);
        $super = User::factory()->create();
        $super->assignRole('super_admin');
        $payload = array_replace($this->form(), ['email' => $pending->email]);
        $this->actingAs($super)->put(route('admin.users.update', $pending), $payload)
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.users.index'));
        $this->assertTrue($pending->fresh()->is_active);
        $this->assertTrue($pending->fresh()->hasRole('estudiante'));
        $this->assertSame($this->school->id, $pending->fresh()->school_id);
        $this->assertFalse($pending->fresh()->hasVerifiedEmail());
        Notification::assertSentTo($pending, UserCreatedNotification::class);
    }

    public function test_import_ignores_legacy_password_column_and_creates_distinct_invitations(): void
    {
        $rows = [
            $this->row() + ['password' => 'old-shared-password'],
            $this->row('second@example.test', '1002'),
        ];
        $summary = app(UserImportService::class)->process($rows, 'create_only', $this->admin);
        $this->assertSame(2, $summary['created']);
        $this->assertSame([], $summary['errors']);
        $first = User::where('email', 'student@example.test')->firstOrFail();
        $second = User::where('email', 'second@example.test')->firstOrFail();
        $this->assertFalse(Hash::check('old-shared-password', $first->password));
        $this->assertNotSame($first->password, $second->password);
        $this->assertNull($first->email_verified_at);
        $this->assertNull($second->email_verified_at);
        $firstInvitation = Notification::sent($first, UserCreatedNotification::class)->sole();
        $secondInvitation = Notification::sent($second, UserCreatedNotification::class)->sole();
        $this->assertNotSame($firstInvitation->token, $secondInvitation->token);

        $this->post(route('password.store'), ['email' => $first->email, 'token' => $firstInvitation->token,
            'password' => 'chosen-by-owner', 'password_confirmation' => 'chosen-by-owner'])
            ->assertRedirect(route('login'));
        $this->assertTrue($first->fresh()->hasVerifiedEmail());
        $this->assertTrue(Hash::check('chosen-by-owner', $first->fresh()->password));
    }

    public function test_import_cannot_update_another_school_or_assign_super_admin(): void
    {
        $foreign = User::factory()->create(['school_id' => $this->otherSchool->id, 'document_number' => '9000']);
        $rows = [
            $this->row($foreign->email, '9000'),
            array_replace($this->row('elevated@example.test', '9001'), ['role' => 'super_admin']),
            array_replace($this->row('foreign@example.test', '9002'), ['school' => $this->otherSchool->name]),
        ];
        $summary = app(UserImportService::class)->process($rows, 'update_or_create', $this->admin);
        $this->assertSame(0, $summary['created']);
        $this->assertSame(0, $summary['updated']);
        $this->assertCount(3, $summary['errors']);
        $this->assertSame($this->otherSchool->id, $foreign->fresh()->school_id);
        $this->assertDatabaseMissing('users', ['email' => 'elevated@example.test']);
        Notification::assertNothingSent();
        $this->actingAs($this->admin)->get(route('admin.users.edit', $foreign))->assertForbidden();
        $this->put(route('admin.users.update', $foreign), $this->form())->assertForbidden();
    }

    public function test_import_email_change_removes_verification_and_sends_new_invitation(): void
    {
        $user = User::factory()->create(['school_id' => $this->school->id, 'document_number' => '1001']);
        $summary = app(UserImportService::class)->process([$this->row()], 'update_or_create', $this->admin);
        $this->assertSame(1, $summary['updated']);
        $this->assertSame('student@example.test', $user->fresh()->email);
        $this->assertNull($user->fresh()->email_verified_at);
        Notification::assertSentTo($user, UserCreatedNotification::class);
    }

    public function test_inactive_imported_account_does_not_receive_invitation(): void
    {
        $row = array_replace($this->row(), ['is_active' => false]);
        $summary = app(UserImportService::class)->process([$row], 'create_only', $this->admin);
        $this->assertSame(1, $summary['created']);
        $this->assertFalse(User::where('email', $row['email'])->firstOrFail()->is_active);
        Notification::assertNothingSent();
    }

    public function test_template_has_no_password_column(): void
    {
        $rows = (new UsersTemplateSheetExport($this->admin, $this->school))->array();
        $this->assertNotContains('password', $rows[0]);
        $this->assertCount(count($rows[0]), $rows[1]);
    }
}
