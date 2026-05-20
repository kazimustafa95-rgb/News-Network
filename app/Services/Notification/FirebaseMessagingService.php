<?php

namespace App\Services\Notification;

use App\Models\UserNotificationDevice;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class FirebaseMessagingService
{
    public function sendToDevices(iterable $devices, array $payload): void
    {
        if (! $this->enabled()) {
            return;
        }

        foreach ($devices as $device) {
            if (! $device instanceof UserNotificationDevice || ! $device->is_active) {
                continue;
            }

            $this->sendToDevice($device, $payload);
        }
    }

    public function sendToDevice(UserNotificationDevice $device, array $payload): void
    {
        if (! $this->enabled() || blank($device->token)) {
            return;
        }

        try {
            $accessToken = $this->accessToken();
            $projectId = $this->projectId();

            if (blank($accessToken) || blank($projectId)) {
                return;
            }

            $response = Http::withToken($accessToken)
                ->connectTimeout((int) config('services.firebase.connect_timeout', 5))
                ->timeout((int) config('services.firebase.request_timeout', 10))
                ->post(sprintf('https://fcm.googleapis.com/v1/projects/%s/messages:send', $projectId), [
                    'message' => [
                        'token' => $device->token,
                        'notification' => [
                            'title' => (string) ($payload['title'] ?? ''),
                            'body' => (string) ($payload['body'] ?? ''),
                        ],
                        'data' => $this->normalizeDataPayload((array) ($payload['data'] ?? [])),
                        'android' => [
                            'priority' => 'high',
                            'notification' => [
                                'channel_id' => 'news_updates',
                                'sound' => 'default',
                            ],
                        ],
                        'apns' => [
                            'headers' => [
                                'apns-priority' => '10',
                            ],
                            'payload' => [
                                'aps' => [
                                    'sound' => 'default',
                                ],
                            ],
                        ],
                    ],
                ]);

            if ($response->successful()) {
                return;
            }

            $errorCode = data_get($response->json(), 'error.details.0.errorCode');

            if ($errorCode === 'UNREGISTERED') {
                $device->markInactive();
            }

            Log::warning('Firebase notification delivery failed.', [
                'device_id' => $device->id,
                'user_id' => $device->user_id,
                'status' => $response->status(),
                'error_code' => $errorCode,
                'response' => $response->json(),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Firebase notification delivery threw an exception.', [
                'device_id' => $device->id,
                'user_id' => $device->user_id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    protected function enabled(): bool
    {
        return (bool) config('services.firebase.enabled', false);
    }

    protected function accessToken(): ?string
    {
        $credentials = $this->credentials();

        if ($credentials === null) {
            return null;
        }

        $cacheKey = sprintf(
            'firebase:access-token:%s',
            sha1((string) Arr::get($credentials, 'client_email', 'default'))
        );

        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($credentials): ?string {
            $jwt = $this->buildJwtAssertion($credentials);

            $response = Http::asForm()
                ->connectTimeout((int) config('services.firebase.connect_timeout', 5))
                ->timeout((int) config('services.firebase.request_timeout', 10))
                ->post('https://oauth2.googleapis.com/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);

            if (! $response->successful()) {
                Log::warning('Failed to obtain Firebase OAuth token.', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);

                return null;
            }

            return $response->json('access_token');
        });
    }

    protected function projectId(): ?string
    {
        $credentials = $this->credentials();

        return config('services.firebase.project_id')
            ?: Arr::get($credentials, 'project_id');
    }

    protected function credentials(): ?array
    {
        $path = $this->credentialsPath();

        if ($path === null || ! is_file($path)) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded)) {
            Log::warning('Firebase credentials file could not be decoded.', [
                'path' => $path,
            ]);

            return null;
        }

        return $decoded;
    }

    protected function credentialsPath(): ?string
    {
        $path = trim((string) config('services.firebase.credentials', ''));

        if ($path === '') {
            return null;
        }

        if (is_file($path)) {
            return $path;
        }

        $relativePath = base_path($path);

        return is_file($relativePath) ? $relativePath : null;
    }

    protected function buildJwtAssertion(array $credentials): string
    {
        $now = now()->timestamp;
        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ], JSON_THROW_ON_ERROR));
        $payload = $this->base64UrlEncode(json_encode([
            'iss' => Arr::get($credentials, 'client_email'),
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ], JSON_THROW_ON_ERROR));
        $unsignedToken = $header.'.'.$payload;
        $privateKey = Arr::get($credentials, 'private_key');

        if (! is_string($privateKey) || $privateKey === '') {
            throw new RuntimeException('Firebase private key is missing.');
        }

        $signature = '';
        $signed = openssl_sign($unsignedToken, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        if (! $signed) {
            throw new RuntimeException('Firebase JWT signing failed.');
        }

        return $unsignedToken.'.'.$this->base64UrlEncode($signature);
    }

    protected function normalizeDataPayload(array $payload): array
    {
        $data = [];

        foreach ($payload as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (is_bool($value)) {
                $data[$key] = $value ? '1' : '0';

                continue;
            }

            if (is_scalar($value)) {
                $data[$key] = (string) $value;

                continue;
            }

            $data[$key] = json_encode($value);
        }

        return $data;
    }

    protected function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
