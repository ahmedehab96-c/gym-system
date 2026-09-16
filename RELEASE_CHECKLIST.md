# Release Checklist

Run through this before every production release. It's a checklist, not
a tutorial — see `DEPLOYMENT.md` and `OPERATIONS.md` for the detailed
commands each item refers to.

## Environment Configuration
- [ ] `backend/.env` created from `.env.example` with real production values (not copied from a dev machine).
- [ ] `APP_ENV=production`, `APP_DEBUG=false` (never `true` — leaks stack traces).
- [ ] `APP_KEY` freshly generated on the server (`php artisan key:generate`), not reused from dev.
- [ ] `APP_URL` set to the real API HTTPS URL.
- [ ] `FRONTEND_URL` set to the real frontend HTTPS origin(s) (drives CORS).
- [ ] `frontend/.env.production` has `VITE_API_BASE_URL` set to the real API URL *before* `npm run build` (it's inlined at build time).
- [ ] `LOG_LEVEL=error` (or `warning`), not `debug`.
- [ ] No `.env` file committed to version control (already gitignored in both `backend/` and `frontend/`).

## Database Migration
- [ ] Database and application DB user created, scoped to that database only (not root).
- [ ] `php artisan migrate --force` run successfully.
- [ ] Foreign keys, indexes, and unique constraints verified during QA (Phase 14) — no outstanding schema concerns.
- [ ] **`php artisan db:seed` NOT run in production** — seeders are dev/demo fixtures only.
- [ ] Initial Super Admin account created directly (tinker or a one-off script), not via seeders.

## Storage Setup
- [ ] `php artisan storage:link` run (required for uploaded images to be reachable over HTTP).
- [ ] `storage/` and `bootstrap/cache/` writable by the web server user (`chmod -R 775`, owned by that user).
- [ ] Confirmed uploads are stored as real files (not base64 in the DB) — no change needed, already verified.

## Queue Worker
- [ ] Queue worker installed and running (`deploy/systemd/gym-queue-worker.service.template`), even though no jobs are queued today — avoids a gap when one is added later.
- [ ] `QUEUE_CONNECTION=database` (or an alternative you've provisioned) confirmed in `.env`.

## Scheduler
- [ ] Cron entry installed: `* * * * * cd <backend> && php artisan schedule:run >> /dev/null 2>&1`.
- [ ] Confirmed this drives the daily `memberships:update-statuses` job, which also generates the "Membership Expiring"/"Membership Expired" staff notifications.

## HTTPS
- [ ] TLS certificates issued for both domains (e.g. via certbot) at the paths referenced in `deploy/.env.deploy`.
- [ ] Nginx configs rendered from `deploy/nginx/*.conf.template` and installed; `nginx -t` passes.
- [ ] HTTP→HTTPS redirect confirmed on both the frontend and API domains.
- [ ] `SESSION_SECURE_COOKIE=true` set (app is served over HTTPS).

## CORS
- [ ] `FRONTEND_URL` matches the real deployed frontend origin exactly (scheme + host, comma-separate if more than one).
- [ ] Confirmed `config/cors.php` still restricts to only these origins (`supports_credentials: false` — no change needed, bearer-token auth doesn't use cookies).

## Sanctum
- [ ] `SANCTUM_STATEFUL_DOMAINS` left **empty** — not applicable to this app's bearer-token auth model.
- [ ] Confirmed login → token → authenticated request round-trip works against the real deployed API (see Final Smoke Tests below).

## Admin Account Creation
- [ ] At least one Super Admin account created on the production database (not from seeders).
- [ ] Verified that account can log in and reach `/auth/me` successfully.
- [ ] Confirmed the account's password is not a placeholder/dev value.
- [ ] Ran `php artisan permissions:grant-all "Super Admin"` — **required**, not
      optional. Every permission-gated route checks `role_permissions` with no
      role-based bypass (not even Super Admin), and `database/seeders/StaffSeeder`
      (the only thing that normally populates that table) is a demo seeder that
      must never run in production. Without this, a fresh Super Admin account can
      log in but gets 403 on every gated action, including opening Roles &
      Permissions itself to fix it. This command creates no demo data — only
      permission rows for the one named role.

  Grant additional roles the same way as needed later, e.g.
  `php artisan permissions:grant-all Manager` — or configure them more
  precisely through the Roles & Permissions screen once the Super Admin can
  reach it.

## Backup
- [ ] Automated `mysqldump` backup scheduled (see `OPERATIONS.md` for a ready-to-use cron example).
- [ ] Restore procedure tested at least once against a non-production copy.
- [ ] DB credentials used for backup stored outside the crontab (e.g. `~/.my.cnf`, `chmod 600`).

## Logs
- [ ] Confirmed `backend/storage/logs/laravel.log` is being written and is readable by whoever will investigate incidents.
- [ ] Nginx access/error logs confirmed at the paths in the rendered Nginx configs.
- [ ] Log rotation in place (Laravel's default `single`/`daily` channel, or the OS's `logrotate`) so logs don't grow unbounded.

## Rollback Procedure
- [ ] Releases deployed to timestamped directories with a `current` symlink (see `OPERATIONS.md`), so rollback is a symlink swap, not a redeploy.
- [ ] Confirmed the rollback command (`ln -sfn ... current && php artisan config:cache`) has been documented and is known to whoever is on call.
- [ ] Decided in advance: does this release include a migration that would need special handling (`migrate:rollback` risk) if rolled back? If yes, documented the specific rollback caveat for this release.

## Final Smoke Tests
- [ ] `deploy/smoke-test.sh` run against the real production `BASE_URL` with real credentials — all checks pass.
- [ ] `deploy/e2e-test.sh` run against the real production `BASE_URL` — full business-lifecycle and role-restriction checks pass (member → membership → payment → check-in/out → history; membership suspend/cancel/renew; class booking + capacity; payment refund; equipment/maintenance; staff role/permission/deactivate; notifications).
- [ ] Manually logged in through the real frontend URL as at least: Super Admin, and one of Receptionist/Trainer/Accountant — confirm the dashboard loads, navigation reflects the role's permissions, and logout works.
- [ ] Confirmed uploaded file (e.g. a staff photo) is reachable over HTTPS at its returned URL.
- [ ] Confirmed a deliberately-wrong login (bad password) is rejected, and that 7 rapid login attempts in a row trigger the rate limiter (429) rather than allowing unlimited attempts.
