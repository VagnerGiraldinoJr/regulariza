#!/usr/bin/env bash
set -Eeuo pipefail

BRANCH="${1:-main}"
DO_FRESH_SEED="${DEPLOY_FRESH_SEED:-false}"

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT_DIR"

log() {
  printf '[deploy] %s\n' "$*"
}

run() {
  log "$*"
  "$@"
}

wait_mysql_health() {
  local container_id
  local status
  local attempts=60

  container_id="$(docker compose ps -q mysql)"
  if [[ -z "$container_id" ]]; then
    log "Nao consegui encontrar o container do service mysql."
    return 1
  fi

  for ((i=1; i<=attempts; i++)); do
    status="$(docker inspect -f '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "$container_id" 2>/dev/null || true)"

    if [[ "$status" == "healthy" || "$status" == "running" ]]; then
      log "MySQL pronto (status: $status)."
      return 0
    fi

    log "Aguardando MySQL ficar pronto... tentativa $i/$attempts (status atual: ${status:-unknown})"
    sleep 2
  done

  log "Timeout aguardando MySQL."
  return 1
}

log "Projeto: $ROOT_DIR"
log "Branch de deploy: $BRANCH"

run git fetch origin "$BRANCH"
run git pull --ff-only origin "$BRANCH"

run docker compose up -d --build --remove-orphans

wait_mysql_health

if [[ "$DO_FRESH_SEED" == "true" ]]; then
  run docker compose exec -T app php artisan migrate:fresh --seed --force
else
  run docker compose exec -T app php artisan migrate --force
fi

run docker compose exec -T app php artisan optimize:clear
run docker compose exec -T app php artisan optimize
run docker compose restart app queue
run docker compose ps

log "Deploy finalizado com sucesso."
