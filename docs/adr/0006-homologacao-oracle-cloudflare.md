# ADR 0006: Homologação na Oracle Always Free com Cloudflare Tunnel e Access

- Data: 30/10/2026
- Situação: aceita
- Tarefa: J6-B11 (DAD-05)

## Contexto

O Fixa precisa de um ambiente de testes fora do Mac, acessível pelo celular e por poucos testers, sem custo de servidor. O app depende de PHP 8.5, PostgreSQL 18, Redis, ClamAV, poppler, fila e agendador rodando o tempo todo. Hospedagem compartilhada não atende.

## Decisão

- Uma VM ARM Always Free da Oracle (Ubuntu 24.04, 2 OCPUs e 12 GB) roda tudo direto no sistema, sem Docker: PHP 8.5 do Ondřej Surý (mesma origem do container), PostgreSQL 18 do PGDG, Redis, ClamAV e Nginx. Todos escutam só em 127.0.0.1.
- Nenhuma porta web aberta. O acesso passa pelo Cloudflare Tunnel, e o Cloudflare Access (código de uso único por e-mail, lista de e-mails permitidos) fica na frente de tudo.
- O Nginx marca a requisição como HTTPS e lê o IP real em `CF-Connecting-IP`. `TRUSTED_PROXIES` fica vazio.
- `APP_ENV=staging`, `APP_DEBUG=false`, só dados fictícios (DAD-05). A IA começa no adaptador falso; o Gemini usa uma chave separada da local.
- `deploy/hml/provision.sh` prepara a VM de forma idempotente. `deploy/hml/deploy.sh` publica só o commit atual (`git archive`) mais o `public/build`, para nada ignorado pelo Git sair do computador.
- E-mail pelo SMTP do Resend (plano gratuito). Segredos (senha do banco, chaves do Resend e do Gemini, token do túnel) só no `.env` ou no serviço da VM, nunca no repositório nem no chat (DAD-04).

## Consequências

- Custo zero além do domínio. A Oracle pode recuperar VMs ociosas; a conta Pay As You Go com alerta de orçamento evita isso.
- Atualizar o PHP na homologação é `apt upgrade` na VM, e entra no `docs/versoes.md` como os outros ambientes (SEG-03).
- O passo a passo fica em `plan-hml/`. Produção continua sendo outra decisão.
