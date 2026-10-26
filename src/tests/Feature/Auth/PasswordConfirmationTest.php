<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_confirmation_screen_can_be_rendered(): void
    {
        $this->actingAs(User::factory()->create())->get('/confirm-password')->assertOk();
    }

    public function test_password_can_be_confirmed_using_http_form(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/confirm-password', ['password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertSessionHas('auth.password_confirmed_at');
    }

    public function test_incorrect_password_does_not_confirm_session(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/confirm-password', ['password' => 'wrong'])
            ->assertSessionHasErrors('password')
            ->assertSessionMissing('auth.password_confirmed_at');
    }
}
