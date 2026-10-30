# Guia de deploy: Fixa na Oracle Cloud (homologação)

Oct 30, 2026 · @Abisai · revisado em 30/10/2026 contra o código do Fixa (ADR 0006, tarefa J6-B11)

## Progresso

Você faz as partes de navegador (contas, VM, domínio, Cloudflare). A parte do servidor eu faço por SSH, com os scripts de `deploy/hml/`, e confiro cada passo antes de marcar.

| # | Passo | Quem | Situação |
| --- | --- | --- | --- |
| 1 | Conta na Oracle Cloud | você | ⬜ |
| 2 | Chave SSH e VM ARM | você | ⬜ |
| 3 | Atalho `ssh fixa-hml` e primeiro acesso | você, eu confiro | ⬜ |
| 4 | Preparar o servidor (`provision.sh`) | eu | ⬜ |
| 5 | Primeiro deploy (`deploy.sh`) | eu | ⬜ |
| 6 | Domínio na Cloudflare | você | ⬜ |
| 7 | Túnel da Cloudflare | você, eu confiro | ⬜ |
| 8 | Proteção (Access) e publicação do endereço | você, eu confiro | ⬜ |
| 9 | E-mail (Resend) | você, eu configuro | ⬜ |
| 10 | IA (Gemini), opcional | você | ⬜ |
| 11 | Teste completo e checklist de segurança | nós dois | ⬜ |

## O que mudou em relação à primeira versão

O guia original era genérico para Laravel. Para o Fixa funcionar, estes pontos foram corrigidos:

| Ponto | Antes | Agora | Por quê |
| --- | --- | --- | --- |
| PHP | 8.3 do Ubuntu | **8.5** do repositório do Ondřej Surý | O `composer.json` exige `^8.5`, e só a 8.5.12 em diante corrige o "PHP2Shell" (docs/versoes.md) |
| PostgreSQL | 16 do Ubuntu | **18** do repositório oficial (PGDG) | É a versão do projeto, e as migrations usam `pg_trgm` e `unaccent` |
| Redis | não tinha | **instalado**, só em 127.0.0.1 | Fila e cache do Fixa usam Redis |
| Antivírus | não tinha | **ClamAV** em 127.0.0.1:3310 | Todo PDF enviado passa pelo antivírus (UPL-03). Sem ele, o upload falha |
| PDF | não tinha | **poppler-utils** | A extração usa `pdfinfo` e `pdftotext` |
| Limite de upload | 2 MB (padrão do PHP) | 12 MB no PHP e 14 MB no Nginx | O Fixa aceita PDF de até 10 MB |
| Aviso de segurança do PHP | não tinha | `session.upload_progress.enabled = 0` | Mesma mitigação do container (CVE-2026-115560) |
| Fila e agendador | "só se o app usa" | **obrigatórios**, como serviços do systemd | Processamento das aulas, plano semanal e lembretes |
| `APP_ENV` | `production` | **`staging`** | É homologação, com dados fictícios (DAD-05) |
| Fuso da VM | America/Fortaleza | **UTC** (padrão) | Convenção do projeto: horários em UTC |
| Envio do código | `rsync` da pasta inteira | `git archive` do commit + `public/build` | O rsync levaria arquivos ignorados, como `tests/aulas-reais` (material real) e o `.env` |
| Rota do túnel | `http://localhost:80` | **`http://127.0.0.1:80`** | O Nginx escuta só em 127.0.0.1; `localhost` pode ir para IPv6 e falhar |
| IP do visitante | sempre 127.0.0.1 | `CF-Connecting-IP` no Nginx | Rate limit e auditoria precisam do IP real |
| E-mail | não tinha | **Resend** (gratuito) | O Fixa exige e-mail verificado para entrar, e envia lembretes |
| Cloudflare | padrão | desligar **Email Address Obfuscation** | Ela reescreve e-mails no HTML e corrompe os dados da página (Inertia) |
| Backup | na pasta do usuário `ubuntu` | `/var/backups/fixa`, só o usuário `postgres` lê | Dump fora da pasta do app, sem acesso de outros usuários |

## Visão geral

```text
Navegador ──HTTPS──▶ Cloudflare Access (login por código no e-mail)
                         │
                         ▼
                  Cloudflare Tunnel ◀── conexão de saída aberta pela VM
                         │
VM Oracle (Ubuntu 24.04 ARM) ──▶ Nginx 127.0.0.1:80 ──▶ PHP-FPM 8.5 (Laravel)
                                  PostgreSQL 18, Redis, ClamAV, fila e agendador, todos locais
```

