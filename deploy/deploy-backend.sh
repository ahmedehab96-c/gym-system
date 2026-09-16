#!/usr/bin/env bash
# Deploys/updates the Laravel backend on a server that already has
# backend/.env configured (see DEPLOYMENT.md §3) and PHP/Composer
# installed (§1). Safe to re-run for subsequent deploys.
#
# Usage: ./deploy/deploy-backend.sh [path-to-backend]
# Defaults to ../backend relative to this script.

set -euo pipefail
BACKEND_ROOT="${1:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../backend" && pwd)}"
cd "$BACKEND_ROOT"

if [[ ! -f .env ]]; then
  echo "No .env found in $BACKEND_ROOT — copy .env.example to .env and configure it first (see DEPLOYMENT.md §3)." >&2
  exit 1
fi

if grep -q '^APP_ENV=local' .env; then
  echo "Refusing to run: $BACKEND_ROOT/.env still has APP_ENV=local. Set APP_ENV=production first." >&2
  exit 1
fi

echo "==> composer install (production, no dev dependencies)"
composer install --no-dev --optimize-autoloader --no-interaction

if ! grep -q '^APP_KEY=base64:' .env; then
  echo "==> generating APP_KEY"
  php artisan key:generate --force
else
  echo "==> APP_KEY already set, skipping key:generate"
fi

echo "==> running migrations"
php artisan migrate --force

echo "==> linking public storage"
php artisan storage:link || true   # already-linked is not an error

echo "==> caching config/routes/events/views for production"
php artisan config:cache
php artisan route:cache
php artisan event:cache
php artisan view:cache

echo "==> fixing storage/bootstrap-cache permissions"
WEB_USER="${WEB_USER:-www-data}"
WEB_GROUP="${WEB_GROUP:-www-data}"
if command -v chown >/dev/null && [[ "$(id -u)" -eq 0 ]]; then
  chown -R "${WEB_USER}:${WEB_GROUP}" storage bootstrap/cache
else
  echo "    (skipped chown — not running as root; run manually: chown -R ${WEB_USER}:${WEB_GROUP} storage bootstrap/cache)"
fi
chmod -R 775 storage bootstrap/cache

echo "==> done. Reminders:"
echo "    - reload/restart PHP-FPM if opcache is enabled with validate_timestamps=0"
echo "    - restart the queue worker (systemctl restart gym-queue-worker) if it's running"
echo "    - confirm the cron entry for 'php artisan schedule:run' is installed (DEPLOYMENT.md §6)"
