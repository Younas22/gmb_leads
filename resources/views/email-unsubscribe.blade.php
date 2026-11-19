<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Unsubscribe</title>
    <style>
        :root { --orange: #F97316; --bg: #FFF7ED; --text: #111827; --muted: #6b7280; }
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: var(--bg); color: var(--text); }
        main { max-width: 440px; margin: 12vh auto; padding: 0 16px; }
        .card { background: #fff; border: 1px solid #fed7aa; border-radius: 16px; padding: 28px; }
        h1 { font-size: 20px; margin: 0 0 8px; }
        p { color: var(--muted); line-height: 1.5; margin: 0 0 16px; }
        button { width: 100%; padding: 12px 16px; border: 0; border-radius: 10px; background: var(--orange); color: #fff; font-weight: 600; font-size: 15px; cursor: pointer; }
        button:hover { background: #ea580c; }
    </style>
</head>
<body>
<main>
    <div class="card">
        @if($done)
            <h1>You're unsubscribed</h1>
            <p>{{ $masked }} will not receive further emails from this sender through CustomerNearMe.</p>
        @else
            <h1>Unsubscribe?</h1>
            <p>Stop emails from this sender to {{ $masked }}.</p>
            <form method="POST" action="{{ request()->fullUrl() }}">
                <button type="submit">Unsubscribe</button>
            </form>
        @endif
    </div>
</main>
</body>
</html>
