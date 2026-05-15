<?php

namespace Tests\Feature\Api;

use App\Mail\RegistrationOtpMail;
use App\Models\PendingRegistration;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_register_starts_pending_registration_and_sends_otp_email(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/register', [
            'name' => 'Baker Davis',
            'email' => 'baker@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'device_name' => 'iphone-16',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Verification code sent to your email address.')
            ->assertJsonPath('data.email', 'baker@example.test');

        $this->assertDatabaseMissing('users', ['email' => 'baker@example.test']);
        $this->assertDatabaseHas('pending_registrations', ['email' => 'baker@example.test']);

        Mail::assertSent(RegistrationOtpMail::class, function (RegistrationOtpMail $mail): bool {
            return $mail->hasTo('baker@example.test')
                && strlen($mail->otpCode) === 6
                && $mail->expiresInMinutes === 10;
        });
    }

    public function test_verify_otp_creates_verified_user_and_returns_token(): void
    {
        Mail::fake();

        $this->postJson('/api/register', [
            'name' => 'Baker Davis',
            'email' => 'baker@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'device_name' => 'iphone-16',
        ])->assertOk();

        $otpCode = null;
        Mail::assertSent(RegistrationOtpMail::class, function (RegistrationOtpMail $mail) use (&$otpCode): bool {
            $otpCode = $mail->otpCode;

            return true;
        });

        $response = $this->postJson('/api/register/verify-otp', [
            'email' => 'baker@example.test',
            'otp' => $otpCode,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Registration completed successfully.')
            ->assertJsonPath('data.user.email', 'baker@example.test');

        $this->assertDatabaseHas('users', [
            'email' => 'baker@example.test',
        ]);

        $this->assertDatabaseMissing('pending_registrations', [
            'email' => 'baker@example.test',
        ]);

        $user = User::query()->where('email', 'baker@example.test')->firstOrFail();

        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->roles()->where('slug', 'user')->exists());
        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_invalid_otp_does_not_create_user(): void
    {
        Mail::fake();

        $this->postJson('/api/register', [
            'name' => 'Baker Davis',
            'email' => 'baker@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertOk();

        $response = $this->postJson('/api/register/verify-otp', [
            'email' => 'baker@example.test',
            'otp' => '000000',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation failed.');

        $this->assertDatabaseMissing('users', ['email' => 'baker@example.test']);
        $this->assertDatabaseHas('pending_registrations', [
            'email' => 'baker@example.test',
            'attempts' => 1,
        ]);
    }

    public function test_unverified_user_cannot_log_in(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'unverified@example.test',
            'password' => Hash::make('Password123!'),
        ]);

        $userRole = Role::query()->where('slug', 'user')->firstOrFail();
        $user->roles()->syncWithoutDetaching([$userRole->id]);

        $response = $this->postJson('/api/login', [
            'email' => 'unverified@example.test',
            'password' => 'Password123!',
        ]);

        $response
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Please verify your email address before logging in.');
    }
}
