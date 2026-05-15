<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $name,
        public readonly string $otpCode,
        public readonly int $expiresInMinutes,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Community Will verification code',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.registration-otp',
        );
    }
}
