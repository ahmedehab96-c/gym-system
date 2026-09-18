#!/bin/sh
set -e

if [ ! -f .env ]; then
  cp .env.example .env
fi

# Prefer platform-provided public URL. Render exposes RENDER_EXTERNAL_URL
# (full https:// URL) automatically; Railway exposes RAILWAY_PUBLIC_DOMAIN
# (host only, no scheme) when a domain is generated for the service.
if [ -z "${APP_URL:-}" ] && [ -n "${RENDER_EXTERNAL_URL:-}" ]; then
  export APP_URL="${RENDER_EXTERNAL_URL}"
elif [ -z "${APP_URL:-}" ] && [ -n "${RAILWAY_PUBLIC_DOMAIN:-}" ]; then
  export APP_URL="https://${RAILWAY_PUBLIC_DOMAIN}"
fi

# Ensure a usable APP_KEY is available to the PHP process. Platform CLIs can
# mangle base64 padding (`=`) when passing env vars, so always normalize here.
case "${APP_KEY:-}" in
  base64:????????????????*)
    ;;
  *)
    export APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
    ;;
esac

# Persist the platform's env vars into .env — `php artisan serve` spawns
# its own PHP built-in-server subprocess (Symfony Process) that does NOT
# reliably inherit arbitrary orchestrator-injected env vars the way a
# directly-exec'd process does; it does reliably read .env. Without this,
# the serve subprocess silently falls back to .env.example's committed
# defaults (DB_CONNECTION=mysql, CACHE_STORE=database, ...) even though
# `artisan migrate`/`db:seed` above (run directly, not through serve) see
# the real platform values and work fine — a split-brain that's easy to
# misdiagnose as a code bug.
set_env() {
  key="$1"
  value="$2"
  [ -z "$value" ] && return 0
  TMP_ENV="$(mktemp)"
  grep -v "^${key}=" .env > "$TMP_ENV" || true
  echo "${key}=${value}" >> "$TMP_ENV"
  mv "$TMP_ENV" .env
}

set_env APP_KEY "${APP_KEY}"
set_env APP_URL "${APP_URL:-}"
set_env APP_ENV "${APP_ENV:-}"
set_env APP_DEBUG "${APP_DEBUG:-}"
set_env FRONTEND_URL "${FRONTEND_URL:-}"
set_env LOG_CHANNEL "${LOG_CHANNEL:-}"
set_env DB_CONNECTION "${DB_CONNECTION:-}"
set_env SESSION_DRIVER "${SESSION_DRIVER:-}"
set_env CACHE_STORE "${CACHE_STORE:-}"
set_env QUEUE_CONNECTION "${QUEUE_CONNECTION:-}"
set_env MAIL_MAILER "${MAIL_MAILER:-}"

export APP_KEY

php artisan config:clear --no-interaction >/dev/null 2>&1 || true

# HTTPS demos behind Render / Railway need secure cookies when APP_URL is https.
case "${APP_URL:-}" in
  https://*)
    export SESSION_SECURE_COOKIE="${SESSION_SECURE_COOKIE:-true}"
    set_env SESSION_SECURE_COOKIE "${SESSION_SECURE_COOKIE:-true}"
    ;;
esac

# Wait for MySQL when configured (not used by default — this project runs
# on sqlite for the live demo, see backend/Dockerfile).
if [ "${DB_CONNECTION:-sqlite}" = "mysql" ]; then
  echo "Waiting for MySQL at ${DB_HOST:-mysql}:${DB_PORT:-3306}..."
  i=0
  until php -r "
    try {
      new PDO(
        'mysql:host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: '3306') . ';dbname=' . getenv('DB_DATABASE'),
        getenv('DB_USERNAME'),
        getenv('DB_PASSWORD')
      );
      exit(0);
    } catch (Throwable \$e) {
      exit(1);
    }
  "; do
    i=$((i + 1))
    if [ "$i" -ge 60 ]; then
      echo "MySQL not ready after 60s"
      exit 1
    fi
    sleep 1
  done
fi

php artisan migrate --force --no-interaction

# Seed only on first boot (empty users) or when SEED_ON_START=true
SHOULD_SEED="${SEED_ON_START:-false}"
if [ "$SHOULD_SEED" = "true" ]; then
  php artisan db:seed --force --no-interaction
elif [ "${DB_CONNECTION:-sqlite}" = "mysql" ]; then
  COUNT="$(php -r "
    try {
      \$pdo = new PDO(
        'mysql:host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: '3306') . ';dbname=' . getenv('DB_DATABASE'),
        getenv('DB_USERNAME'),
        getenv('DB_PASSWORD')
      );
      \$n = (int) \$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
      echo \$n;
    } catch (Throwable \$e) {
      echo '0';
    }
  ")"
  if [ "$COUNT" = "0" ]; then
    php artisan db:seed --force --no-interaction
  fi
elif [ -f database/database.sqlite ]; then
  COUNT="$(php -r "
    try {
      \$pdo = new PDO('sqlite:database/database.sqlite');
      \$n = (int) \$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
      echo \$n;
    } catch (Throwable \$e) {
      echo '0';
    }
  ")"
  if [ "$COUNT" = "0" ]; then
    php artisan db:seed --force --no-interaction
  fi
else
  php artisan db:seed --force --no-interaction
fi

if [ "${RUN_QUEUE_WORKER:-false}" = "true" ] && [ "${QUEUE_CONNECTION:-sync}" != "sync" ]; then
  echo "Starting queue worker in background..."
  php artisan queue:work --sleep=3 --tries=3 --max-time=0 &
fi

PORT="${PORT:-8000}"
exec php artisan serve --host=0.0.0.0 --port="${PORT}"
