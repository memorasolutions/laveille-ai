<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - {{ config('app.name') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            color: #334155;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .error-container {
            text-align: center;
            padding: 2rem;
        }
        .error-code {
            font-size: 6rem;
            font-weight: 800;
            color: #e2e8f0;
            line-height: 1;
        }
        .error-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 1rem 0 0.5rem;
        }
        .error-message {
            color: #64748b;
            margin-bottom: 2rem;
        }
        .error-link {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #3b82f6;
            color: #fff;
            text-decoration: none;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: background 0.2s;
        }
        .error-link:hover { background: #2563eb; }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">@yield('code')</div>
        <h1 class="error-title">@yield('title')</h1>
        <p class="error-message">@yield('message')</p>
        <a href="{{ url('/') }}" class="error-link">{{ __('Retour à l\'accueil') }}</a>
    </div>
</body>
</html>
