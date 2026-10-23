# Rotina semanal de segurança

Toda segunda-feira (SEG-04 e SEG-06). Leva de 15 a 30 minutos. Correção crítica entra em até 48 horas, ou no mesmo dia se houver servidor exposto à internet.

O workflow `security-weekly` roda `composer audit`, `npm audit` e gitleaks automaticamente toda segunda às 8h (horário de Brasília), e o Dependabot abre PRs semanais para Composer, npm e GitHub Actions. Esta lista cobre o que a automação não vê.

## Checklist

### 1. Automação
- [ ] O workflow `security-weekly` da semana passou. Se falhou, abrir o log e tratar antes do resto.
- [ ] Revisar os PRs do Dependabot: ler o changelog, rodar `composer test` localmente e fazer o merge dos que passarem. Atualização de segurança tem prioridade.

### 2. PHP
- [ ] Conferir a última versão de 8.5 em [php.net/releases](https://www.php.net/releases/) e os avisos em [github.com/php/php-src/security/advisories](https://github.com/php/php-src/security/advisories).
- [ ] Computador: `php -v`. Se houver versão nova, atualizar pelo Herd e reiniciar (`herd restart`).
- [ ] Container: `./vendor/bin/sail php -v`. Se houver versão nova no sury, `./vendor/bin/sail build --no-cache && ./vendor/bin/sail up -d` e conferir de novo a versão **em execução** (SEG-03).
- [ ] Servidores, quando existirem: reconstruir a imagem, reimplantar e reiniciar PHP-FPM, servidor web e workers de fila.

### 3. Laravel e pacotes
- [ ] Avisos em [github.com/laravel/framework/security/advisories](https://github.com/laravel/framework/security/advisories) e nos pacotes principais (Fortify, Inertia, Sail).
- [ ] `composer audit` e `npm audit` locais limpos.
- [ ] `composer outdated --direct` e `npm outdated`: anotar o que está muito atrasado.

### 4. Imagens e serviços
- [ ] Atualizar as imagens dos serviços: `./vendor/bin/sail pull && ./vendor/bin/sail up -d` (PostgreSQL, Redis, Mailpit, RustFS).
- [ ] Docker Desktop e macOS com atualizações de segurança aplicadas.

### 5. Repositório e contas
- [ ] GitHub: dois fatores ativos, nenhum token pessoal sem uso ou sem validade, alertas de segurança do repositório zerados (SEG-07).
- [ ] Nenhum segredo novo no histórico: `gitleaks git`.

### 6. Registro
- [ ] Atualizar `docs/versoes.md` com as versões em uso e a data.
- [ ] Se houve aviso crítico, seguir o "Procedimento para aviso crítico" do plano e registrar um ADR com a causa, o tempo até a correção e o que muda no processo.
- [ ] Se um servidor ficou exposto sem correção, trocar as chaves dele (DAD-04).

## Histórico

| Data | Quem | Resultado | Ações |
| --- | --- | --- | --- |
| 23/10/2026 | Configuração inicial | PHP 8.5.12 no computador; container com 8.5.11 aguardando o sury | Ver `docs/versoes.md` |