Nenhuma porta web fica aberta na Oracle. Só o SSH (porta 22, com chave).

**Custo esperado:** US$ 0 com a Oracle Always Free, a Cloudflare Free e o Resend Free. O único gasto provável é o domínio, se você ainda não tiver um (registro.br, valor anual).

**Dados:** homologação só com dados fictícios (DAD-05). Não envie aulas reais nem material com dados pessoais.

## Antes de começar

- [ ] Cartão de crédito ou débito (a Oracle usa só para confirmar identidade)
- [ ] Um domínio seu, ou a decisão de registrar um (Passo 6)
- [ ] Conta na Cloudflare (gratuita)
- [ ] Os e-mails de quem vai testar, incluindo o seu

## Passo 1: conta na Oracle Cloud (você)

1. Abra [oracle.com/cloud/free](https://www.oracle.com/cloud/free/) e clique em **Start for free**.
2. Informe país (Brazil), nome e e-mail, confirme o captcha e clique em **Verify my email**.
3. Abra o e-mail e clique no link de verificação.
4. Crie a senha e preencha endereço e telefone.
5. Em **Home Region**, escolha **Brazil East (Sao Paulo)**. Não dá para trocar depois, e o Always Free só vale na home region.
6. Informe o cartão. Se for recusado, tente outro.
7. Aguarde o e-mail de boas-vindas e entre em [cloud.oracle.com](https://cloud.oracle.com) com o **Cloud Account Name** que veio nele.
8. Ative a verificação em duas etapas (MFA) quando pedir.

**Me avise quando a conta estiver ativa.**

## Passo 2: chave SSH e VM ARM (você)

**Crie a chave no Mac** (Terminal):

```bash
ssh-keygen -t ed25519 -f ~/.ssh/fixa_hml -C "fixa-hml"
cat ~/.ssh/fixa_hml.pub
```

Defina uma senha para a chave quando pedir (recomendado). Copie todo o texto que o `cat` mostrar. É a chave **pública**; a privada (`~/.ssh/fixa_hml`, sem `.pub`) nunca sai do Mac.

**Crie a VM** no console da Oracle:

1. Menu (☰), **Compute**, **Instances**, **Create instance**.
2. **Name:** `fixa-hml`.
3. **Image and shape**, **Edit**:
   - **Change image:** **Canonical Ubuntu 24.04** (não a Minimal).
   - **Change shape:** **Ampere**, **VM.Standard.A1.Flex**, **2 OCPUs** e **12 GB**. Confira o selo **Always Free-eligible**.
4. **Networking:** mantenha a rede padrão (na primeira vez, deixe criar uma) e confirme **Assign a public IPv4 address** ativado.
5. **Add SSH keys:** **Paste public keys** e cole a chave pública.
6. **Boot volume:** mantenha 50 GB.
7. **Create**. Espere o status **Running** e copie o **Public IP address**.

**"Out of host capacity":** falta temporária de VMs gratuitas na região. Tente outro domínio de disponibilidade, espere e tente de novo, ou migre a conta para Pay As You Go (veja "Evitar que a Oracle pare a VM" no fim).

Não abra as portas 80 e 443. O acesso web passa pelo túnel.

**Me mande só o IP público.**

## Passo 3: atalho SSH e primeiro acesso (você, eu confiro)

No Mac, abra o arquivo de configuração do SSH:

```bash
chmod 600 ~/.ssh/fixa_hml
nano ~/.ssh/config
```

Cole o bloco abaixo trocando `IP_PUBLICO`, salve com Ctrl + O e Enter, e saia com Ctrl + X:

```text
Host fixa-hml
  HostName IP_PUBLICO
  User ubuntu
  IdentityFile ~/.ssh/fixa_hml
  IdentitiesOnly yes
```

Teste:

```bash
ssh fixa-hml
```

Na primeira vez, digite `yes`. Quando aparecer `ubuntu@fixa-hml:~$`, saia com `exit`.

Se a chave tem senha, rode `ssh-add --apple-use-keychain ~/.ssh/fixa_hml` uma vez para eu conseguir usar o atalho daqui.

**Opcional: SSH só do seu IP.** Só se o IP da sua internet for fixo. Descubra em [ifconfig.me](https://ifconfig.me), depois vá em ☰, **Networking**, **Virtual cloud networks**, sua rede, a sub-rede pública, **Default Security List**, e troque o **Source CIDR** da regra da porta 22 de `0.0.0.0/0` para `SEU_IP/32`.

**Me avise. Eu entro pelo atalho e confiro a VM.**

## Passo 4: preparar o servidor (eu)

Eu envio a pasta `deploy/hml/` e rodo o `provision.sh` na VM. Ele instala e configura:

- PHP 8.5 (FPM e CLI) com as extensões do Fixa e o `deploy/hml/php.ini`
- PostgreSQL 18 com o papel `fixa` e o banco `fixa_hml`; a senha é gerada na VM e vai direto para o `.env`, sem aparecer na tela
- Redis, ClamAV (com a base de vírus) e poppler-utils, todos só locais
- Nginx em 127.0.0.1:80 (`deploy/hml/nginx.conf`)
- Serviços `fixa-queue` e `fixa-scheduler`
- Backup diário do banco às 3h UTC, guardando 14 dias
- O `.env` com `APP_ENV=staging`, `APP_DEBUG=false` e a IA no adaptador falso

Você não precisa fazer nada aqui.

## Passo 5: primeiro deploy (eu)

No Mac, eu rodo `deploy/hml/deploy.sh`. Ele:

1. Recusa publicar se houver mudança sem commit.
2. Gera o front-end (`npm run build`).
3. Envia só os arquivos do commit atual mais o `public/build`.
4. Na VM: `composer install --no-dev`, `key:generate` na primeira vez, `migrate`, `optimize` e reinício da fila e do agendador.

Depois eu confiro o app dentro da VM (`curl http://127.0.0.1/up`) e os serviços.

Para atualizações futuras é o mesmo comando: `deploy/hml/deploy.sh`.

## Passo 6: domínio na Cloudflare (você)

### 6.1 Domínio

- **Já tem um domínio:** use um subdomínio de um nível, como `hml.seudominio.com.br`. Níveis extras (`a.hml.seudominio.com.br`) exigem certificado pago.
- **Não tem:** registre em [registro.br](https://registro.br) (`.com.br`, com CPF).

### 6.2 Colocar o domínio na Cloudflare

1. Crie a conta em [dash.cloudflare.com/sign-up](https://dash.cloudflare.com/sign-up).
2. **Add a domain**, informe o domínio (sem `www` e sem subdomínio) e escolha o plano **Free**.
3. **Se o domínio já tem site ou e-mail funcionando, confira se todos os registros DNS foram importados.**
4. Copie os dois nameservers que a Cloudflare mostrar.
5. No Registro.br: **Meus domínios**, o domínio, **DNS**, **Alterar servidores DNS**, e cole os dois.
6. Aguarde o domínio ficar **Active** na Cloudflare (minutos a horas).

### 6.3 Ajustes que o Fixa precisa

No painel do domínio, use a busca do topo para achar cada opção:

- **Email Address Obfuscation:** **desligado**. Ela reescreve e-mails no HTML e quebra os dados da página do Fixa.
- **Rocket Loader:** **desligado** (já vem assim). Ele injeta script, e a CSP do Fixa bloqueia.
- **SSL/TLS, modo de criptografia:** **Full**. O túnel já é criptografado, e isso evita o aviso.

**Me avise com o subdomínio escolhido** (ex.: `hml.seudominio.com.br`). Eu coloco no `APP_URL`.

## Passo 7: túnel da Cloudflare (você, eu confiro)

1. No painel: **Zero Trust** (na primeira vez, escolha o plano **Free**), **Networks**, **Tunnels**.
2. **Create a tunnel**, tipo **Cloudflared**, nome `fixa-hml`, **Save tunnel**.
3. Escolha **Debian** e **arm64**, e copie o comando de instalação.
4. No Terminal do Mac, entre na VM (`ssh fixa-hml`), cole o comando e rode. **O comando tem um token secreto: não cole aqui no chat nem em lugar nenhum.**
5. Volte ao painel e espere o conector aparecer como **Connected**. Clique em **Next**, mas **não crie rota (public hostname) agora**.

**Me avise. Eu confiro se o `cloudflared` está rodando na VM.**

## Passo 8: proteção e publicação do endereço (você, eu confiro)

Sempre proteja antes de publicar. Uma rota sem Access deixa o app aberto para a internet.

### 8.1 Login por código no e-mail

**Zero Trust**, **Settings**, **Authentication** (ou **Integrations**, **Identity providers**), **Add new**, **One-time PIN**.

### 8.2 Aplicativo protegido

1. **Zero Trust**, **Access**, **Applications**, **Add an application**, **Self-hosted**.
2. **Application name:** `Fixa hml`. **Session duration:** 24 horas.
3. **Public hostname:** subdomínio `hml` e o seu domínio.
4. **Identity providers:** só **One-time PIN**.
5. **Policy:** nome `Testers`, ação **Allow**, regra **Include**, seletor **Emails**, com o e-mail de cada pessoa, incluindo o seu.
6. Salve.

### 8.3 Publicar

1. **Networks**, **Tunnels**, túnel `fixa-hml`, **Edit**, aba **Public Hostname**, **Add a public hostname**.
2. Subdomínio `hml`, seu domínio, **Type** `HTTP`, **URL** `127.0.0.1:80`.
3. Salve. A Cloudflare cria o DNS e o certificado HTTPS.

### 8.4 Testar

1. Janela anônima em `https://hml.SEU_DOMINIO`: deve aparecer a tela da Cloudflare, não o Fixa.
2. Entre com um e-mail da lista: o código chega por e-mail, vale 10 minutos e é de uso único.
3. Teste também com um e-mail fora da lista: nenhum código chega.
4. Repita no celular.

Se a sessão do Access expirar com o Fixa aberto, as ações param de responder. Recarregue a página para entrar de novo.

**Me avise. Eu confiro o endereço de fora e os cabeçalhos de segurança.**

## Passo 9: e-mail com o Resend (você, eu configuro)

O Fixa exige e-mail verificado para entrar e manda lembretes e redefinição de senha. O Resend tem plano gratuito (3.000 e-mails por mês).

1. Crie a conta em [resend.com](https://resend.com).
2. **Domains**, **Add domain**, informe `hml.SEU_DOMINIO` (ou o domínio principal) e região **São Paulo** se houver.
3. Use a opção de configurar automaticamente na Cloudflare, ou crie os registros DNS que o Resend mostrar. Espere o status **Verified**.
4. **API Keys**, **Create API Key**, permissão **Sending access**, só para esse domínio. Copie a chave.
5. Coloque a chave no `.env` da VM **você mesmo** (não cole aqui):

   ```bash
   ssh fixa-hml
   sudo -u www-data nano /var/www/fixa/.env
   ```

   Ajuste as linhas:

   ```text
   MAIL_MAILER=smtp
   MAIL_SCHEME=smtp
   MAIL_HOST=smtp.resend.com
   MAIL_PORT=587
   MAIL_USERNAME=resend
   MAIL_PASSWORD=A_CHAVE_DO_RESEND
   MAIL_FROM_ADDRESS="nao-responda@hml.SEU_DOMINIO"
   ```

   Salve com Ctrl + O e Enter, saia com Ctrl + X, e rode `exit`.

**Me avise. Eu recarrego a configuração e mando um e-mail de teste.**

## Passo 10: IA com o Gemini (você, opcional)

A homologação sobe com a IA no adaptador falso. Para testar a geração de verdade:

1. Crie uma chave **separada** da local em [aistudio.google.com](https://aistudio.google.com), com limite de gasto se o projeto for pago.
2. No `.env` da VM (mesmo caminho do Passo 9), ajuste `FIXA_AI_PROVIDER=gemini` e `GEMINI_API_KEY=A_CHAVE`.

No plano gratuito do Gemini, o Google pode usar o conteúdo para treino. Por isso só envie aulas fictícias (DAD-05).

**Me avise. Eu recarrego a configuração e confiro.**

## Passo 11: teste completo e checklist de segurança (nós dois)

Você cria sua conta pelo endereço, confirma o e-mail, envia uma aula fictícia e faz um dia do plano. Eu acompanho a fila, o antivírus e os logs.

### Checklist antes de convidar testers

- [ ] `APP_ENV=staging` e `APP_DEBUG=false`
- [ ] PostgreSQL, Redis e ClamAV só locais; nenhuma porta além da 22 aberta na Oracle
- [ ] Acesso só pelo Cloudflare Access, testado em janela anônima
- [ ] SSH só com chave, de preferência restrito ao seu IP
- [ ] Email Address Obfuscation desligado
- [ ] Limite de gasto na chave do Gemini, se ativada
- [ ] Backup agendado e uma restauração testada
- [ ] Só dados fictícios (DAD-05)
- [ ] Alerta de orçamento na Oracle
- [ ] `docs/versoes.md` com as versões da homologação

## Backup e restauração

O backup roda às 3h UTC e guarda 14 dias em `/var/backups/fixa`. Para trazer uma cópia para o Mac:

```bash
ssh fixa-hml 'sudo ls /var/backups/fixa'
ssh fixa-hml 'sudo cat /var/backups/fixa/ARQUIVO.dump' > ~/Downloads/ARQUIVO.dump
```

Para restaurar na VM:

```bash
sudo -u postgres pg_restore --clean --if-exists -d fixa_hml /var/backups/fixa/ARQUIVO.dump
```

O plano gratuito da Oracle também permite até cinco backups do volume da VM pelo console.

## Evitar que a Oracle pare a VM

A Oracle pode recuperar VMs Always Free ociosas: 7 dias com CPU (percentil 95), rede e memória abaixo de 20%. Uma homologação pouco usada pode cair nisso.

- **Recomendado:** migrar para **Pay As You Go**. A Oracle não cobra o que fica dentro do limite gratuito. Em **Billing & Cost Management**, **Budgets**, crie um orçamento de US$ 1 com alerta por e-mail. Crie só recursos marcados como **Always Free-eligible**.
- **Alternativa:** usar com frequência. Se a VM for parada, inicie de novo pelo console.

## Problemas comuns

| Sintoma | Causa provável | O que fazer |
| --- | --- | --- |
| 502 Bad Gateway | PHP-FPM parado | `sudo systemctl status php8.5-fpm` |
| 500 no app | Erro do Laravel ou permissão | `sudo tail -50 /var/www/fixa/storage/logs/laravel-*.log` |
| Erro da Cloudflare 1033 ou 530 | Túnel parado | `sudo systemctl restart cloudflared` e confira **Healthy** no painel |
| Links em `http` ou laço de redirecionamento | `APP_URL` sem `https` ou cache antigo | Ajuste o `APP_URL` e rode o `deploy.sh` (ou `sudo -u www-data php artisan optimize` na VM) |
| Mudei o `.env` e nada mudou | Configuração em cache | `cd /var/www/fixa && sudo -u www-data php artisan optimize` |
| Upload de aula falha | ClamAV parado ou sem base | `sudo systemctl status clamav-daemon clamav-freshclam` |
| Aula fica em processamento | Fila parada | `sudo systemctl status fixa-queue` e `journalctl -u fixa-queue -n 50` |
| Lembretes não saem | Agendador parado ou e-mail sem configurar | `sudo systemctl status fixa-scheduler` e Passo 9 |
| Tela em branco ou dados quebrados | Email Address Obfuscation ligada | Passo 6.3 |
| Código de login não chega | E-mail fora da política ou no spam | Confira a política `Testers` e peça um novo código |
| SSH não conecta | Regra da porta 22 ou IP mudou | Revise a regra na Default Security List |

## Links úteis

| Link | Para que serve |
| --- | --- |
| [Oracle Cloud Free Tier](https://www.oracle.com/cloud/free/) | Criar a conta |
| [Console da Oracle Cloud](https://cloud.oracle.com) | VM, rede e cobrança |
| [Recursos Always Free](https://docs.oracle.com/en-us/iaas/Content/FreeTier/freetier_topic-Always_Free_Resources.htm) | Limites e regra de VM ociosa |
| [Painel da Cloudflare](https://dash.cloudflare.com) | Domínio, túnel e Access |
| [Criar um túnel pelo painel](https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/get-started/create-remote-tunnel/) | Passo 7 |
| [Aplicativo self-hosted no Access](https://developers.cloudflare.com/cloudflare-one/applications/configure-apps/self-hosted-public-app/) | Passo 8 |
| [One-time PIN](https://developers.cloudflare.com/cloudflare-one/identity/one-time-pin/) | Passo 8.1 |
| [Resend](https://resend.com/docs/send-with-smtp) | Passo 9 |
| [Registro.br](https://registro.br) | Domínio |
| [Deploy do Laravel](https://laravel.com/docs/deployment) | Referência do Nginx |
