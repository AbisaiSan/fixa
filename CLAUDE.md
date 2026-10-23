# Fixa

Plataforma pessoal de estudo de idiomas com IA (começando pelo inglês), baseada nas aulas do professor do aluno. Monolito modular em Laravel, desenvolvido localmente por uma pessoa.

## Documentos de referência

- `docs/produto.md`: produto e regras de negócio. Os códigos de regra (MAT, REV, FLA, VOC, ATV, ERR, PLA, GAM, NIV, SEG, ACE, DAD, UPL, IAS, OPE) vêm daqui.
- `docs/plano-desenvolvimento.md`: jornadas, tarefas (IDs como J2-B3), critérios de pronto e segurança por jornada.
- `docs/versoes.md`: versões em uso por ambiente e avisos de segurança conferidos.
- `docs/adr/`: decisões de arquitetura. Toda decisão nova vira um ADR curto e numerado.
- `docs/design/prototipo/`: protótipo do Claude Design, somente leitura.

## Stack

PHP 8.5 e Laravel 13 (versões mínimas no `composer.json`), Vue 3 com Inertia, TypeScript, Tailwind 4, shadcn-vue (reka-ui), Vite+ (Vite, Vitest, Oxlint e Oxfmt), PostgreSQL 18, Redis, Mailpit e RustFS (S3 local), tudo pelo Laravel Sail. Autenticação pelo starter kit (Fortify, sessão com cookie).

## Comandos

```bash
./vendor/bin/sail up -d              # sobe o ambiente (app em http://localhost:8080)
./vendor/bin/sail artisan <comando>  # artisan dentro do container
npm run dev                          # Vite no host
npm run build
composer test                        # Pint, Larastan, Pest e Vitest (Sail precisa estar no ar)
composer test:php                    # só Pest
composer test:js                     # só Vitest
composer lint                        # corrige o estilo PHP (Pint)
npm run check:fix                    # corrige lint e formatação do frontend
npm run types:check
composer audit && npm audit
gitleaks git                         # antes de cada commit
```

Os comandos `npm` e o `composer test` rodam no host, não no container: o Vite+ tem binário nativo do macOS, e o Pest acessa o PostgreSQL do Sail por `127.0.0.1`.

## Estrutura

- `app/Domain/{Lessons,Cards,Vocabulary,Practice,Plan,Progress,Ai}`: um domínio por pasta, com Ações e DTOs. Só o domínio `Ai` fala com provedores externos, sempre pela interface `AiProvider`.
- `resources/js`: frontend Vue (páginas Inertia em `pages/`, componentes em `components/`, shadcn-vue em `components/ui/`).
- `tests/`: Pest (`Feature`, `Unit`). Testes Vitest ficam ao lado do arquivo testado (`*.test.ts`).
- `tests/aulas-reais/`: aulas reais para avaliar a IA à mão. Pasta ignorada pelo Git, nunca versionar.

## Convenções

- Código, nomes de classes, variáveis e mensagens de commit em inglês. Textos de interface, documentação e comentários explicativos em pt-BR. Não use travessão em textos, use vírgula.
- Commits pequenos no padrão convencional (`feat`, `fix`, `chore`, `docs`, `ci`, `test`, `refactor`), citando o ID da tarefa e as regras quando houver.
- Branch principal `main`. Uma branch por jornada.
- Nada de "inglês" fixo no código: o idioma estudado é parâmetro (`targetLang`).
- Dependência nova só com justificativa (mantida, popular, necessária), registrada no commit (SEG-05). Versões travadas por `composer.lock` e `package-lock.json`. O gerenciador JS é o npm.
- Horários em UTC, na aplicação e na conexão com o PostgreSQL.

## Padrões de dados e segurança da aplicação

- **ULID público:** modelos expostos ao aluno usam `HasPublicUlid` (coluna `ulid`, chave de rota). O `id` numérico é interno e nunca aparece em URL, JSON ou tela.
- **Dono do dado:** todo modelo de conteúdo usa `BelongsToUser` e tem `user_id`. O escopo global filtra pelo aluno autenticado e, sem login, não retorna nada. Em filas e comandos use `Modelo::forUser($user)`. Nunca coloque `user_id` em `$fillable`, e nunca remova o escopo `OwnedByAuthenticatedUser` fora de um ponto revisado.
- **Matriz de acesso entre alunos:** todo endpoint que recebe dado de aluno entra em `tests/Support/OwnedEndpoints.php`. O `CrossStudentAccessTest` tenta ler, alterar e apagar como outro aluno e falha se uma rota com modelo `BelongsToUser` ficar de fora. Policies de conteúdo estendem `App\Policies\OwnedModelPolicy`.
- **Papéis:** `Role::Student` e `Role::Admin`; admin só pelo comando `fixa:admin` e sempre com 2FA. O admin não lê conteúdo de aluno.
- **Cabeçalhos e CSP:** `App\Http\Middleware\SecurityHeaders`. Script inline só com `nonce="{{ Vite::cspNonce() }}"`. Origem externa nova (fonte, CDN, API no navegador) exige mudança na CSP com justificativa.
- **Logs:** canais em JSON com `request_id` e o ULID do usuário. O processador `RedactSensitiveData` apaga chaves como `password`, `token`, `text`, `content`, `answer`, `material` e `prompt`, e reduz exceções a classe e local. Ainda assim, nunca passe texto do aluno para o log; registre IDs.
- **Auditoria:** `app(AuditLog::class)->record('acao.em_ingles', $user, [...metadados])` para login, exclusão, exportação e ações de administrador. Só IDs e metadados escalares.
- **Erros:** páginas genéricas em `resources/views/errors/`, em pt-BR e sem detalhes.
- **Rotina semanal:** `docs/seguranca/rotina-semanal.md`.

