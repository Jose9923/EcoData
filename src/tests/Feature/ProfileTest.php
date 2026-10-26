<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function payload(User $user): array
    {
        return ['name' => 'Updated Name', 'email' => $user->email,
            'document_type' => 'CC', 'document_number' => '900100'];
    }

    public function test_profile_page_is_displayed(): void
    {
        $this->actingAs(User::factory()->create())->get('/profile')->assertOk();
    }

    public function test_profile_can_be_updated_without_losing_email_verification(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->from('/profile')->put('/profile', $this->payload($user))
            ->assertRedirect('/profile')->assertSessionHasNoErrors();
        $this->assertSame('Updated Name', $user->fresh()->name);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_changing_email_requires_verification_of_new_address(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $payload = array_replace($this->payload($user), ['email' => 'changed@example.test']);
        $this->actingAs($user)->put('/profile', $payload)
            ->assertRedirect(route('verification.notice'));
        $this->assertSame('changed@example.test', $user->fresh()->email);
        $this->assertNull($user->fresh()->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->get('/profile')->assertRedirect(route('verification.notice'));
    }

    public function test_profile_form_cannot_change_privileges_or_school(): void
    {
        $user = User::factory()->create();
        $payload = array_replace($this->payload($user), [
            'school_id' => 999, 'role' => 'super_admin', 'is_active' => false,
            'email_verified_at' => null,
        ]);
        $this->actingAs($user)->put('/profile', $payload)->assertSessionHasNoErrors();
        $this->assertNull($user->fresh()->school_id);
        $this->assertTrue($user->fresh()->is_active);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertCount(0, $user->fresh()->roles);
    }
}
