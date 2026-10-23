# ADR 0001: Stack da aplicação

- Data: 23/10/2026
- Situação: aceita
- Tarefas: J0-B1, J0-B2, J0-B4

## Contexto

O Fixa é desenvolvido por uma pessoa, localmente, com o Claude no terminal. O produto precisa nascer seguro (SEG, ACE, DAD, OPE), testável e capaz de crescer do uso local até a venda sem reescrita. O plano (`docs/plano-desenvolvimento.md`) assume um monolito modular em Laravel com Vue.

## Decisão

- **Backend:** PHP 8.5 (8.5.12 ou superior) e Laravel 13 (13.38.0 ou superior), como monolito modular com domínios em `app/Domain`. Escolhemos o PHP 8.5 em vez do 8.4 pelo suporte mais longo.
- **Frontend:** starter kit oficial do Laravel com Vue 3, Inertia, TypeScript, Tailwind 4 e componentes shadcn-vue (sobre reka-ui). A autenticação é a do kit (Fortify, sessão com cookie), conforme ACE-01.
- **Ferramentas de frontend:** Vite+ (Vite, Vitest, Oxlint e Oxfmt num único pacote), que já vem no kit. Atualizamos da 0.3.0 para a 1.3.0 porque a versão do kit tinha 3 vulnerabilidades críticas.
- **Dados e serviços:** PostgreSQL 18, Redis (cache e filas), Mailpit (e-mail local) e RustFS como armazenamento compatível com S3, todos pelo Laravel Sail.
- **Qualidade:** Pest (com PostgreSQL real), Pint, Larastan nível 7, Vitest, `composer audit`, `npm audit` e gitleaks, no GitHub Actions.
- **Gerenciador de pacotes JS:** npm, com `package-lock.json`. Removemos o `pnpm-workspace.yaml` do kit para evitar ambiguidade.

## Desvio do plano: RustFS no lugar do MinIO

O plano previa MinIO. O Sail 1.69 não oferece mais o MinIO, cuja imagem pública deixou de receber correções em 2025, e passou a usar o RustFS. O app acessa os arquivos sempre pelo disco `s3` do Laravel, então trocar por MinIO, S3 ou outro serviço compatível é só configuração de `.env`. O driver `league/flysystem-aws-s3-v3` entra na J2, quando o upload for implementado.

## Consequências

- O PHP do container depende do repositório sury, que pode atrasar correções. Em 23/10/2026 ele ainda não tinha o 8.5.12, ver `docs/versoes.md`. Se o atraso se repetir, avaliar um Dockerfile próprio sobre a imagem oficial `php`.
- O Vite+ é recente e muda rápido. As versões ficam exatas no `package.json` e passam por `npm audit` em todo commit.
- O Vite+ tem binário nativo, então os comandos de frontend (`npm run ...`) e o `composer test` rodam no computador, não dentro do container.
