#!/usr/bin/env bash
# Prepara a VM de homologação do Fixa: Ubuntu 24.04 ARM na Oracle Always Free (ADR 0006).
# Roda na VM como root, a partir de /tmp/fixa-hml (esta pasta mais o .env.example):
#   sudo APP_URL=https://fixa-hml.exemplo.com.br bash /tmp/fixa-hml/provision.sh
# Idempotente: rodar de novo só reaplica a configuração. Nunca imprime segredos.
set -euo pipefail

: "${APP_URL:?Informe APP_URL, por exemplo https://fixa-hml.exemplo.com.br}"

HERE="$(cd "$(dirname "$0")" && pwd)"
APP=/var/www/fixa
PHP=8.5
PG=18
export DEBIAN_FRONTEND=noninteractive

step() { printf '\n==> %s\n' "$1"; }

step "Sistema e pacotes base"
apt-get update -q
apt-get upgrade -yq
apt-get install -yq software-properties-common ca-certificates curl gnupg unzip git rsync \
    nginx redis-server poppler-utils clamav-daemon clamav-freshclam postgresql-common

step "Repositórios do PHP $PHP (Ondřej Surý) e do PostgreSQL $PG (PGDG)"
ls /etc/apt/sources.list.d/ | grep -q ondrej || add-apt-repository -y ppa:ondrej/php
ls /etc/apt/sources.list.d/ | grep -q pgdg || /usr/share/postgresql-common/pgdg/apt.postgresql.org.sh -y
apt-get update -q
apt-get install -yq "php$PHP-fpm" "php$PHP-cli" "php$PHP-pgsql" "php$PHP-mbstring" "php$PHP-xml" \
    "php$PHP-curl" "php$PHP-zip" "php$PHP-bcmath" "php$PHP-intl" "php$PHP-gd" "php$PHP-redis" \
    "php$PHP-igbinary" "php$PHP-msgpack" "postgresql-$PG"

step "PHP: limites do upload e mitigação do aviso de progresso de sessão"
install -m 644 "$HERE/php.ini" "/etc/php/$PHP/fpm/conf.d/99-fixa.ini"
install -m 644 "$HERE/php.ini" "/etc/php/$PHP/cli/conf.d/99-fixa.ini"
systemctl restart "php$PHP-fpm"

step "Composer (instalador oficial, com assinatura conferida)"
if ! command -v composer >/dev/null; then
    expected="$(curl -fsSL https://composer.github.io/installer.sig)"
    curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
    actual="$(php -r "echo hash_file('sha384', '/tmp/composer-setup.php');")"
    [ "$expected" = "$actual" ] || { echo "Assinatura do instalador do Composer não confere"; exit 1; }
    php /tmp/composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
    rm -f /tmp/composer-setup.php
fi

step "Pasta do app"
mkdir -p "$APP"
chown www-data:www-data "$APP"
sudo -u www-data mkdir -p "$APP"/storage/app/private/lessons "$APP"/storage/app/public \
    "$APP"/storage/framework/cache/data "$APP"/storage/framework/sessions \
    "$APP"/storage/framework/views "$APP"/storage/logs "$APP"/bootstrap/cache

step "PostgreSQL $PG: papel e banco do Fixa (só conexões locais)"
db_password=""
if ! sudo -u postgres psql -tAc "select 1 from pg_roles where rolname = 'fixa'" | grep -q 1; then
    db_password="$(openssl rand -hex 24)"
    sudo -u postgres psql -q -v ON_ERROR_STOP=1 -c "create role fixa login password '$db_password'"
    sudo -u postgres createdb -O fixa fixa_hml
fi

step ".env da homologação"
ENV="$APP/.env"
if [ ! -f "$ENV" ]; then
    install -o www-data -g www-data -m 600 "$HERE/.env.example" "$ENV"
fi
set_env() {
    if grep -q "^$1=" "$ENV"; then sed -i "s|^$1=.*|$1=$2|" "$ENV"; else echo "$1=$2" >> "$ENV"; fi
}
set_env APP_ENV staging
set_env APP_DEBUG false
set_env APP_URL "$APP_URL"
set_env LOG_LEVEL info
set_env DB_HOST 127.0.0.1
set_env DB_PORT 5432
set_env DB_DATABASE fixa_hml
set_env DB_USERNAME fixa
[ -n "$db_password" ] && set_env DB_PASSWORD "$db_password"
set_env REDIS_HOST 127.0.0.1
set_env CLAMAV_HOST 127.0.0.1
set_env TRUSTED_PROXIES ""
grep -q '^FIXA_AI_PROVIDER=' "$ENV" || set_env FIXA_AI_PROVIDER fake
unset db_password

step "ClamAV: base de vírus e clamd em 127.0.0.1:3310"
mkdir -p /etc/systemd/system/clamav-daemon.socket.d
cat > /etc/systemd/system/clamav-daemon.socket.d/fixa.conf <<'EOF'
[Socket]
ListenStream=127.0.0.1:3310
EOF
systemctl daemon-reload
systemctl enable --now clamav-freshclam
for _ in $(seq 1 60); do ls /var/lib/clamav/daily.c[lv]d >/dev/null 2>&1 && break; sleep 5; done
systemctl restart clamav-daemon.socket clamav-daemon

step "Nginx (só em 127.0.0.1, o cloudflared entrega as visitas)"
install -m 644 "$HERE/nginx.conf" /etc/nginx/sites-available/fixa
ln -sf /etc/nginx/sites-available/fixa /etc/nginx/sites-enabled/fixa
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx

step "Fila e agendador como serviços"
install -m 644 "$HERE/fixa-queue.service" /etc/systemd/system/fixa-queue.service
install -m 644 "$HERE/fixa-scheduler.service" /etc/systemd/system/fixa-scheduler.service
systemctl daemon-reload
systemctl enable fixa-queue fixa-scheduler

step "Backup diário do banco (14 dias, em /var/backups/fixa)"
install -d -o postgres -g postgres -m 700 /var/backups/fixa
cat > /etc/cron.d/fixa-backup <<'EOF'
0 3 * * * postgres pg_dump -Fc fixa_hml -f /var/backups/fixa/fixa_hml-$(date +\%Y\%m\%d).dump && find /var/backups/fixa -name 'fixa_hml-*.dump' -mtime +14 -delete
EOF

step "Versões"
php -v | head -1
sudo -u postgres psql -tAc 'select version()' | cut -d, -f1
nginx -v 2>&1
redis-server --version | cut -d' ' -f1-3
clamd --version
composer --version 2>/dev/null | head -1
echo "Pronto. Próximo passo: deploy/hml/deploy.sh no Mac."
