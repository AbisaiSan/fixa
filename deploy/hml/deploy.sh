#!/usr/bin/env bash
# Publica o commit atual na homologação (ADR 0006). Roda no Mac: deploy/hml/deploy.sh
# Só envia arquivos versionados (git archive) mais o public/build, então nada
# ignorado pelo Git, como .env ou tests/aulas-reais, sai do computador.
set -euo pipefail

HOST="${FIXA_HML_HOST:-fixa-hml}"
APP=/var/www/fixa
cd "$(git rev-parse --show-toplevel)"

if [ -n "$(git status --porcelain)" ]; then
    echo "Há mudanças sem commit. A homologação recebe só o que está commitado."
    exit 1
fi

npm run build
rm -f public/hot

BUILD="$(mktemp -d)"
trap 'rm -rf "$BUILD"' EXIT
git archive HEAD | tar -x -C "$BUILD"
cp -R public/build "$BUILD/public/build"

rsync -rlptz --delete --chmod=D755,F644 --rsync-path="sudo -u www-data rsync" \
    --exclude '/.env' --exclude '/vendor/' --exclude '/storage/' \
    --exclude '/bootstrap/cache/' --exclude '/public/storage' \
    "$BUILD/" "$HOST:$APP/"

ssh "$HOST" "cd $APP && sudo -u www-data env COMPOSER_HOME=/tmp/composer-www bash -c '
    set -e
    composer install --no-dev --optimize-autoloader --no-interaction --no-progress
    grep -q \"^APP_KEY=base64:\" .env || php artisan key:generate --force
    php artisan migrate --force
    php artisan optimize
    php artisan queue:restart
' && sudo systemctl reload php8.5-fpm && sudo systemctl restart fixa-queue fixa-scheduler"

echo "Publicado: $(git log -1 --format='%h %s')"
