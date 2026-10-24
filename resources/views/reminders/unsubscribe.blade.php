<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">
        <title>Lembrete diário · {{ config('app.name') }}</title>
        <style nonce="{{ Vite::cspNonce() }}">
            body { margin: 0; min-height: 100vh; display: grid; place-items: center; font-family: system-ui, sans-serif; background: #fafafa; color: #1a1a1a; }
            main { max-width: 28rem; padding: 2rem; text-align: center; }
            h1 { font-size: 1.5rem; margin: 0 0 .75rem; }
            p { margin: 0 0 1.5rem; line-height: 1.5; color: #4a4a4a; }
            a { color: inherit; font-weight: 600; }
            button { min-height: 48px; padding: 0 1.5rem; border: 0; border-radius: 14px; background: #1a1a1a; color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
            button:focus-visible { outline: 3px solid #4a7dff; outline-offset: 2px; }
            @media (prefers-color-scheme: dark) { body { background: #141414; color: #f2f2f2; } p { color: #bdbdbd; } button { background: #f2f2f2; color: #141414; } }
        </style>
    </head>
    <body>
        <main>
            @if ($done)
                <h1>Lembrete desativado</h1>
                <p>Você não vai mais receber o lembrete diário. Dá para ligar de novo em Configurações quando quiser.</p>
                <a href="{{ url('/') }}">Ir para o Fixa</a>
            @else
                <h1>Parar o lembrete diário?</h1>
                <p>Você deixa de receber o e-mail que lembra do plano de hoje. Seu plano e sua sequência continuam iguais.</p>
                <form method="POST" action="{{ $action }}">
                    @csrf
                    <button type="submit">Não quero mais receber</button>
                </form>
            @endif
        </main>
    </body>
</html>
