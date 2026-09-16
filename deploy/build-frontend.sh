#!/usr/bin/env bash
# Builds the React frontend for production. Requires frontend/.env.production
# (or VITE_API_BASE_URL already exported in the environment) pointing at the
# real API URL — Vite inlines it at build time.
#
# Usage: ./deploy/build-frontend.sh [path-to-frontend]

set -euo pipefail
FRONTEND_ROOT="${1:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../frontend" && pwd)}"
cd "$FRONTEND_ROOT"

if [[ -z "${VITE_API_BASE_URL:-}" ]] && [[ ! -f .env.production ]] && [[ ! -f .env ]]; then
  echo "No VITE_API_BASE_URL set and no .env/.env.production found in $FRONTEND_ROOT." >&2
  echo "Copy .env.example to .env.production and set VITE_API_BASE_URL to the production API URL first." >&2
  exit 1
fi

echo "==> npm ci"
npm ci

echo "==> type-check + build"
npm run build

echo "==> done. Output in $FRONTEND_ROOT/dist — deploy that directory as static assets."
echo "    Verify the API URL that got baked in:"
grep -o 'https\?://[^"'"'"']*api[^"'"'"']*' dist/assets/*.js 2>/dev/null | head -1 || echo "    (could not auto-detect; open dist/assets/index-*.js and confirm manually if needed)"
