<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_api_sends_reset_email_successfully(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'reset-user@example.test',
        ]);

        $this->postJson('/api/forgot-password', [
            'email' => $user->email,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Password reset email sent successfully.');

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_reset_password_page_route_is_available(): void
    {
        $this->get(route('password.reset', [
            'token' => 'example-reset-token',
            'email' => 'reset-user@example.test',
        ]))
            ->assertOk()
            ->assertSee('Reset Password')
            ->assertSee('reset-user@example.test');
    }

    public function test_reset_password_api_updates_the_user_password(): void
    {
        $user = User::factory()->create([
            'email' => 'reset-user@example.test',
            'password' => Hash::make('OldPassword123!'),
        ]);

        $token = Password::broker()->createToken($user);

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Password reset completed successfully.');

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
    }
}
