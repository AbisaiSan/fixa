# ADR 0005: Acesso pelo celular via Tailscale

- Data: 28/10/2026
- Situação: aceita

## Contexto

O Fixa roda só no Mac, pelo Sail, com a porta presa em `127.0.0.1:8080`. O aluno quer usar o app pelo celular, em casa ou no 4G, sem servidor público. Um túnel público (ngrok) deixaria a tela de login e o cadastro abertos para qualquer pessoa com o link.

## Decisão

- O acesso de fora do Mac é só pela rede privada do Tailscale, com HTTPS do `tailscale serve`. Nada fica exposto na internet.
- O `tailscale serve` roda no Mac e repassa para `http://127.0.0.1:8080`. O container recebe a conexão pelo gateway da rede do Docker (hoje `172.18.0.1`).
- `config/trustedproxy.php` lê `TRUSTED_PROXIES`. Vazio, o app ignora os cabeçalhos `X-Forwarded-*`. No `.env` local fica `172.16.0.0/12`, a faixa das redes do Docker, para o app gerar links `https://` com o host do Tailscale e a verificação de origem do CSRF aceitar o formulário.
- O celular usa os assets compilados (`npm run build`). O servidor do Vite não é alcançável de lá.

## Como usar

1. Instale o Tailscale no Mac (`brew install --cask tailscale`) e no celular, e entre com a mesma conta.
2. No painel do Tailscale, ative MagicDNS e HTTPS Certificates.
3. No Mac: `tailscale serve --bg 8080`. O comando mostra o endereço `https://<mac>.<rede>.ts.net`.
4. Rode `npm run build` e confira que não existe `public/hot`.
5. Opcional: `APP_URL=https://<mac>.<rede>.ts.net` no `.env`, para os links dos e-mails abrirem no celular.
6. O Mac precisa ficar ligado e acordado, com o Sail no ar (`caffeinate -s` ou Ajustes > Bateria).

Para desligar: `tailscale serve --https=443 off`.

## Consequências

- Só aparelhos da conta Tailscale do aluno chegam ao app.
- A faixa confiável vale só porque a porta do app está presa em `127.0.0.1`. Se a porta for aberta para a rede local, `TRUSTED_PROXIES` precisa ser revisto.
- Fora do Mac ligado não há acesso. Um servidor de verdade continua sendo outra decisão.
