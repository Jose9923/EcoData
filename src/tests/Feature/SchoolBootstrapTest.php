<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SchoolBootstrapTest extends TestCase
{
    use RefreshDatabase;

    private array $temporaryFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->app->instance('env', 'production');
        Notification::fake();
    }

    protected function tearDown(): void
    {
        foreach (array_reverse($this->temporaryFiles) as $path) {
            if (is_file($path) || is_link($path)) {
                unlink($path);
            }
        }
        parent::tearDown();
    }

    public function test_preview_leaves_database_unchanged_without_reading_password(): void
    {
        $this->assertSame(0, Artisan::call('ecodata:bootstrap-school', [
            '--config-file' => $this->privateFile(json_encode($this->configuration())),
            '--password-file' => '/missing-password',
        ]));
        $output = Artisan::output();
        $this->assertStringContainsString('"state":"new"', $output);
        $this->assertStringContainsString('"users_to_create":4', $output);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('schools', 0);
        Notification::assertNothingSent();
    }

    public function test_fresh_bootstrap_creates_exactly_one_school_and_four_verified_accounts_without_email(): void
    {
        $rolesBefore = DB::table('role_has_permissions')->orderBy('role_id')->orderBy('permission_id')->get()->toJson();
        $this->assertSame(0, $this->apply());
        $output = Artisan::output();
        $school = School::sole();
        $this->assertSame('Institución Educativa de Prueba', $school->name);
        $this->assertTrue($school->is_active);
        $this->assertDatabaseCount('users', 4);
        $this->assertDatabaseCount('schools', 1);
        $this->assertDatabaseCount('grades', 0);
        $this->assertDatabaseCount('courses', 0);
        $this->assertDatabaseCount('physical_variable_records', 0);
        foreach ($this->configuration()['users'] as $account) {
            $user = User::where('email', $account['email'])->firstOrFail();
            $this->assertSame($account['name'], $user->name);
            $this->assertTrue($user->is_active);
            $this->assertTrue($user->hasVerifiedEmail());
            $this->assertSame([$account['role']], $user->getRoleNames()->all());
            $this->assertTrue(Hash::check('Fixture-18', $user->password));
            $this->assertSame($account['role'] === 'super_admin' ? null : $school->id, $user->school_id);
            $this->assertNull($user->document_type);
            $this->assertNull($user->document_number);
            $this->assertNull($user->grade_id);
            $this->assertNull($user->course_id);
            $this->assertStringNotContainsString($user->password, $output);
            $this->assertStringNotContainsString($user->remember_token, $output);
        }
        $this->assertStringNotContainsString('Fixture-18', $output);
        $this->assertSame($rolesBefore, DB::table('role_has_permissions')->orderBy('role_id')->orderBy('permission_id')->get()->toJson());
        Notification::assertNothingSent();
        $this->app->instance('env', 'testing');
        $this->post('/login', ['email' => 'superadmin@example.org', 'password' => 'Fixture-18'])->assertRedirect('/dashboard');
    }

    public function test_exact_rerun_preserves_passwords_names_roles_and_remember_tokens(): void
    {
        $this->assertSame(0, $this->apply());
        $before = DB::table('users')->orderBy('id')->get()->toJson();
        $rolesBefore = DB::table('model_has_roles')->orderBy('model_id')->get()->toJson();
        $schoolBefore = School::sole()->getRawOriginal();
        $this->assertSame(0, $this->apply(['--password-file' => '/not-read-on-rerun']));
        $this->assertStringContainsString('"state":"preserved"', Artisan::output());
        $this->assertSame($before, DB::table('users')->orderBy('id')->get()->toJson());
        $this->assertSame($rolesBefore, DB::table('model_has_roles')->orderBy('model_id')->get()->toJson());
        $this->assertSame($schoolBefore, School::sole()->getRawOriginal());
        Notification::assertNothingSent();
    }

    public function test_any_existing_unrelated_account_aborts_without_promotion_or_mutation(): void
    {
        $existing = User::factory()->create(['email' => 'superadmin@example.org']);
        $before = $existing->fresh()->getRawOriginal();
        $this->assertSame(2, $this->apply());
        $this->assertSame($before, $existing->fresh()->getRawOriginal());
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('schools', 0);
        $this->assertFalse($existing->fresh()->hasRole('super_admin'));
    }

    public function test_changed_existing_bootstrap_is_not_overwritten(): void
    {
        $this->assertSame(0, $this->apply());
        $student = User::where('email', 'student@example.org')->firstOrFail();
        $student->forceFill(['name' => 'Changed name'])->save();
        $before = DB::table('users')->orderBy('id')->get()->toJson();
        $this->assertSame(2, $this->apply());
        $this->assertSame($before, DB::table('users')->orderBy('id')->get()->toJson());
    }

    public function test_roles_environment_and_operator_confirmation_are_required(): void
    {
        $this->app->instance('env', 'local');
        $this->assertSame(2, $this->apply());
        $this->app->instance('env', 'production');
        $this->assertSame(2, $this->apply(['--verified-operator-emails' => false]));
        Role::where('name', 'docente')->firstOrFail()->delete();
        $this->assertSame(2, $this->apply());
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('schools', 0);
        $this->assertDatabaseMissing('roles', ['name' => 'docente']);
    }

    public function test_duplicate_roles_emails_and_embedded_password_are_rejected(): void
    {
        $configuration = $this->configuration();
        $configuration['users'][3]['role'] = 'docente';
        $this->assertSame(2, $this->apply(['--config-file' => $this->privateFile(json_encode($configuration))]));
        $configuration = $this->configuration();
        $configuration['users'][3]['email'] = 'SUPERADMIN@example.org';
        $this->assertSame(2, $this->apply(['--config-file' => $this->privateFile(json_encode($configuration))]));
        $configuration = $this->configuration();
        $configuration['password'] = 'must-be-in-separate-file';
        $this->assertSame(2, $this->apply(['--config-file' => $this->privateFile(json_encode($configuration))]));
        $this->assertStringNotContainsString('must-be-in-separate-file', Artisan::output());
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('schools', 0);
    }

    #[DataProvider('invalidPasswordFiles')]
    public function test_password_file_constraints_prevent_changes(string $kind): void
    {
        $path = $this->privateFile('Fixture-18');
        if ($kind === 'permissions') {
            chmod($path, 0644);
        } elseif ($kind === 'symlink') {
            $link = $path.'-link';
            symlink($path, $link);
            $this->temporaryFiles[] = $link;
            $path = $link;
        } elseif ($kind === 'repository') {
            $path = storage_path('bootstrap-password-fixture.txt');
            file_put_contents($path, 'Fixture-18');
            chmod($path, 0600);
            $this->temporaryFiles[] = $path;
        } elseif ($kind === 'multiline') {
            file_put_contents($path, "Fixture-18\nsecond");
        } elseif ($kind === 'oversized') {
            file_put_contents($path, str_repeat('x', 2049));
        } elseif ($kind === 'weak') {
            file_put_contents($path, 'short');
        }
        $this->assertSame(2, $this->apply(['--password-file' => $path]));
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('schools', 0);
        $this->assertStringNotContainsString('Fixture-18', Artisan::output());
    }

    public static function invalidPasswordFiles(): array
    {
        return array_map(fn (string $kind): array => [$kind], ['permissions', 'symlink', 'repository', 'multiline', 'oversized', 'weak']);
    }

    private function apply(array $overrides = []): int
    {
        return Artisan::call('ecodata:bootstrap-school', array_replace([
            '--apply' => true,
            '--config-file' => $this->privateFile(json_encode($this->configuration())),
            '--password-file' => $this->privateFile("Fixture-18\n", 0400),
            '--verified-operator-emails' => true,
        ], $overrides));
    }

    private function privateFile(string $contents, int $mode = 0600): string
    {
        $path = tempnam(sys_get_temp_dir(), 'ecodata-bootstrap-test-');
        file_put_contents($path, $contents);
        chmod($path, $mode);
        $this->temporaryFiles[] = $path;

        return $path;
    }

    private function configuration(): array
    {
        return [
            'school' => ['name' => 'Institución Educativa de Prueba'],
            'users' => [
                ['name' => 'Super admin fixture', 'email' => 'superadmin@example.org', 'role' => 'super_admin'],
                ['name' => 'School admin fixture', 'email' => 'school-admin@example.org', 'role' => 'admin_colegio'],
                ['name' => 'Teacher fixture', 'email' => 'teacher@example.org', 'role' => 'docente'],
                ['name' => 'Student fixture', 'email' => 'student@example.org', 'role' => 'estudiante'],
            ],
        ];
    }
}
