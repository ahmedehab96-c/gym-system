#!/usr/bin/env bash
# Phase 17 release validation: exercises full business-lifecycle scenarios
# and per-role access restrictions against a LIVE running instance of the
# API (not PHPUnit's in-memory test DB) — i.e. what a real user session
# would do. Run against the local dev stack to validate before a release,
# and again against staging/production after deploying.
#
# Usage: BASE_URL=... ./deploy/e2e-test.sh
# Defaults to the local dev stack + seeded dev credentials.

set -uo pipefail
BASE_URL="${BASE_URL:-http://localhost:8000/api/v1}"

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

req() { # req METHOD path token [json-body]
  local method="$1" path="$2" token="$3" body="${4:-}"
  if [[ -n "$body" ]]; then
    curl -s -o /tmp/e2e_body.json -w '%{http_code}' -X "$method" "${BASE_URL}$path" \
      -H "Authorization: Bearer $token" -H "Accept: application/json" -H "Content-Type: application/json" -d "$body"
  else
    curl -s -o /tmp/e2e_body.json -w '%{http_code}' -X "$method" "${BASE_URL}$path" \
      -H "Authorization: Bearer $token" -H "Accept: application/json"
  fi
}

login() { # login email password -> echoes token
  curl -s -X POST "${BASE_URL}/auth/login" -H "Content-Type: application/json" -H "Accept: application/json" \
    -d "{\"email\":\"$1\",\"password\":\"$2\"}" | jq -r '.data.token // empty'
}

jval() { jq -r "$1" /tmp/e2e_body.json; }

ADMIN_TOKEN=$(login "ahmed.ehab@premiumgym.com" "password")
if [[ -z "$ADMIN_TOKEN" ]]; then echo "FATAL: admin login failed, aborting" >&2; exit 1; fi

# ============================================================
# 1. Role-based access verification (Receptionist / Trainer / Accountant)
# ============================================================
echo "== Role restrictions =="

RECEPTIONIST_TOKEN=$(login "salma.adly@premiumgym.com" "password")
code=$(req GET "/members" "$RECEPTIONIST_TOKEN"); check "Receptionist: GET /members (allowed)" 200 "$code"
code=$(req GET "/staff" "$RECEPTIONIST_TOKEN"); check "Receptionist: GET /staff (forbidden)" 403 "$code"
code=$(req POST "/expenses" "$RECEPTIONIST_TOKEN" '{}'); check "Receptionist: POST /expenses (forbidden)" 403 "$code"
code=$(req GET "/members?per_page=1" "$RECEPTIONIST_TOKEN"); EXISTING_MEMBER_ID=$(jval '.data[0].id')
code=$(req DELETE "/members/${EXISTING_MEMBER_ID}" "$RECEPTIONIST_TOKEN"); check "Receptionist: DELETE /members/{real-id} (no delete perm, forbidden)" 403 "$code"

TRAINER_TOKEN=$(login "karim.adel@premiumgym.com" "password")
code=$(req GET "/members" "$TRAINER_TOKEN"); check "Trainer: GET /members (view-only, allowed)" 200 "$code"
code=$(req POST "/members" "$TRAINER_TOKEN" '{}'); check "Trainer: POST /members (no create perm, forbidden)" 403 "$code"
code=$(req GET "/payments" "$TRAINER_TOKEN"); check "Trainer: GET /payments (no view perm, forbidden)" 403 "$code"
code=$(req GET "/staff" "$TRAINER_TOKEN"); check "Trainer: GET /staff (forbidden)" 403 "$code"

ACCOUNTANT_TOKEN=$(login "hany.fekry@premiumgym.com" "password")
if [[ -n "$ACCOUNTANT_TOKEN" ]]; then
  code=$(req GET "/payments" "$ACCOUNTANT_TOKEN"); check "Accountant: GET /payments (allowed)" 200 "$code"
  code=$(req GET "/expenses" "$ACCOUNTANT_TOKEN"); check "Accountant: GET /expenses (allowed)" 200 "$code"
  code=$(req GET "/members" "$ACCOUNTANT_TOKEN"); check "Accountant: GET /members (no view perm, forbidden)" 403 "$code"
  code=$(req GET "/staff" "$ACCOUNTANT_TOKEN"); check "Accountant: GET /staff (forbidden)" 403 "$code"
else
  echo "  SKIP Accountant checks (seeded Accountant account is Inactive by default and could not log in)"
fi

