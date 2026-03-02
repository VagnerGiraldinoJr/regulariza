#!/usr/bin/env sh
set -e

if [ ! -f .env ]; then
  if [ -f .env.example ]; then
    cp .env.example .env
  else
    touch .env
  fi
fi

upsert_env() {
  key="$1"
  value="$2"
  if [ -z "$key" ] || [ -z "$value" ]; then
    return 0
  fi

  escaped_value=$(printf '%s' "$value" | sed 's/[\/&]/\\&/g')
  if grep -qE "^${key}=" .env; then
    sed -i "s/^${key}=.*/${key}=${escaped_value}/" .env
  else
    printf '\n%s=%s\n' "$key" "$value" >> .env
  fi
}

# Ensure runtime variables are persisted for web requests, even when .env was created from .env.example.
upsert_env APP_ENV "${APP_ENV:-}"
upsert_env APP_DEBUG "${APP_DEBUG:-}"
upsert_env APP_URL "${APP_URL:-}"
upsert_env DB_CONNECTION "${DB_CONNECTION:-}"
upsert_env DB_HOST "${DB_HOST:-}"
upsert_env DB_PORT "${DB_PORT:-}"
upsert_env DB_DATABASE "${DB_DATABASE:-}"
upsert_env DB_USERNAME "${DB_USERNAME:-}"
upsert_env DB_PASSWORD "${DB_PASSWORD:-}"
upsert_env REDIS_HOST "${REDIS_HOST:-}"
upsert_env REDIS_PORT "${REDIS_PORT:-}"
upsert_env CACHE_STORE "${CACHE_STORE:-}"
upsert_env SESSION_DRIVER "${SESSION_DRIVER:-}"
upsert_env QUEUE_CONNECTION "${QUEUE_CONNECTION:-}"

# Guarantee Laravel writable/cache directories exist in runtime container.
mkdir -p \
  storage/framework/cache \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

# Prevent stale package/config cache copied from host (can reference dev providers).
rm -f bootstrap/cache/packages.php bootstrap/cache/services.php bootstrap/cache/*.php

if [ -z "${APP_KEY}" ]; then
  php artisan key:generate --force || true
fi

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  echo "Waiting for MySQL at ${DB_HOST:-mysql}:${DB_PORT:-3306}..."
  for i in $(seq 1 60); do
    if nc -z "${DB_HOST:-mysql}" "${DB_PORT:-3306}"; then
      break
    fi
    sleep 2
  done

  php artisan migrate --force || true
fi

if [ "${APP_ENV}" = "production" ]; then
  php artisan config:cache || true
  php artisan route:cache || true
  php artisan view:cache || true
fi

if [ "$#" -gt 0 ]; then
  exec "$@"
fi

exec php artisan serve --host=0.0.0.0 --port=8000
