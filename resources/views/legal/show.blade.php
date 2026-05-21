<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $document->title }} - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('branding/favicon.png') }}">
    <style>
        :root {
            color-scheme: light;
            --bg: #f4f7fb;
            --card: #ffffff;
            --surface: rgba(255, 255, 255, 0.72);
            --text: #0b1d43;
            --muted: #405372;
            --subtle: #73829c;
            --border: #d9e3f0;
            --brand: #1d63e8;
            --brand-dark: #1142b8;
            --shadow: 0 24px 60px rgba(17, 36, 77, 0.08);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: "Segoe UI", Arial, Helvetica, sans-serif;
            line-height: 1.6;
            background-image:
                radial-gradient(circle at top left, rgba(29, 99, 232, 0.09), transparent 35%),
                radial-gradient(circle at top right, rgba(17, 66, 184, 0.08), transparent 28%),
                linear-gradient(180deg, #f7faff 0%, #f4f7fb 100%);
        }

        .page-shell {
            width: 100%;
            padding: 24px 0 0;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 16px;
            margin-bottom: 20px;
            padding: 0 24px;
        }

        .logo {
            height: 34px;
            width: auto;
            display: block;
        }

        .card {
            background: var(--surface);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(217, 227, 240, 0.9);
            box-shadow: var(--shadow);
            border-radius: 24px 24px 0 0;
            overflow: hidden;
            width: 100%;
        }

        .card-header {
            padding: 42px 40px 32px;
            border-bottom: 1px solid rgba(217, 227, 240, 0.9);
            background:
                linear-gradient(180deg, rgba(255, 255, 255, 0.9) 0%, rgba(250, 252, 255, 0.82) 100%);
        }

        .eyebrow {
            margin: 0 0 12px;
            color: var(--brand);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        .title {
            margin: 0;
            font-size: 48px;
            line-height: 1.12;
            font-weight: 800;
            letter-spacing: -0.03em;
            max-width: 760px;
        }

        .summary {
            margin: 16px 0 0;
            max-width: 780px;
            font-size: 18px;
            color: var(--muted);
        }

        .updated {
            margin: 22px 0 0;
            font-size: 14px;
            color: var(--subtle);
        }

        .content {
            padding: 38px 40px 46px;
            color: var(--muted);
            background: rgba(255, 255, 255, 0.94);
        }

        .content h2,
        .content h3 {
            color: var(--text);
            line-height: 1.25;
            margin-top: 34px;
            margin-bottom: 14px;
            letter-spacing: -0.03em;
        }

        .content h2 {
            font-size: 34px;
        }

        .content h3 {
            font-size: 24px;
        }

        .content p,
        .content li,
        .content blockquote {
            font-size: 18px;
        }

        .content p,
        .content ul,
        .content ol,
        .content blockquote {
            margin: 0 0 18px;
        }

        .content ul,
        .content ol {
            padding-left: 26px;
        }

        .content a {
            color: var(--brand);
        }

        .content blockquote {
            padding: 14px 18px;
            border-left: 4px solid #bfdbfe;
            background: #eff6ff;
            border-radius: 12px;
        }

        @media (max-width: 640px) {
            .page-shell {
                padding: 16px 0 0;
            }

            .page-header {
                margin-bottom: 16px;
                padding: 0 16px;
            }

            .card-header,
            .content {
                padding: 24px 20px;
            }

            .title {
                font-size: 34px;
            }

            .summary {
                font-size: 16px;
            }

            .content h2 {
                font-size: 28px;
            }

            .content h3 {
                font-size: 22px;
            }

            .content p,
            .content li,
            .content blockquote {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <div class="page-header">
            <a href="/">
                <img src="{{ asset('branding/light-logo.png') }}" alt="{{ config('app.name') }}" class="logo">
            </a>
        </div>

        <article class="card">
            <div class="card-header">
                <p class="eyebrow">Community Will</p>
                <h1 class="title">{{ $document->title }}</h1>
                @if (filled($document->summary))
                    <p class="summary">{{ $document->summary }}</p>
                @endif
                <p class="updated">Last updated {{ optional($document->updated_at)->format('F j, Y') }}</p>
            </div>

            <div class="content">
                {!! $document->content !!}
            </div>
        </article>
    </div>
</body>
</html>
