#!/usr/bin/env bash
# Post-deploy smoke test: exercises every module listed in the Phase 16
# production-verification checklist against a running instance of the
# API. Run this against the LOCAL dev stack to prove the script works,
# and again against the real production URL right after deploying.
#
# Usage:
#   BASE_URL=https://api.gym-domain.com/api/v1 \
#   ADMIN_EMAIL=admin@example.com ADMIN_PASSWORD='...' \
#   LOW_PRIV_EMAIL=trainer@example.com LOW_PRIV_PASSWORD='...' \
#   ./deploy/smoke-test.sh
#
# Defaults to the local dev stack + seeded dev credentials if no env vars
# are given, so it's runnable out of the box in development. It does NOT
# know any real production credentials — you must supply those yourself.

set -uo pipefail

BASE_URL="${BASE_URL:-http://localhost:8000/api/v1}"
ADMIN_EMAIL="${ADMIN_EMAIL:-ahmed.ehab@premiumgym.com}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-password}"
LOW_PRIV_EMAIL="${LOW_PRIV_EMAIL:-karim.adel@premiumgym.com}"   # seeded Trainer
LOW_PRIV_PASSWORD="${LOW_PRIV_PASSWORD:-password}"

PASS=0
FAIL=0

check() {
  local label="$1" expected="$2" actual="$3"
  if [[ "$actual" == "$expected" ]]; then
    echo "  OK   $label ($actual)"
    PASS=$((PASS+1))
  else
    echo "  FAIL $label (expected $expected, got $actual)"
    FAIL=$((FAIL+1))
  fi
}

get() { # get <path> <token>
  curl -s -o /tmp/smoke_body.json -w '%{http_code}' \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $2" \
    "${BASE_URL}$1"
}

echo "== Auth =="
LOGIN_BODY=$(curl -s -X POST "${BASE_URL}/auth/login" \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d "{\"email\":\"${ADMIN_EMAIL}\",\"password\":\"${ADMIN_PASSWORD}\"}")
TOKEN=$(echo "$LOGIN_BODY" | jq -r '.data.token // empty')
check "login" "1" "$([[ -n "$TOKEN" ]] && echo 1 || echo 0)"

if [[ -z "$TOKEN" ]]; then
  echo "Cannot continue without a valid admin login — check ADMIN_EMAIL/ADMIN_PASSWORD and BASE_URL." >&2
  exit 1
fi

code=$(get "/auth/me" "$TOKEN"); check "GET /auth/me" 200 "$code"

echo "== Dashboard =="
code=$(get "/dashboard/summary" "$TOKEN"); check "GET /dashboard/summary" 200 "$code"

echo "== Members / Memberships / Attendance =="
code=$(get "/members" "$TOKEN"); check "GET /members" 200 "$code"
code=$(get "/memberships" "$TOKEN"); check "GET /memberships" 200 "$code"
code=$(get "/attendance/today" "$TOKEN"); check "GET /attendance/today" 200 "$code"

echo "== Trainers / Programs / Classes / Schedule =="
code=$(get "/trainers" "$TOKEN"); check "GET /trainers" 200 "$code"
code=$(get "/training-programs" "$TOKEN"); check "GET /training-programs" 200 "$code"
code=$(get "/classes" "$TOKEN"); check "GET /classes" 200 "$code"
code=$(get "/schedule/weekly" "$TOKEN"); check "GET /schedule/weekly" 200 "$code"

echo "== Equipment / Maintenance =="
code=$(get "/equipment" "$TOKEN"); check "GET /equipment" 200 "$code"
code=$(get "/maintenance" "$TOKEN"); check "GET /maintenance" 200 "$code"

echo "== Payments / Invoices / Expenses =="
code=$(get "/payments" "$TOKEN"); check "GET /payments" 200 "$code"
code=$(get "/invoices" "$TOKEN"); check "GET /invoices" 200 "$code"
code=$(get "/expenses" "$TOKEN"); check "GET /expenses" 200 "$code"

echo "== Notifications / Announcements =="
code=$(get "/notifications" "$TOKEN"); check "GET /notifications" 200 "$code"
code=$(get "/announcements" "$TOKEN"); check "GET /announcements" 200 "$code"

echo "== Staff / Settings =="
code=$(get "/staff" "$TOKEN"); check "GET /staff" 200 "$code"
code=$(get "/settings" "$TOKEN"); check "GET /settings" 200 "$code"

echo "== File upload (staff photo, 1x1 PNG) =="
STAFF_ID=$(jq -r '.data[0].id // empty' /tmp/smoke_body.json 2>/dev/null)
if [[ -z "$STAFF_ID" ]]; then
  code=$(get "/staff" "$TOKEN"); STAFF_ID=$(jq -r '.data[0].id // empty' /tmp/smoke_body.json)
fi
if [[ -n "$STAFF_ID" ]]; then
  PNG_B64="iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII="
  echo "$PNG_B64" | base64 -d > /tmp/smoke_pixel.png
  code=$(curl -s -o /dev/null -w '%{http_code}' -X POST "${BASE_URL}/staff/${STAFF_ID}/photo" \
    -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" \
    -F "photo=@/tmp/smoke_pixel.png;type=image/png")
  check "POST /staff/{id}/photo" 200 "$code"
  rm -f /tmp/smoke_pixel.png
else
  echo "  SKIP file upload check (no staff record found to attach a photo to)"
fi

echo "== Role/permission restriction (Trainer must be forbidden from /staff) =="
LOW_LOGIN=$(curl -s -X POST "${BASE_URL}/auth/login" \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d "{\"email\":\"${LOW_PRIV_EMAIL}\",\"password\":\"${LOW_PRIV_PASSWORD}\"}")
LOW_TOKEN=$(echo "$LOW_LOGIN" | jq -r '.data.token // empty')
if [[ -n "$LOW_TOKEN" ]]; then
  code=$(get "/staff" "$LOW_TOKEN"); check "GET /staff as Trainer (expect 403)" 403 "$code"
else
  echo "  SKIP permission check (could not log in as $LOW_PRIV_EMAIL — set LOW_PRIV_EMAIL/LOW_PRIV_PASSWORD)"
fi

echo "== Logout =="
code=$(curl -s -o /dev/null -w '%{http_code}' -X POST "${BASE_URL}/auth/logout" \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json")
check "POST /auth/logout" 200 "$code"

echo
echo "== Result: $PASS passed, $FAIL failed =="
[[ "$FAIL" -eq 0 ]]
