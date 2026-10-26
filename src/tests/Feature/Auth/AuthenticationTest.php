<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_renders_blade_form(): void
    {
        $this->get('/login')->assertOk()->assertSee('name="email"', false);
    }

    public function test_active_verified_user_can_log_in(): void
    {
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_and_inactive_account_are_rejected(): void
    {
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');
        $user->update(['is_active' => false]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_throttled_for_normalized_email_and_ip_then_recovers(): void
    {
        $user = User::factory()->create(['email' => 'student@example.test']);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['email' => ' STUDENT@example.test ', 'password' => 'wrong'])
                ->assertSessionHasErrors('email');
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->travel(61)->seconds();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_rate_limit_for_one_ip_does_not_lock_out_another_ip(): void
    {
        $user = User::factory()->create();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.1'])
                ->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        }
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.2'])
            ->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_ip_limit_blocks_password_spraying_across_different_emails(): void
    {
        $user = User::factory()->create();
        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->post('/login', ['email' => 'missing'.$attempt.'@example.test', 'password' => 'wrong']);
        }
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_inactive_existing_session_is_revoked_even_if_user_instance_is_stale(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        User::whereKey($user->id)->update(['is_active' => false]);
        $this->get('/profile')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_inactive_json_session_gets_forbidden(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => false]))
            ->getJson('/profile')->assertForbidden();
        $this->assertGuest();
    }

    public function test_existing_unverified_user_gets_verification_link_and_notice(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('verification.notice'));
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->get('/profile')->assertRedirect(route('verification.notice'));
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_users_can_logout_with_http_post(): void
    {
        $this->actingAs(User::factory()->create())->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }
}
