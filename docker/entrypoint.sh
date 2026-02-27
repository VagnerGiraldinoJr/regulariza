#!/usr/bin/env sh
set -e

if [ ! -f .env ]; then
  cp .env.example .env
fi

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

exec php artisan serve --host=0.0.0.0 --port=8000
