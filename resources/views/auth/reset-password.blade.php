<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6fb;
            color: #1f2937;
        }

        .page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .card {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.12);
            padding: 32px;
        }

        h1 {
            margin: 0 0 12px;
            font-size: 28px;
        }

        p {
            margin: 0 0 20px;
            line-height: 1.5;
            color: #4b5563;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
        }

        input {
            width: 100%;
            box-sizing: border-box;
            padding: 12px 14px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        button {
            width: 100%;
            border: 0;
            border-radius: 10px;
            padding: 13px 16px;
            font-size: 15px;
            font-weight: 700;
            color: #ffffff;
            background: #1d4ed8;
            cursor: pointer;
        }

        .alert {
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }

        ul {
            margin: 0;
            padding-left: 18px;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="card">
            <h1>Reset Password</h1>
            <p>Enter your new password below to finish resetting your Community Will account.</p>

            @if (! empty($status))
                <div class="alert alert-success">{{ $status }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-error">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (! $completed)
                <form method="POST" action="{{ route('password.update') }}">
                    @csrf

                    <input type="hidden" name="token" value="{{ old('token', $token) }}">

                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required>

                    <label for="password">New Password</label>
                    <input id="password" name="password" type="password" required>

                    <label for="password_confirmation">Confirm Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required>

                    <button type="submit">Update Password</button>
                </form>
            @endif
        </div>
    </div>
</body>
</html>
