<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_updated_through_profile_route(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->from('/profile')->put('/profile/password', [
            'current_password' => 'password', 'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect('/profile')->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_current_password_is_required_to_update_password(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'wrong', 'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
