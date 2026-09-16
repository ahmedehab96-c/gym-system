#!/usr/bin/env bash
# Renders the Nginx/systemd *.template files in this directory into their
# real config counterparts, substituting only the deployment variables
# defined in deploy/.env.deploy (never Nginx's own $uri/$host/etc., which
# are left untouched).
#
# Usage:
#   cp deploy/.env.deploy.example deploy/.env.deploy   # then fill in real values
#   ./deploy/render-templates.sh
#
# Output goes to deploy/rendered/ — review it, then copy into place
# (e.g. /etc/nginx/sites-available/, /etc/systemd/system/) yourself.
# This script does not touch any system directory.

set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")"

ENV_FILE=".env.deploy"
if [[ ! -f "$ENV_FILE" ]]; then
  echo "Missing deploy/.env.deploy — copy .env.deploy.example to .env.deploy and fill in real values first." >&2
  exit 1
fi

set -a
# shellcheck disable=SC1090
source "$ENV_FILE"
set +a

for required in FRONTEND_DOMAIN API_DOMAIN BACKEND_ROOT FRONTEND_DIST_ROOT PHP_FPM_SOCK; do
  if [[ -z "${!required:-}" ]]; then
    echo "deploy/.env.deploy: $required is not set — fill it in before rendering." >&2
    exit 1
  fi
done

# Explicit variable list: envsubst only replaces these, so Nginx's own
# $uri/$host/$request_uri/$query_string/$realpath_root are left as-is.
VARS='${FRONTEND_DOMAIN} ${API_DOMAIN} ${BACKEND_ROOT} ${FRONTEND_DIST_ROOT} ${PHP_FPM_SOCK} ${FRONTEND_SSL_CERT} ${FRONTEND_SSL_KEY} ${API_SSL_CERT} ${API_SSL_KEY} ${WEB_USER} ${WEB_GROUP}'

mkdir -p rendered
for tmpl in nginx/*.template systemd/*.template; do
  out="rendered/$(basename "${tmpl%.template}")"
  envsubst "$VARS" < "$tmpl" > "$out"
  echo "Rendered $tmpl -> deploy/$out"
done

echo
echo "Review the files in deploy/rendered/ before deploying them:"
echo "  Nginx:   sudo cp deploy/rendered/*.conf /etc/nginx/sites-available/ && sudo ln -s ... /etc/nginx/sites-enabled/ && sudo nginx -t && sudo systemctl reload nginx"
echo "  systemd: sudo cp deploy/rendered/*.service /etc/systemd/system/ && sudo systemctl daemon-reload"
