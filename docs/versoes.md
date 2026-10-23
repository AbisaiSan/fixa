# Versões

Registro das versões em uso por ambiente (SEG-01, SEG-02 e SEG-03). Atualize este arquivo sempre que trocar a versão do PHP, do Laravel ou de uma imagem, e anote a data.

Última atualização: 23/10/2026.

## Avisos de segurança conferidos em 23/10/2026

| Componente | Aviso | Corrigido em | Situação |
| --- | --- | --- | --- |
| PHP | CVE-2026-109618 e CVE-2026-109619, execução remota de código (21/10/2026) | 8.5.12 e 8.4.27 | Corrigido no computador. **Pendente no container**, ver abaixo |
| Laravel | Auth bypass no guard de token (crítico, 19/10/2026) | 13.29.0 | Coberto pela 13.38.0 |
| Laravel | Destruição de dados não autorizada (médio, 19/10/2026) | 13.27.0 | Coberto |
| Laravel | XSS na página de depuração (baixo, 10/09/2026) | 13.30.0 | Coberto |
| Laravel | Confusão de caminho em URL assinada temporária (médio, 08/06/2026) | 13.12.0 | Coberto |
| Laravel | Injeção CRLF na regra de e-mail (alto, 01/06/2026) | 13.10.0 | Coberto |
| npm | Vite+ 0.3.0 do starter kit (Vite, Vitest, tinypool: 3 críticos e 4 moderados) | Vite+ 1.x | Atualizado para 1.3.0, `npm audit` sem vulnerabilidades |

Fontes: [php.net](https://www.php.net/releases/), [avisos do Laravel no GitHub](https://github.com/laravel/framework/security/advisories), [releases do Laravel](https://github.com/laravel/framework/releases).

## Computador de desenvolvimento (macOS)

| Ferramenta | Versão |
| --- | --- |
| PHP (Herd) | 8.5.12 |
| Composer | 2.10.2 |
| Node | 26.10.0 |
| npm | 11.19.1 |
| Docker | 29.8.2 |
| gitleaks | 8.30.1 |

O PHP 8.4.25, ainda instalado no Herd e vulnerável, não é mais o padrão. Atualize ou remova pelo Herd.

## Container do Sail (ambiente local)

| Serviço | Imagem | Versão |
| --- | --- | --- |
| App | `sail-8.5/app` (Ubuntu 24.04, PHP do repositório sury) | PHP **8.5.11**, Node 24.22.1 |
| Banco | `postgres:18-alpine` | PostgreSQL 18.6 |
| Cache e filas | `redis:alpine` | Redis 8.10.2 |
| E-mail | `axllent/mailpit:latest` | Mailpit 1.32.0 |
| Arquivos (S3) | `rustfs/rustfs:latest` | RustFS 1.0.3 |

**Pendência (SEG-03 e SEG-06):** em 23/10/2026 o repositório sury ainda não publicou o PHP 8.5.12, então o container roda o 8.5.11, vulnerável. Mitigação até lá: todas as portas publicadas só em `127.0.0.1` e nenhum dado real no ambiente. Assim que o pacote sair, reconstrua e confira a versão em execução:

```bash
./vendor/bin/sail build --no-cache && ./vendor/bin/sail up -d
./vendor/bin/sail php -v
```

Para conferir se o pacote já saiu: `./vendor/bin/sail root-shell -c "apt-get update -qq && apt-cache policy php8.5-cli"`.

## CI (GitHub Actions)

| Ferramenta | Versão |
| --- | --- |
| PHP | 8.5 (última corrigida, via setup-php) |
| Node | 24 |
| PostgreSQL | 18 (`postgres:18-alpine`) |

## Aplicação

| Pacote | Versão | Restrição |
| --- | --- | --- |
| laravel/framework | 13.38.0 | `^13.38` |
| PHP exigido pelo `composer.json` | | `^8.5` |
| inertiajs/inertia-laravel | 3.5.2 | |
| laravel/fortify | 1.41.0 | |
| laravel/sail | 1.69.0 | |
| laravel/wayfinder | 0.1.23 | |
| pestphp/pest | 5.3.3 | |
| larastan/larastan | 3.13.1 | |
| laravel/pint | 1.32.1 | |
| vue | 3.5.44 | |
| @inertiajs/vue3 | 3.9.1 | |
| tailwindcss | 4.3.3 | |
| typescript | 5.9.3 | |
| reka-ui (base do shadcn-vue) | 2.11.0 | |
| vite-plus (Vite, Vitest, Oxlint e Oxfmt) | 1.3.0 | exata |
| vitest | 5.0.5 | |
| vue-tsc | 3.3.13 | `~3.3.0` |
| @fontsource/plus-jakarta-sans | 5.3.0 | exata |
| @fontsource/gloock | 5.3.0 | exata |
| @fontsource/source-serif-4 | 5.3.1 | exata |
| @fontsource-variable/material-symbols-rounded | 5.3.9 | exata |
| @vue/test-utils | 2.5.1 | exata |
| jsdom | 30.1.3 | exata |
| axe-core | 4.14.0 | exata |
