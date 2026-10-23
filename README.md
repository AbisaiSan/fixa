# Fixa

Plataforma pessoal de estudo de idiomas com IA, começando pelo inglês e baseada nas aulas do seu professor.

Documentação: [produto e regras](docs/produto.md), [plano de desenvolvimento](docs/plano-desenvolvimento.md), [versões](docs/versoes.md), [decisões (ADR)](docs/adr/) e [protótipo](docs/design/prototipo/).

## Requisitos

- PHP 8.5.12 ou superior e Composer 2 (no macOS, pelo Herd)
- Node 24 ou superior e npm
- Docker
- gitleaks (`brew install gitleaks`)

## Subir o ambiente

Na primeira vez:

```bash
cp .env.example .env            # depois, no .env local, troque APP_DEBUG para true
composer install
npm ci
./vendor/bin/sail up -d         # sobe app, PostgreSQL, Redis, Mailpit e RustFS (S3)
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
npm run build
```

Nas próximas vezes, basta:

```bash
./vendor/bin/sail up -d
npm run dev                     # Vite com recarga automática (opcional)
```

| Serviço | Endereço |
| --- | --- |
| Aplicação | http://localhost:8080 |
| Mailpit (e-mails) | http://localhost:8025 |
| RustFS (console S3) | http://localhost:9001 (usuário `sail`, senha `password`) |
| PostgreSQL | `127.0.0.1:5432` (usuário `sail`, senha `password`) |
| Redis | `127.0.0.1:6379` |

Todas as portas ficam só em `127.0.0.1`. Para parar: `./vendor/bin/sail down`.

## Testes e verificações

```bash
composer test        # Pint, Larastan, Pest e Vitest (precisa do Sail no ar, por causa do PostgreSQL)
npm run check        # lint e formatação do frontend
npm run types:check  # tipos do frontend
composer audit && npm audit
gitleaks git         # busca de segredos no histórico
```

O CI (`.github/workflows/ci.yml`) roda tudo isso a cada push e pull request.