# ============================================================
# 2. Member -> Membership -> Payment -> Check-in -> Check-out -> History
# ============================================================
echo "== Member lifecycle =="

code=$(req GET "/membership-plans" "$ADMIN_TOKEN"); check "GET /membership-plans" 200 "$code"
PLAN_ID=$(jval '.data[0].id')

MEMBER_EMAIL="e2e.$(date +%s)@example.test"
code=$(req POST "/members" "$ADMIN_TOKEN" "{\"name\":\"E2E Test Member\",\"email\":\"${MEMBER_EMAIL}\",\"phone\":\"+20 100 000 9999\",\"gender\":\"Male\"}")
check "POST /members (create)" 201 "$code"
MEMBER_ID=$(jval '.data.id')

code=$(req POST "/memberships" "$ADMIN_TOKEN" "{\"member_id\":${MEMBER_ID},\"plan_id\":${PLAN_ID}}")
check "POST /memberships (create, member has none yet)" 201 "$code"
MEMBERSHIP_ID=$(jval '.data.id')

code=$(req POST "/memberships" "$ADMIN_TOKEN" "{\"member_id\":${MEMBER_ID},\"plan_id\":${PLAN_ID}}")
check "POST /memberships (duplicate active, must reject)" 422 "$code"

code=$(req POST "/payments" "$ADMIN_TOKEN" "{\"member_id\":${MEMBER_ID},\"amount\":500,\"method\":\"Cash\",\"status\":\"Paid\"}")
check "POST /payments (record payment)" 201 "$code"

code=$(req POST "/attendance/check-in" "$ADMIN_TOKEN" "{\"member_id\":${MEMBER_ID}}")
check "POST /attendance/check-in" 201 "$code"
ATTENDANCE_ID=$(jval '.data.id')

code=$(req POST "/attendance/${ATTENDANCE_ID}/check-out" "$ADMIN_TOKEN")
check "POST /attendance/{id}/check-out" 200 "$code"

code=$(req GET "/members/${MEMBER_ID}/attendance" "$ADMIN_TOKEN")
check "GET /members/{id}/attendance (history)" 200 "$code"
check "  -> history has 1 record" "1" "$(jval '.data | length')"

code=$(req GET "/members/${MEMBER_ID}/payments" "$ADMIN_TOKEN")
check "GET /members/{id}/payments (history)" 200 "$code"

# ============================================================
# 3. Membership: Active -> Expiring -> Expired -> Renew -> Suspend -> Cancel
# ============================================================
echo "== Membership lifecycle =="

code=$(req GET "/memberships/${MEMBERSHIP_ID}" "$ADMIN_TOKEN")
check "GET /memberships/{id} (Active)" 200 "$code"
check "  -> status is Active" "Active" "$(jval '.data.status')"

code=$(req POST "/memberships/${MEMBERSHIP_ID}/suspend" "$ADMIN_TOKEN")
check "POST /memberships/{id}/suspend" 200 "$code"
check "  -> status is Suspended" "Suspended" "$(jval '.data.status')"

code=$(req POST "/memberships/${MEMBERSHIP_ID}/cancel" "$ADMIN_TOKEN")
check "POST /memberships/{id}/cancel" 200 "$code"
check "  -> status is Expired" "Expired" "$(jval '.data.status')"

code=$(req POST "/memberships/${MEMBERSHIP_ID}/renew" "$ADMIN_TOKEN")
check "POST /memberships/{id}/renew (after cancel)" 200 "$code"
check "  -> status is Active again" "Active" "$(jval '.data.status')"

# ============================================================
# 4. Class: Create -> Schedule -> Booking -> Capacity -> Cancellation
# ============================================================
echo "== Class lifecycle =="

# Randomize date/time each run so repeat runs against the same dev DB
# don't collide with a previous run's leftover test class on the same
# trainer/date/time (ClassScheduleService rejects double-booking a trainer).
code=$(req GET "/trainers" "$ADMIN_TOKEN"); TRAINER_ID=$(jval '.data[0].id')
RAND_DAY_OFFSET=$(( (RANDOM % 300) + 30 ))
RAND_DATE=$(date -v+${RAND_DAY_OFFSET}d +%F 2>/dev/null || date -d "+${RAND_DAY_OFFSET} days" +%F)
RAND_HOUR=$(( (RANDOM % 12) + 6 ))
START_TIME=$(printf "%02d:00" "$RAND_HOUR")
END_TIME=$(printf "%02d:00" "$((RAND_HOUR + 1))")
code=$(req POST "/classes" "$ADMIN_TOKEN" "{\"name\":\"E2E Test Class\",\"category\":\"Yoga\",\"trainer_id\":${TRAINER_ID},\"date\":\"${RAND_DATE}\",\"day\":\"Friday\",\"start_time\":\"${START_TIME}\",\"end_time\":\"${END_TIME}\",\"capacity\":1}")
check "POST /classes (create, capacity 1)" 201 "$code"
CLASS_ID=$(jval '.data.id')

