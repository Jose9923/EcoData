<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Notifications\FieldDiarySubmissionReviewedNotification;
use App\Notifications\NewFieldDiaryActivityNotification;
use App\Notifications\NewLaboratoryGuideNotification;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\EcoDataDemoSeeder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OperationalSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_seeding_is_repeatable_and_preserves_school_and_users(): void
    {
        $school = School::create(['name' => 'Conservar', 'slug' => 'conservar', 'is_active' => true]);
        $user = User::factory()->create(['school_id' => $school->id]);
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseHas('schools', ['id' => $school->id]);
        $this->assertDatabaseCount('roles', 4);
    }

    public function test_demo_is_disabled_by_default_before_it_touches_data(): void
    {
        $user = User::factory()->create();
        config(['ecodata.allow_demo_seed' => false]);
        try {
            (new EcoDataDemoSeeder)->run();
            $this->fail('La demo no debe ejecutarse sin habilitación explícita.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('base descartable', $exception->getMessage());
        }
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_demo_is_rejected_in_production_even_if_flag_is_enabled(): void
    {
        config(['ecodata.allow_demo_seed' => true]);
        $this->app->instance('env', 'production');
        $this->expectException(\RuntimeException::class);
        (new EcoDataDemoSeeder)->run();
    }

    public function test_content_notifications_require_a_worker_and_safe_retry_window(): void
    {
        foreach ([NewFieldDiaryActivityNotification::class, NewLaboratoryGuideNotification::class, FieldDiarySubmissionReviewedNotification::class] as $notification) {
            $this->assertTrue(is_subclass_of($notification, ShouldQueue::class));
        }
        $this->assertGreaterThan(60, config('queue.connections.database.retry_after'));
        $this->assertGreaterThan(60, config('queue.connections.redis.retry_after'));
        $this->assertTrue(config('queue.connections.database.after_commit'));
    }

    public function test_route_inventory_has_no_missing_controllers(): void
    {
        $this->artisan('route:list', ['--except-vendor' => true])->assertExitCode(0);
    }

    public function test_creating_administrator_preserves_custom_permissions_on_existing_roles(): void
    {
        $permission = Permission::findOrCreate('custom.school.reporting', 'web');
        $schoolAdmin = Role::findOrCreate('admin_colegio', 'web');
        $superAdmin = Role::findOrCreate('super_admin', 'web');
        $schoolAdmin->givePermissionTo($permission);
        $superAdmin->givePermissionTo($permission);
        $existing = User::factory()->create();
        $existing->assignRole($schoolAdmin);

        $this->artisan('ecodata:admin')
            ->expectsQuestion('Nombre', 'Segundo administrador')
            ->expectsQuestion('Correo controlado por el administrador', 'second-admin@example.test')
            ->expectsQuestion('Contraseña única (mínimo 12 caracteres)', 'Unique-test-password-184')
            ->expectsQuestion('Repetir contraseña', 'Unique-test-password-184')
            ->assertExitCode(0);

        $this->assertSame(['custom.school.reporting'], $schoolAdmin->fresh()->permissions->pluck('name')->all());
        $this->assertSame(['custom.school.reporting'], $superAdmin->fresh()->permissions->pluck('name')->all());
        $this->assertTrue($existing->fresh()->hasPermissionTo('custom.school.reporting'));
        $this->assertDatabaseCount('roles', 2);
        $this->assertDatabaseCount('permissions', 1);
    }

    public function test_initial_admin_is_created_without_changing_other_accounts(): void
    {
        $existing = User::factory()->create();
        $this->artisan('ecodata:admin')
            ->expectsQuestion('Nombre', 'Administración')
            ->expectsQuestion('Correo controlado por el administrador', 'admin@example.test')
            ->expectsQuestion('Contraseña única (mínimo 12 caracteres)', 'Unique-test-password-184')
            ->expectsQuestion('Repetir contraseña', 'Unique-test-password-184')
            ->assertExitCode(0);
        $admin = User::where('email', 'admin@example.test')->firstOrFail();
        $this->assertTrue($admin->hasRole('super_admin'));
        $this->assertFalse($admin->hasVerifiedEmail());
        $this->assertDatabaseHas('users', ['id' => $existing->id]);
    }
}
