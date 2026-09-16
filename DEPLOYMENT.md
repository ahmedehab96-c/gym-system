# Deployment Guide

This document describes how to deploy the Premium Gym system (Laravel API +
React admin/Super Admin dashboards + Flutter Member/Trainer apps) to a
production environment. It does not assume a specific hosting provider
(VPS, managed PaaS, or containers all work) and does not cover DNS/domain
registration itself. Payment gateway, email, push, and WhatsApp provider
credentials ARE wired into the app (§3) — supplying real ones is a
one-time, provider-specific step this repo can't do on your behalf; with
none configured, those features simply no-op (see each service's "no
key, no attempt" guard) rather than erroring.

Ready-to-render Nginx and systemd configuration live in `deploy/` (see
§8 below); day-2 operations (logs, backups, rollback, maintenance mode)
are in `OPERATIONS.md`.

## 0. Domain Structure

The app is designed for a split-domain deployment:

- Frontend (React SPA): `https://<your-frontend-domain>`
- Backend API: `https://<your-api-domain>`

No domain is hardcoded anywhere in the codebase or in `deploy/` — both
sides read the other's URL from environment variables you supply:

- Backend: `FRONTEND_URL` in `backend/.env` (drives `config/cors.php`,
  restricting the API to that origin).
- Frontend: `VITE_API_BASE_URL` in `frontend/.env.production` (baked into
  the build).
- Nginx templates: `FRONTEND_DOMAIN` / `API_DOMAIN` in `deploy/.env.deploy`
  (see `deploy/.env.deploy.example`).

A single shared domain (e.g. `gym-domain.com` for the frontend and
`gym-domain.com/api` proxied to the backend) also works — just set
`FRONTEND_URL`/`VITE_API_BASE_URL` to match whatever split you choose;
nothing in the app assumes separate subdomains.

### CORS & Sanctum across domains

Authentication is a Sanctum **bearer token** (`Authorization: Bearer
<token>`), not a cookie — so there's no cross-subdomain cookie/CSRF
concern to configure. Concretely:

- `config/cors.php` allows only the origin(s) in `FRONTEND_URL`, with
  `supports_credentials: false` (no cookies are sent, so none need to be
  allowed). This is correct as-is for a split-domain deployment; it does
  not need to change when the frontend and API move to real domains,
  only `FRONTEND_URL` does.
- `SANCTUM_STATEFUL_DOMAINS` should stay **empty** in production — that
  setting is for cookie-based first-party SPA auth, which this app
  doesn't use. Setting it has no effect on bearer-token requests.
- After deploying to real domains, authentication should work
  immediately once `FRONTEND_URL` (backend) and `VITE_API_BASE_URL`
  (frontend) both point at the real HTTPS URLs — there is no other
  domain-specific auth wiring. Verify with `deploy/smoke-test.sh`
  (§11) pointed at the real API URL.

## 1. Server Requirements

- **PHP**: 8.4+ (project is developed against 8.5). Required extensions:
  `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`,
  `bcmath`, `fileinfo`, `curl`, `gd` or `imagick` (image uploads are
  validated but not processed/resized, so either is fine).
- **Composer**: 2.x.
- **MySQL**: 8.0+ (or MariaDB 10.6+). One database + one application user
  with privileges scoped to that database (not root).
- **Node.js**: 20+ and npm, needed only at build time for the frontend
  (the built static assets are what actually gets served — Node is not
  needed at runtime).
- A process supervisor (systemd, Supervisor, or your platform's
  equivalent) to keep the queue worker and PHP-FPM/app server running.
- A cron entry (or platform scheduler) for Laravel's scheduler.

## 2. Laravel (backend/) Installation

```sh
cd backend
composer install --no-dev --optimize-autoloader
cp .env.example .env   # then edit .env — see §3
php artisan key:generate
```

`--no-dev` skips dev-only packages (e.g. Laravel Boost, Pest/PHPUnit dev
tooling); the app itself doesn't need them at runtime.

## 3. Environment Variables

Copy `backend/.env.example` to `backend/.env` on the server and set real
values — **never commit a real `.env` file** (it's already gitignored).
At minimum, change these from their local-dev defaults:

| Variable | Production value |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` — **critical**: `true` leaks stack traces (file paths, query bindings) in API error responses |
| `APP_URL` | the real HTTPS URL of the API |
| `APP_KEY` | generate fresh via `php artisan key:generate` on the server, don't reuse a dev key |
| `FRONTEND_URL` | the deployed frontend's HTTPS origin(s), comma-separated — this drives `config/cors.php`, which restricts the API to only these origins |
| `LOG_LEVEL` | `error` or `warning` (not `debug`) |
| `DB_*` | production database host/name/user/password |
| `SESSION_SECURE_COOKIE` | `true` (app is served over HTTPS) |
| `MAIL_MAILER` / `MAIL_*` | a real SMTP provider if/when transactional email is needed — not required for the app to function today, since all in-app notifications go through the database-backed notification feed, not email |

Leave `SANCTUM_STATEFUL_DOMAINS` empty — the frontend authenticates with a
Sanctum bearer token (not cookies), so stateful-domain CSRF handling
doesn't apply here (see the comment in `config/cors.php`).

`FILESYSTEM_DISK`, `CACHE_STORE`, and `QUEUE_CONNECTION` default to
`local`/`database`/`database`, which work fine for a single-server
deployment with no extra infrastructure. If you later move to multiple
app servers behind a load balancer, switch `FILESYSTEM_DISK` to `s3` (an
S3-compatible disk is already configured in `config/filesystems.php`,
just needs `AWS_*` env vars) so uploaded images are shared across
instances, and consider `CACHE_STORE=redis` / `QUEUE_CONNECTION=redis`.

## 4. Database

```sh
php artisan migrate --force
```

`--force` is required because Laravel prompts for confirmation when
`APP_ENV=production`, as a safety net against running migrations by
accident.

**Do not run `php artisan db:seed` in production.** The seeders
(`database/seeders/*`) generate realistic-looking fake staff, members,
payments, etc. for local development and demos — they are not meant to
populate a real deployment. If you need an initial Super Admin account,
create one directly (e.g. via `php artisan tinker` or a one-off script),
not through the seeders.

All migrations were verified for foreign keys, indexes, and unique
constraints during QA (Phase 14) — no schema changes are needed for
production; `migrate --force` against a fresh database is sufficient.

## 5. Storage

```sh
php artisan storage:link
```

This creates the `public/storage` symlink to `storage/app/public`, which
is required for uploaded images (staff/trainer photos, equipment,
facility, program, announcement images, gym logo, expense receipts) to be
reachable over HTTP. Without it, every upload endpoint will still work but
the returned image URLs will 404.

Ensure the web server user can write to `storage/` and
`bootstrap/cache/` (standard Laravel permissions — `chmod -R 775` owned by
the web server user is the usual approach, adjust to your platform).

Uploads are stored as real files on disk (not base64 in the database) and
served via `Storage::url()`; every upload endpoint already validates MIME
type and file size server-side (see each `Upload*Request` in
`app/Http/Requests/`) — no changes needed for production.

## 6. Queue Worker & Scheduler

The in-app (database) notification feed is written synchronously, but
email/push/WhatsApp delivery for those same events is queued
(`app/Jobs/Send{Email,Push,WhatsApp}NotificationJob.php`, dispatched from
`NotificationChannelDispatcher`) — **a queue worker is required**, not
optional, or staff/members simply never receive those channels (the
in-app feed itself is unaffected either way):

```sh
# under Supervisor/systemd, not run directly:
php artisan queue:work --tries=3 --max-time=3600
```

The scheduler **is** required today — it runs the daily membership status
recalculation, which also generates the "Membership Expiring" / "Membership
Expired" staff notifications (`app/Console/Commands/UpdateMembershipStatuses.php`,
registered in `routes/console.php`). Add a single cron entry:

```
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```

Laravel's scheduler itself decides when each registered task actually
runs (the membership job runs `->daily()`); the cron entry just needs to
invoke `schedule:run` every minute.

Other notification types (new member, payment received/pending/failed,
class booking/cancellation, maintenance due/overdue) are generated
in real time by the relevant controller action, not on a schedule — no
separate cron job needed for those.

## 7. React (frontend/) Build

```sh
cd frontend
cp .env.example .env.production   # or set VITE_API_BASE_URL in your CI/host env
# edit VITE_API_BASE_URL to the production API's HTTPS URL + /api/v1
npm ci
npm run build
```

`npm run build` runs `tsc -b && vite build`, so it also fails the build on
any TypeScript error. Output goes to `frontend/dist/` — this is a static
asset bundle (HTML/JS/CSS); deploy it to any static host or serve it via
your web server (nginx/Apache/etc.), no Node runtime needed in
production. `VITE_API_BASE_URL` is inlined into the JS bundle at build
time, so it must be correct *before* running `npm run build`, not set as
a runtime environment variable afterward.

## 8. Web Server Configuration

Ready-to-render Nginx server blocks are in `deploy/nginx/*.conf.template`
(one for the API, one for the frontend SPA), plus a systemd unit for the
queue worker in `deploy/systemd/`. To use them:

```sh
cd deploy
cp .env.deploy.example .env.deploy   # fill in real domains/paths — see below
./render-templates.sh                # writes deploy/rendered/*.conf, *.service
# review deploy/rendered/, then:
sudo cp rendered/*.conf /etc/nginx/sites-available/
sudo ln -s /etc/nginx/sites-available/*.conf /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo cp rendered/*.service /etc/systemd/system/
sudo systemctl daemon-reload && sudo systemctl enable --now gym-queue-worker
```

What the templates do (see the files themselves for full detail and
comments):

- **API** (`api.conf.template`): document root `backend/public`, HTTP→HTTPS
  redirect, `.php` routed to PHP-FPM, `/storage/` served directly for
  uploaded files (no PHP involved), `.env`/`composer.*`/dotfiles denied,
  `client_max_body_size 10M` (headroom over the app's 5MB upload limit).
- **Frontend** (`frontend.conf.template`): document root `frontend/dist`,
  HTTP→HTTPS redirect, hashed `/assets/` cached for a year, everything
  else falls back to `index.html` (`try_files $uri $uri/ /index.html;`)
  so React Router's client-side routes survive a hard refresh, and
  `index.html` itself is never cached so deploys take effect immediately.

Nginx doesn't read `.env` files natively, so `deploy/.env.deploy` (domains,
absolute paths, PHP-FPM socket, TLS cert paths, web server user) is a
separate, infrastructure-only set of variables consumed by
`render-templates.sh` via `envsubst` — this is the "use environment
variables, don't hardcode domains" requirement applied to Nginx config
specifically. It is distinct from `backend/.env` / `frontend/.env.production`,
which hold the *application's* configuration. `deploy/.env.deploy` is
gitignored; only `.env.deploy.example` (no real values) is committed.

TLS certificates (e.g. via `certbot`) are assumed to already exist at the
paths given in `.env.deploy` — obtaining them is a one-time,
domain-specific step this repo can't do on your behalf (see §12).

## 9. Recommended Production Directory Structure

```
/var/www/gym-system/
├── backend/            # Laravel app (composer install --no-dev; storage/, bootstrap/cache/ writable)
│   ├── public/         # web server document root for the API
│   └── storage/app/public/   # uploaded files (symlinked from public/storage)
└── frontend-dist/      # `frontend/dist` output, deployed separately (static host or same box)
```

Keep `backend/.env` outside version control on the server (it already is
via `.gitignore`) and restrict its file permissions (e.g. `chmod 600`) —
it holds the database password and `APP_KEY`.

## 10. Automated Deploy Scripts

`deploy/deploy-backend.sh` and `deploy/build-frontend.sh` wrap the steps
in §2–§7 into two idempotent scripts (safe to re-run on every deploy):

```sh
./deploy/deploy-backend.sh    # composer install --no-dev, key:generate if
                               # unset, migrate --force, storage:link,
                               # config/route/event/view cache, permissions
./deploy/build-frontend.sh    # npm ci, tsc + vite build
```

Both refuse to run against an obviously-unconfigured environment (e.g.
`deploy-backend.sh` aborts if `.env` still says `APP_ENV=local`, or is
missing entirely) rather than silently proceeding with wrong settings.

## 11. Production Verification

`deploy/smoke-test.sh` exercises every module in the verification
checklist — auth (login/logout), dashboard, members, memberships,
attendance, trainers, programs, classes, schedule, equipment,
maintenance, payments, invoices, expenses, notifications, announcements,
staff, settings, a file upload, and a role/permission restriction check
(logs in as a low-privilege seeded role and confirms a 403 on a
staff-only endpoint) — against a running instance of the API:

```sh
BASE_URL=https://api.gym-domain.com/api/v1 \
ADMIN_EMAIL=<real admin email> ADMIN_PASSWORD='<real password>' \
LOW_PRIV_EMAIL=<real low-privilege email> LOW_PRIV_PASSWORD='<real password>' \
./deploy/smoke-test.sh
```

Run it immediately after deploying, pointed at the real production URL
and real credentials, to confirm the checklist end-to-end. With no
arguments it defaults to the local dev stack and the seeded dev
credentials, which is how it was validated during this phase (see the
accompanying report) — that run proves the script and every listed flow
work correctly, but it is **not** a substitute for re-running it against
the actual production deployment once one exists.

`deploy/e2e-test.sh` goes further: it drives the actual business-lifecycle
scenarios (member → membership → payment → check-in/out → history;
membership suspend/cancel/renew; class booking + capacity limit +
cancellation; payment refund; equipment/maintenance/overdue; staff
create/role/permission/login/deactivate; notifications generate/read/mark-
all-read/delete) and per-role access restrictions (Receptionist, Trainer,
Accountant) against a live server, asserting both HTTP status codes and
response field values at each step. Same usage pattern:

```sh
BASE_URL=https://api.gym-domain.com/api/v1 ./deploy/e2e-test.sh
```

It creates real records (a test member, class, equipment item, staff
account, etc.) — safe to run against a fresh staging database, but avoid
running it against a production database already holding real member
data unless you're comfortable with a few clearly-named "E2E Test ..."
rows being created (nothing it creates is ever assumed to be cleaned up
automatically).

## 12. Final Local Verification (repeat before every release)

```sh
# backend/
php artisan test
php artisan config:cache && php artisan config:clear   # confirms config caches cleanly
php artisan route:cache && php artisan route:clear     # confirms no un-cacheable (closure) routes issue

# frontend/
npx tsc -b tsconfig.app.json --noEmit
npm run lint
npm run build
```

## 13. Monitoring & Recovery

See `OPERATIONS.md` for logs, failed-queue handling, database backup,
rollback, cache-clearing, and maintenance-mode procedures.

## 14. Mobile (Flutter Member/Trainer Apps) Build & Release

Both apps live in one Flutter project (`mobile/`) — see `mobile/README.md`
for the feature layout. Neither is published to a store yet.

### API URL

The API base URL is compiled in at build time, not read from a runtime
`.env`:

```sh
flutter build apk --release --dart-define=API_BASE_URL=https://api.gym-domain.com/api/v1
flutter build appbundle --release --dart-define=API_BASE_URL=https://api.gym-domain.com/api/v1   # Play Store upload format
```

Omitting `--dart-define` falls back to `http://10.0.2.2:8000/api/v1`
(`lib/core/config/app_config.dart`) — the Android emulator's alias for a
local backend — which is correct for development but must never be used
for a release build.

### Android signing

`android/app/build.gradle.kts` currently signs release builds with the
**debug** key (a `TODO` there says so explicitly) so `flutter build apk
--release` works out of the box for testing. Before a real Play Store
upload:

1. Generate a real upload keystore (do this once, store it somewhere
   durable — losing it means you can never update the app again):
   ```sh
   keytool -genkey -v -keystore ~/upload-keystore.jks -keyalg RSA \
     -keysize 2048 -validity 10000 -alias upload
   ```
2. Create `android/key.properties` (gitignored — never commit it) with
   the keystore path/passwords, and point `signingConfigs.release` at it
   instead of `signingConfigs.getByName("debug")`. This is the standard
   Flutter Android release-signing setup; see
   https://docs.flutter.dev/deployment/android for the exact
   `key.properties` + `build.gradle.kts` wiring.

### iOS signing

Confirmed to compile (`flutter build ios --release --no-codesign`
succeeds; bundle id `com.premiumgym.gym_member_app`). A real device/App
Store build additionally needs an Apple Developer Program account, a
distribution certificate, and a provisioning profile configured in
Xcode (`ios/Runner.xcworkspace` → Signing & Capabilities) — none of
which this repo can generate; see
https://docs.flutter.dev/deployment/ios.

### Pre-release checklist

- [ ] `flutter analyze` clean, `flutter test` passing.
- [ ] Built with the real production `API_BASE_URL` (see above) — not
      the emulator default.
- [ ] `pubspec.yaml`'s `version:` bumped (`X.Y.Z+buildNumber` — the
      build number must increase on every store upload).
- [ ] Android: signed with the real upload keystore, not the debug key.
- [ ] iOS: signed with a distribution certificate/provisioning profile.
- [ ] Store listing assets (icon, screenshots, privacy policy URL)
      prepared — outside this repo's scope.
