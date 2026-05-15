<?php

namespace App\Services\Auth;

use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Mail\RegistrationOtpMail;
use App\Models\PendingRegistration;
use App\Models\Role;
use App\Models\User;
use App\Repositories\Contracts\PendingRegistrationRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly PendingRegistrationRepositoryInterface $pendingRegistrations,
    ) {
    }

    public function register(array $attributes): array
    {
        $this->pendingRegistrations->deleteExpired();

        $existingPendingRegistration = $this->pendingRegistrations->findByEmail($attributes['email']);

        if ($existingPendingRegistration && $this->isWithinCooldownWindow($existingPendingRegistration)) {
            $pendingRegistration = $this->pendingRegistrations->updateOrCreateByEmail($attributes['email'], [
                'name' => $attributes['name'],
                'password' => Hash::make($attributes['password']),
                'device_name' => $attributes['device_name'] ?? null,
            ]);

            return [
                'message' => 'Verification code already sent. Please check your email.',
                'data' => $this->formatPendingRegistrationPayload($pendingRegistration),
            ];
        }

        $otpCode = $this->generateOtpCode();
        $pendingRegistration = $this->pendingRegistrations->updateOrCreateByEmail($attributes['email'], [
            'name' => $attributes['name'],
            'password' => Hash::make($attributes['password']),
            'device_name' => $attributes['device_name'] ?? null,
            'otp_code' => Hash::make($otpCode),
            'attempts' => 0,
            'last_sent_at' => now(),
            'expires_at' => now()->addMinutes($this->registrationOtpExpiryMinutes()),
        ]);

        $this->sendRegistrationOtp($pendingRegistration, $otpCode);

        return [
            'message' => 'Verification code sent to your email address.',
            'data' => $this->formatPendingRegistrationPayload($pendingRegistration),
        ];
    }

    public function verifyRegistrationOtp(array $attributes): array
    {
        $this->pendingRegistrations->deleteExpired();

        $pendingRegistration = $this->pendingRegistrations->findByEmail($attributes['email']);

        if (! $pendingRegistration) {
            throw ValidationException::withMessages([
                'email' => ['No pending verification request was found for this email address.'],
            ]);
        }

        if ($pendingRegistration->attempts >= $this->registrationOtpMaxAttempts()) {
            $this->pendingRegistrations->delete($pendingRegistration);

            throw ValidationException::withMessages([
                'otp' => ['Too many invalid verification attempts. Please request a new code.'],
            ]);
        }

        if (! Hash::check($attributes['otp'], $pendingRegistration->otp_code)) {
            $pendingRegistration = $this->pendingRegistrations->incrementAttempts($pendingRegistration);

            if ($pendingRegistration->attempts >= $this->registrationOtpMaxAttempts()) {
                $this->pendingRegistrations->delete($pendingRegistration);
            }

            throw ValidationException::withMessages([
                'otp' => ['The verification code is invalid.'],
            ]);
        }

        if ($this->users->findByEmail($pendingRegistration->email)) {
            $this->pendingRegistrations->delete($pendingRegistration);

            throw ValidationException::withMessages([
                'email' => ['An account with this email address already exists.'],
            ]);
        }

        return DB::transaction(function () use ($pendingRegistration): array {
            $user = $this->users->create([
                'name' => $pendingRegistration->name,
                'email' => $pendingRegistration->email,
                'password' => $pendingRegistration->password,
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ]);

            $nameParts = preg_split('/\s+/', trim($pendingRegistration->name)) ?: [];
            $user->profile()->create([
                'first_name' => $nameParts[0] ?? $pendingRegistration->name,
                'last_name' => count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : null,
            ]);

            $defaultUserRole = Role::query()->where('slug', RoleSlug::User->value)->first();

            if ($defaultUserRole) {
                $user->roles()->syncWithoutDetaching([$defaultUserRole->id]);
            }

            $token = $user->createToken($pendingRegistration->device_name ?: 'mobile-app')->plainTextToken;

            $this->pendingRegistrations->delete($pendingRegistration);

            return compact('user', 'token');
        });
    }

    public function resendRegistrationOtp(string $email): array
    {
        $this->pendingRegistrations->deleteExpired();

        $pendingRegistration = $this->pendingRegistrations->findByEmail($email);

        if (! $pendingRegistration) {
            throw ValidationException::withMessages([
                'email' => ['No pending verification request was found for this email address.'],
            ]);
        }

        if ($this->isWithinCooldownWindow($pendingRegistration)) {
            throw ValidationException::withMessages([
                'email' => ['Please wait before requesting another verification code.'],
            ]);
        }

        $otpCode = $this->generateOtpCode();
        $pendingRegistration = $this->pendingRegistrations->updateOrCreateByEmail($pendingRegistration->email, [
            'otp_code' => Hash::make($otpCode),
            'attempts' => 0,
            'last_sent_at' => now(),
            'expires_at' => now()->addMinutes($this->registrationOtpExpiryMinutes()),
        ]);

        $this->sendRegistrationOtp($pendingRegistration, $otpCode);

        return [
            'message' => 'Verification code resent successfully.',
            'data' => $this->formatPendingRegistrationPayload($pendingRegistration),
        ];
    }

    public function login(array $credentials): array
    {
        $user = $this->users->findByEmail($credentials['email']);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw new AuthenticationException('The provided credentials are incorrect.');
        }

        if (! $user->email_verified_at) {
            throw new AuthenticationException('Please verify your email address before logging in.');
        }

        if ($user->status !== UserStatus::Active) {
            throw new AuthenticationException('Your account is not active.');
        }

        $this->users->update($user, [
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);

        $token = $user->createToken($credentials['device_name'] ?? 'mobile-app')->plainTextToken;

        return compact('user', 'token');
    }

    public function logout(User $user, bool $allDevices = false): void
    {
        if ($allDevices) {
            $user->tokens()->delete();

            return;
        }

        $user->currentAccessToken()?->delete();
    }

    private function sendRegistrationOtp(PendingRegistration $pendingRegistration, string $otpCode): void
    {
        Mail::to($pendingRegistration->email)->send(
            new RegistrationOtpMail(
                name: $pendingRegistration->name,
                otpCode: $otpCode,
                expiresInMinutes: $this->registrationOtpExpiryMinutes(),
            ),
        );
    }

    private function formatPendingRegistrationPayload(PendingRegistration $pendingRegistration): array
    {
        return [
            'email' => $pendingRegistration->email,
            'expires_at' => $pendingRegistration->expires_at?->toIso8601String(),
            'expires_in_seconds' => max(0, now()->diffInSeconds($pendingRegistration->expires_at, false)),
            'resend_available_in_seconds' => max(0, $this->secondsUntilResendAvailable($pendingRegistration)),
        ];
    }

    private function generateOtpCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function isWithinCooldownWindow(PendingRegistration $pendingRegistration): bool
    {
        return $this->secondsUntilResendAvailable($pendingRegistration) > 0;
    }

    private function secondsUntilResendAvailable(PendingRegistration $pendingRegistration): int
    {
        if (! $pendingRegistration->last_sent_at) {
            return 0;
        }

        return now()->diffInSeconds(
            $pendingRegistration->last_sent_at->copy()->addSeconds($this->registrationOtpCooldownSeconds()),
            false,
        );
    }

    private function registrationOtpExpiryMinutes(): int
    {
        return max(1, (int) config('community_will.registration_otp.expires_minutes', 10));
    }

    private function registrationOtpCooldownSeconds(): int
    {
        return max(0, (int) config('community_will.registration_otp.resend_cooldown_seconds', 60));
    }

    private function registrationOtpMaxAttempts(): int
    {
        return max(1, (int) config('community_will.registration_otp.max_attempts', 5));
    }
}