## Frontend e sistema de design

- **Tokens:** `resources/css/tokens.css` (cores, sombras, espaço, tipo, movimento) e `app.css` (raio e curva em `@theme static`, cores em `@theme inline`). Use as utilidades dos tokens (`bg-sf`, `text-ink2`, `text-prid`, `rounded-l`, `shadow-sh1`, `type-title`, `type-target`), nunca cor fixa. `--pri` é só para anéis e barras; texto e botões usam `--prid`. `--ink3` é decorativo.
- **Tema:** `data-theme="light|dark"` no `<html>`; "Automático" segue o sistema. Variante `dark:` do Tailwind segue o atributo.
- **Fontes:** servidas pelo app via `@fonts` (Fontsource travado no `package-lock.json`). Nunca CDN: a CSP bloqueia.
- **Componentes do produto:** `resources/js/components/fixa/` (Button, TextField, Select, Checkbox, Switch, Card, StatusChip, OriginTag, Toast, Banner, Skeleton, EmptyState, Sheet, Icon, TargetText, AppShell). `components/ui/` é o shadcn-vue do kit, usado pelas telas do kit até a J1.
- **Ícones:** `<Icon name="today" />` (Material Symbols Rounded, sempre `aria-hidden`); `filled` para o estado ativo. Ícone nunca é o único portador de significado.
- **Textos:** toda string de interface em `resources/js/i18n/pt-BR.ts`, via `t('chave')`. Idioma estudado só por `useTargetLang()`; conteúdo nesse idioma sempre dentro de `<TargetText>`.
- **Catálogo:** `http://localhost:8080/dev/componentes` (só com `APP_ENV=local`). Componente novo ou alterado entra no catálogo e é conferido nos dois temas contra o protótipo.
- **Testes de componente:** `*.test.ts` ao lado do componente, com `@vue/test-utils` e `expectAccessible()` (axe) de `resources/js/test/axe.ts`. Contraste é testado em `resources/js/test/tokens.test.ts`; par de tokens novo usado em texto entra lá.

## Regras de segurança (nunca quebrar)

- Nunca commitar `.env`, chaves, tokens, dumps nem material real de aulas. Segredos só em variáveis de ambiente (DAD-04). Não peça nem aceite chaves de API no chat.
- `APP_DEBUG=false` fora do ambiente local (OPE-02). Fora da produção, só dados fictícios (DAD-05).
- Todo dado pertence a um aluno: toda consulta filtrada pelo dono por Policy, e todo endpoint novo entra na matriz de teste de acesso entre alunos (ACE-04).
- Nada de `v-html`, `{!! !!}` ou saída de IA sem escape (IAS-02, OPE-01). O texto do material do aluno é dado, nunca instrução (IAS-01).
- Logs, eventos e exceções sem conteúdo do aluno (IAS-04). Sem `dd()` ou `dump()` no diff.
- Sem `$guarded = []`, e validação em toda entrada.
- Testes automáticos nunca chamam a IA de verdade, só o adaptador falso.
- PHP e Laravel sempre na versão corrigida mais recente. Depois de atualizar o PHP, reconstruir as imagens e reiniciar os serviços (SEG-03, SEG-06), e atualizar `docs/versoes.md`.
- `composer audit`, `npm audit` e gitleaks limpos antes de cada commit. Vulnerabilidade crítica nunca é ignorada sem avisar o responsável.

## Protótipo

`docs/design/prototipo/` traz o sistema de design, o protótipo navegável e as telas das fases 2 e 3, com capturas em `screenshots/`. Os arquivos `.dc.html` usam um formato de template próprio do Claude Design: use-os como referência de tela, texto, rota e comportamento, mas nunca copie esse código para o Vue. Os dados de exemplo (aula de Present Perfect, cartões, erros, semana) viram seeders e fixtures de teste. Não edite nada nessa pasta.

## Como trabalhar por jornada

1. Abra a tarefa pelo ID e pelas regras (ex.: "Tarefa J3-B3, regras FLA-03 e FLA-04") e proponha um plano de até 10 linhas antes de qualquer código.
2. Nas regras puras (repetição espaçada, plano, XP), escreva os testes primeiro e veja-os falhar.
3. Implemente só o que a tarefa pede, sem tocar em outras pastas.
4. Rode `composer test`, `composer audit` e `npm audit` e liste os arquivos alterados.
5. Revise o diff com a lista de segurança do plano (seção "Como trabalhar com o Claude no terminal").
6. Atualize este arquivo e a documentação quando uma convenção mudar.