code=$(req POST "/classes/${CLASS_ID}/book" "$ADMIN_TOKEN" "{\"member_id\":${MEMBER_ID}}")
check "POST /classes/{id}/book (fills capacity)" 201 "$code"
check "  -> class status is Full" "Full" "$(jval '.data.status')"

code=$(req POST "/members" "$ADMIN_TOKEN" "{\"name\":\"E2E Second Member\",\"email\":\"e2e2.$(date +%s)@example.test\",\"phone\":\"+20 100 000 8888\",\"gender\":\"Female\"}")
MEMBER2_ID=$(jval '.data.id')
code=$(req POST "/classes/${CLASS_ID}/book" "$ADMIN_TOKEN" "{\"member_id\":${MEMBER2_ID}}")
check "POST /classes/{id}/book (over capacity, must reject)" 422 "$code"

code=$(req DELETE "/classes/${CLASS_ID}/book/${MEMBER_ID}" "$ADMIN_TOKEN")
check "DELETE /classes/{id}/book/{member} (cancel booking)" 200 "$code"
check "  -> class status back to Scheduled" "Scheduled" "$(jval '.data.status')"

# ============================================================
# 5. Payment: Invoice -> Status -> Revenue -> Refund
# ============================================================
echo "== Payment / Invoice / Revenue =="

code=$(req POST "/invoices" "$ADMIN_TOKEN" "{\"member_id\":${MEMBER_ID},\"due_date\":\"2027-02-01\",\"items\":[{\"description\":\"Test line item\",\"amount\":500}]}")
check "POST /invoices (create)" 201 "$code"

code=$(req GET "/payments/stats" "$ADMIN_TOKEN"); check "GET /payments/stats (revenue)" 200 "$code"
code=$(req GET "/finance/overview" "$ADMIN_TOKEN"); check "GET /finance/overview" 200 "$code"

code=$(req POST "/payments" "$ADMIN_TOKEN" "{\"member_id\":${MEMBER_ID},\"amount\":500,\"method\":\"Cash\",\"status\":\"Paid\"}")
PAYMENT_ID=$(jval '.data.id')
code=$(req POST "/payments/${PAYMENT_ID}/refund" "$ADMIN_TOKEN")
check "POST /payments/{id}/refund" 200 "$code"
check "  -> status is Refunded" "Refunded" "$(jval '.data.status')"

# ============================================================
# 6. Equipment -> Maintenance -> Cost -> History -> Overdue
# ============================================================
echo "== Equipment / Maintenance =="

code=$(req POST "/equipment" "$ADMIN_TOKEN" '{"name":"E2E Treadmill","category":"Cardio"}')
check "POST /equipment (create)" 201 "$code"
EQUIPMENT_ID=$(jval '.data.id')

code=$(req POST "/maintenance" "$ADMIN_TOKEN" "{\"equipment_id\":${EQUIPMENT_ID},\"type\":\"Inspection\",\"date\":\"2020-01-01\",\"cost\":150,\"status\":\"Upcoming\"}")
check "POST /maintenance (past date, should read as overdue)" 201 "$code"

code=$(req GET "/equipment/${EQUIPMENT_ID}/maintenance" "$ADMIN_TOKEN")
check "GET /equipment/{id}/maintenance (history)" 200 "$code"

code=$(req GET "/maintenance/overdue" "$ADMIN_TOKEN")
check "GET /maintenance/overdue" 200 "$code"
OVERDUE_COUNT=$(jval '[.data[] | select(.equipmentId=='"${EQUIPMENT_ID}"')] | length' 2>/dev/null || echo 0)
check "  -> new record appears in overdue list" "1" "$OVERDUE_COUNT"

code=$(req GET "/maintenance/stats" "$ADMIN_TOKEN"); check "GET /maintenance/stats (cost totals)" 200 "$code"

