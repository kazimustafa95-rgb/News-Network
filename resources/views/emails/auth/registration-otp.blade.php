<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Community Will Verification Code</title>
</head>
<body style="margin: 0; padding: 24px; background: #f5f7fb; font-family: Arial, Helvetica, sans-serif; color: #111827;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden;">
        <tr>
            <td style="padding: 32px;">
                <p style="margin: 0 0 16px; font-size: 16px;">Hi {{ $name }},</p>
                <h1 style="margin: 0 0 16px; font-size: 28px; line-height: 1.2; color: #0f172a;">Verify your email address</h1>
                <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.6; color: #4b5563;">
                    Use the verification code below to complete your Community Will account registration.
                </p>
                <div style="margin: 0 0 24px; padding: 20px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 14px; text-align: center;">
                    <div style="font-size: 34px; font-weight: 700; letter-spacing: 10px; color: #2563eb;">{{ $otpCode }}</div>
                </div>
                <p style="margin: 0 0 12px; font-size: 14px; line-height: 1.6; color: #4b5563;">
                    This code expires in {{ $expiresInMinutes }} minutes.
                </p>
                <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #6b7280;">
                    If you did not request this code, you can safely ignore this email.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
