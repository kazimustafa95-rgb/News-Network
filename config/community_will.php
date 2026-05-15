<?php

return [
    'registration_otp' => [
        'expires_minutes' => (int) env('REGISTRATION_OTP_EXPIRES_MINUTES', 10),
        'resend_cooldown_seconds' => (int) env('REGISTRATION_OTP_RESEND_COOLDOWN_SECONDS', 60),
        'max_attempts' => (int) env('REGISTRATION_OTP_MAX_ATTEMPTS', 5),
    ],
    'media' => [
        'submission_disk' => env('SUBMISSION_MEDIA_DISK', env('FILESYSTEM_DISK', 'public')),
        'post_disk' => env('POST_MEDIA_DISK', env('AWS_BUCKET') ? 's3' : env('FILESYSTEM_DISK', 'public')),
        'profile_disk' => env('PROFILE_MEDIA_DISK', env('FILESYSTEM_DISK', 'public')),
        'ad_disk' => env('ADVERTISEMENT_MEDIA_DISK', env('FILESYSTEM_DISK', 'public')),
    ],
];
