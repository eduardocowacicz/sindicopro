#!/usr/bin/env sh
set -eu

if [ ! -f /app/artisan ] || [ ! -f /app/composer.json ]; then
    echo "SindicoPro: a aplicacao Laravel ainda nao foi criada em Back-end/." >&2
    echo "Consulte Desenvolvimento/doc/PLANO_INICIAL_DESENVOLVIMENTO.md e execute a etapa de bootstrap." >&2
    exit 1
fi

mkdir -p \
    /app/bootstrap/cache \
    /app/storage/framework/cache \
    /app/storage/framework/sessions \
    /app/storage/framework/views \
    /app/storage/logs

lock_hash=$(sha256sum /app/composer.lock | cut -d ' ' -f 1)
installed_lock_hash=$(cat /app/vendor/.sindicopro-lock-hash 2>/dev/null || true)

if [ "${SINDICOPRO_INSTALL_DEPENDENCIES:-true}" = "true" ] \
    && { [ ! -f /app/vendor/autoload.php ] || [ "$lock_hash" != "$installed_lock_hash" ]; }; then
    echo "SindicoPro: sincronizando dependencias PHP do lockfile..." >&2
    composer install --no-interaction --prefer-dist
    printf '%s' "$lock_hash" > /app/vendor/.sindicopro-lock-hash
fi

if [ "${SINDICOPRO_INSTALL_DEPENDENCIES:-true}" != "true" ]; then
    attempts=0

    while [ ! -f /app/vendor/autoload.php ] \
        || [ "$lock_hash" != "$(cat /app/vendor/.sindicopro-lock-hash 2>/dev/null || true)" ]; do
        attempts=$((attempts + 1))

        if [ "$attempts" -ge 120 ]; then
            echo "SindicoPro: dependencias PHP nao foram preparadas pelo backend." >&2
            exit 1
        fi

        sleep 1
    done
fi

if [ "${1:-}" = "php" ] && [ "${2:-}" = "artisan" ] && [ "${3:-}" = "serve" ]; then
    echo "SindicoPro: aplicando migrations pendentes..." >&2
    php artisan migrate --force

    if [ "${APP_ENV:-production}" = "local" ]; then
        echo "SindicoPro: preparando dados locais de desenvolvimento..." >&2
        php artisan db:seed --force
    fi
fi

exec "$@"
