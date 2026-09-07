#!/usr/bin/env sh
set -eu

if [ ! -f /app/package.json ]; then
    echo "SindicoPro: a aplicacao Vue ainda nao foi criada em Front-end/." >&2
    echo "Consulte Desenvolvimento/doc/PLANO_INICIAL_DESENVOLVIMENTO.md e execute a etapa de bootstrap." >&2
    exit 1
fi

chown -R node:node /app/node_modules

if [ ! -f /app/package-lock.json ]; then
    echo "SindicoPro: package-lock.json ausente." >&2
    exit 1
fi

lock_hash=$(sha256sum /app/package-lock.json | cut -d ' ' -f 1)
installed_lock_hash=$(cat /app/node_modules/.sindicopro-lock-hash 2>/dev/null || true)

if [ "$lock_hash" != "$installed_lock_hash" ]; then
    echo "SindicoPro: sincronizando dependencias Node do lockfile..." >&2
    su-exec node npm ci
    printf '%s' "$lock_hash" > /app/node_modules/.sindicopro-lock-hash
    chown node:node /app/node_modules/.sindicopro-lock-hash
fi

exec su-exec node "$@"
