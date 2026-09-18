#!/bin/sh
set -e

echo "=== DEBUG ENV ==="
env | grep -E '^(FRONTEND_URL|LOG_CHANNEL|SESSION_DRIVER|CACHE_STORE|APP_DEBUG|APP_ENV|SEED_ON_START|DB_CONNECTION|QUEUE_CONNECTION|RENDER_EXTERNAL_URL)=' | sort || true
echo "=================="

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

# Persist into .env so artisan/config always see a key even if the platform env is blank.
if grep -q '^APP_KEY=' .env; then
  TMP_ENV="$(mktemp)"
  grep -v '^APP_KEY=' .env > "$TMP_ENV" || true
  echo "APP_KEY=${APP_KEY}" >> "$TMP_ENV"
  mv "$TMP_ENV" .env
else
  echo "APP_KEY=${APP_KEY}" >> .env
fi
export APP_KEY

if [ -n "${APP_URL:-}" ]; then
  TMP_ENV="$(mktemp)"
  grep -v '^APP_URL=' .env > "$TMP_ENV" || true
  echo "APP_URL=${APP_URL}" >> "$TMP_ENV"
  mv "$TMP_ENV" .env
fi

php artisan config:clear --no-interaction >/dev/null 2>&1 || true

echo "=== DEBUG PING TRACE ==="
php artisan tinker --execute="
try {
    \$kernel = app(\Illuminate\Contracts\Http\Kernel::class);
    \$request = \Illuminate\Http\Request::create('/api/v1/ping', 'GET');
    \$response = \$kernel->sendRequestThroughRouter(\$request);
    echo 'STATUS: ' . \$response->getStatusCode() . PHP_EOL;
    echo 'BODY: ' . \$response->getContent() . PHP_EOL;
} catch (\Throwable \$e) {
    echo 'RAW EXCEPTION: ' . get_class(\$e) . ': ' . \$e->getMessage() . PHP_EOL;
    echo 'AT: ' . \$e->getFile() . ':' . \$e->getLine() . PHP_EOL;
    echo \$e->getTraceAsString() . PHP_EOL;
}
" 2>&1 || true
echo "========================"

# HTTPS demos behind Railway need secure cookies when APP_URL is https.
case "${APP_URL:-}" in
  https://*)
    export SESSION_SECURE_COOKIE="${SESSION_SECURE_COOKIE:-true}"
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
