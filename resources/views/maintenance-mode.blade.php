{{--
    This view is prerendered to static HTML the moment maintenance mode is turned on (see
    App\Http\Controllers\Admin\MaintenanceController::enable()) and served from that static copy for every
    visitor while the site is down, without booting the rest of the application. Everything it needs is
    therefore read here, once, from config() and from AppSettings — never from the current request.
--}}
@php
    $site = app(\App\Services\AppSettings::class)->site();
    $title = (string) config('maintenance_page.title', 'We will be right back');
    $message = (string) config('maintenance_page.message', '');
    $expectedBackAt = config('maintenance_page.expected_back_at');
    $expectedBackLabel = null;

    if (filled($expectedBackAt)) {
        try {
            $expectedBackLabel = \Illuminate\Support\Carbon::parse($expectedBackAt)->translatedFormat('l, j F Y \a\t g:i A');
        } catch (\Throwable) {
            $expectedBackLabel = null;
        }
    }
@endphp
<!DOCTYPE html>
<html lang="en" style="color-scheme: light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} · {{ $site['name'] }}</title>
    @if ($site['favicon_url'])
        <link rel="icon" href="{{ $site['favicon_url'] }}">
    @endif
    <style>
        :root {
            --ink: #0a1018;
            --sky: #76d8f8;
            --brand: #ed2948;
            --brand-deep: #bb1831;
            --brand-soft: #ffe3e8;
            --canvas: #edf7fa;
            --line: #d4e7ed;
            --muted: #526475;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            background: var(--canvas);
            color: var(--ink);
            font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
        }

        header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 18px 22px;
            background: var(--ink);
            color: #fff;
            border-bottom: 3px solid var(--brand);
        }

        .logo {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, var(--sky), var(--brand));
            font-weight: 900;
            color: var(--ink);
            flex-shrink: 0;
            overflow: hidden;
        }

        .logo img { width: 100%; height: 100%; object-fit: cover; }

        header b { font-size: 15px; }

        main {
            flex: 1;
            display: grid;
            place-items: center;
            padding: 32px 18px;
        }

        .card {
            max-width: 560px;
            width: 100%;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 24px;
            padding: 40px 34px;
            text-align: center;
            box-shadow: 0 24px 60px rgba(6, 23, 35, 0.12);
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 999px;
            background: var(--brand-soft);
            color: var(--brand-deep);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .pill::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--brand);
            animation: pulse 1.6s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.35; }
        }

        h1 {
            margin: 18px 0 10px;
            font-size: clamp(26px, 4vw, 34px);
            font-weight: 900;
            letter-spacing: -0.5px;
        }

        p.message {
            margin: 0 auto;
            max-width: 440px;
            color: var(--muted);
            line-height: 1.7;
        }

        .back {
            margin-top: 22px;
            display: inline-block;
            padding: 12px 20px;
            border-radius: 14px;
            background: var(--canvas);
            border: 1px solid var(--line);
            font-weight: 700;
            font-size: 14px;
        }

        .back b { color: var(--ink); }

        footer {
            padding: 18px;
            text-align: center;
            font-size: 12px;
            color: var(--muted);
        }
    </style>
</head>
<body>
<header>
    <span class="logo">
        @if ($site['logo_url'])
            <img src="{{ $site['logo_url'] }}" alt="">
        @else
            {{ \Illuminate\Support\Str::substr($site['name'], 0, 1) }}
        @endif
    </span>
    <b>{{ $site['name'] }}</b>
</header>

<main>
    <div class="card">
        <span class="pill">Maintenance mode</span>
        <h1>{{ $title }}</h1>
        @if ($message !== '')
            <p class="message">{{ $message }}</p>
        @endif
        @if ($expectedBackLabel)
            <div class="back">Expected back: <b>{{ $expectedBackLabel }}</b></div>
        @endif
    </div>
</main>

<footer>{{ $site['footer_text'] ?: $site['name'] }}</footer>
</body>
</html>
