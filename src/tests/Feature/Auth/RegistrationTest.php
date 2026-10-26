<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        return ['name' => 'Applicant', 'email' => 'applicant@example.test',
            'password' => 'password', 'password_confirmation' => 'password',
            'role' => 'super_admin', 'school_id' => 123, 'is_active' => true];
    }

    public function test_public_registration_is_closed_by_default(): void
    {
        config(['auth.registration_enabled' => false]);
        $this->get('/register')->assertNotFound();
        $this->post('/register', $this->payload())->assertNotFound();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_enabled_registration_creates_pending_account_without_access_or_role(): void
    {
        config(['auth.registration_enabled' => true]);
        $this->get('/register')->assertOk()->assertSee('Solicitar cuenta');
        $this->post('/register', $this->payload())
            ->assertRedirect(route('login'))->assertSessionHas('status');
        $user = User::where('email', 'applicant@example.test')->firstOrFail();
        $this->assertFalse($user->is_active);
        $this->assertNull($user->school_id);
        $this->assertNull($user->email_verified_at);
        $this->assertCount(0, $user->roles);
        $this->assertGuest();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
