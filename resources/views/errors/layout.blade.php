<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">
        <title>@yield('title') · {{ config('app.name') }}</title>
        <style nonce="{{ Vite::cspNonce() }}">
            body { margin: 0; min-height: 100vh; display: grid; place-items: center; font-family: system-ui, sans-serif; background: #fafafa; color: #1a1a1a; }
            main { max-width: 28rem; padding: 2rem; text-align: center; }
            h1 { font-size: 1.5rem; margin: 0 0 .75rem; }
            p { margin: 0 0 1.5rem; line-height: 1.5; color: #4a4a4a; }
            a { color: inherit; font-weight: 600; }
            @media (prefers-color-scheme: dark) { body { background: #141414; color: #f2f2f2; } p { color: #bdbdbd; } }
        </style>
    </head>
    <body>
        <main>
            <h1>@yield('title')</h1>
            <p>@yield('message')</p>
            <a href="{{ url('/') }}">Voltar para o início</a>
        </main>
    </body>
</html>