# ============================================================
# 7. Staff: Create -> Role -> Permission -> Login -> Restriction -> Deactivate
# ============================================================
echo "== Staff lifecycle =="

STAFF_EMAIL="e2e.staff.$(date +%s)@example.test"
code=$(req POST "/staff" "$ADMIN_TOKEN" "{\"name\":\"E2E Staff\",\"email\":\"${STAFF_EMAIL}\",\"password\":\"password123\",\"role\":\"Receptionist\"}")
check "POST /staff (create)" 201 "$code"
STAFF_ID=$(jval '.data.id')

code=$(req PATCH "/staff/${STAFF_ID}/role" "$ADMIN_TOKEN" '{"role":"Manager"}')
check "PATCH /staff/{id}/role" 200 "$code"

code=$(req PATCH "/staff/${STAFF_ID}/role" "$ADMIN_TOKEN" '{"role":"Super Admin"}')
STAFF2_TOKEN_CHECK="$code"
# Only meaningful if the acting admin isn't already Super Admin; ahmed.ehab IS
# Super Admin, so this is expected to succeed here (see role-restriction
# checks above for the non-Super-Admin-cannot-escalate case, covered by
# backend test suite: StaffTest::test_an_admin_cannot_promote...).
check "PATCH /staff/{id}/role -> Super Admin (acting user IS Super Admin, allowed)" 200 "$STAFF2_TOKEN_CHECK"

NEW_STAFF_TOKEN=$(login "$STAFF_EMAIL" "password123")
check "New staff member can log in" "1" "$([[ -n "$NEW_STAFF_TOKEN" ]] && echo 1 || echo 0)"

code=$(req PATCH "/staff/${STAFF_ID}/status" "$ADMIN_TOKEN" '{"status":"Inactive"}')
check "PATCH /staff/{id}/status (deactivate)" 200 "$code"

DEACTIVATED_LOGIN=$(login "$STAFF_EMAIL" "password123")
check "Deactivated staff cannot log in" "1" "$([[ -z "$DEACTIVATED_LOGIN" ]] && echo 1 || echo 0)"

# ============================================================
# 8. Notifications: Generate -> Read -> Mark all read -> Delete
# ============================================================
echo "== Notifications =="

code=$(req GET "/notifications" "$ADMIN_TOKEN")
check "GET /notifications (generated by the actions above)" 200 "$code"
NOTIF_COUNT=$(jval '.data | length')
check "  -> at least one notification exists" "1" "$([[ "$NOTIF_COUNT" -ge 1 ]] && echo 1 || echo 0)"
NOTIF_ID=$(jval '.data[0].id')

code=$(req PATCH "/notifications/${NOTIF_ID}" "$ADMIN_TOKEN" '{"read":true}')
check "PATCH /notifications/{id} (mark read)" 200 "$code"

code=$(req POST "/notifications/mark-all-read" "$ADMIN_TOKEN")
check "POST /notifications/mark-all-read" 200 "$code"

code=$(req DELETE "/notifications/${NOTIF_ID}" "$ADMIN_TOKEN")
check "DELETE /notifications/{id}" 200 "$code"

# ============================================================
# 9. API contract spot-checks: pagination, search, filters, validation
# ============================================================
echo "== API contract: pagination / search / filters / validation errors =="

code=$(req GET "/members?per_page=1" "$ADMIN_TOKEN")
check "GET /members?per_page=1 (pagination)" 200 "$code"
check "  -> meta.perPage respected" "1" "$(jval '.meta.perPage')"

code=$(req GET "/members?search=zzznonexistentzzz" "$ADMIN_TOKEN")
check "GET /members?search=... (search, empty result)" 200 "$code"
check "  -> zero results" "0" "$(jval '.data | length')"

code=$(req GET "/members?status=Active" "$ADMIN_TOKEN")
check "GET /members?status=Active (filter)" 200 "$code"

code=$(req POST "/members" "$ADMIN_TOKEN" '{}')
check "POST /members with no body (validation error)" 422 "$code"
check "  -> error response has 'errors' object" "object" "$(jval '.errors | type')"

code=$(req GET "/members/999999999" "$ADMIN_TOKEN")
check "GET /members/{missing} (404)" 404 "$code"

code=$(curl -s -o /dev/null -w '%{http_code}' "${BASE_URL}/members")
check "GET /members with no token (401)" 401 "$code"

echo
echo "== Result: $PASS passed, $FAIL failed =="
[[ "$FAIL" -eq 0 ]]
