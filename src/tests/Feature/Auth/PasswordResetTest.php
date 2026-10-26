<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_request_and_reset_form_can_be_rendered(): void
    {
        $this->get('/forgot-password')->assertOk();
        $this->get('/reset-password/example-token')->assertOk();
    }

    public function test_reset_link_is_sent_for_active_user(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('success');
        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
            return Password::broker()->tokenExists($user, $notification->token);
        });
    }

    public function test_missing_and_inactive_accounts_receive_same_generic_response_without_mail(): void
    {
        Notification::fake();
        $user = User::factory()->create(['is_active' => false]);
        $inactive = $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('success');
        $message = session('success');
        $this->post('/forgot-password', ['email' => 'missing@example.test'])
            ->assertSessionHas('success', $message);
        Notification::assertNothingSent();
    }

    public function test_reset_sets_password_verifies_email_and_consumes_token(): void
    {
        $user = User::factory()->unverified()->create();
        $token = Password::broker()->createToken($user);
        $payload = ['email' => $user->email, 'token' => $token,
            'password' => 'new-password', 'password_confirmation' => 'new-password'];
        $this->post('/reset-password', $payload)->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertFalse(Password::broker()->tokenExists($user, $token));
        $this->post('/reset-password', $payload)->assertSessionHasErrors('email');
    }

    public function test_expired_token_and_inactive_user_cannot_reset_password(): void
    {
        $user = User::factory()->create();
        $token = Password::broker()->createToken($user);
        $this->travel(config('auth.passwords.users.expire') + 1)->minutes();
        $this->post('/reset-password', ['email' => $user->email, 'token' => $token,
            'password' => 'new-password', 'password_confirmation' => 'new-password'])
            ->assertSessionHasErrors('email');
        $this->travelBack();
        $token = Password::broker()->createToken($user);
        $user->update(['is_active' => false]);
        $this->post('/reset-password', ['email' => $user->email, 'token' => $token,
            'password' => 'new-password', 'password_confirmation' => 'new-password'])
            ->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
