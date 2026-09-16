# Operations Runbook

Day-2 procedures for running the deployed app: where to look when
something's wrong, and how to recover. No monitoring services are
introduced here — everything below uses tools already part of Laravel,
MySQL, or standard Linux.

## Logs

| What | Where |
|---|---|
| Laravel application log | `backend/storage/logs/laravel.log` (rotates per `config/logging.php`'s `stack`/`single` channel) |
| Nginx access/error logs | `/var/log/nginx/<domain>-access.log` / `-error.log` (paths from `deploy/nginx/*.conf.template`) |
| PHP-FPM log | wherever your distro's PHP-FPM pool config points it (commonly `/var/log/php8.4-fpm.log`) |
| Queue worker output | `journalctl -u gym-queue-worker` (systemd unit in `deploy/systemd/`) |

Quick tail while reproducing an issue:

```sh
tail -f backend/storage/logs/laravel.log
```

With `APP_DEBUG=false` (required in production, see `DEPLOYMENT.md`),
API error responses only ever return a generic message — the real
exception and stack trace are in `laravel.log`, not in the HTTP
response. That's where to look first for any reported 500.

## Failed Queue Jobs

Email/push/WhatsApp notification delivery is queued
(`app/Jobs/Send{Email,Push,WhatsApp}NotificationJob.php`); the in-app
(database) notification feed itself writes synchronously and is
unaffected if the worker is down. A worker is expected to be running
(`deploy/systemd/gym-queue-worker.service.template`):

```sh
php artisan queue:failed          # list failed jobs
php artisan queue:retry <id>      # retry one, or `all`
php artisan queue:flush           # discard all failed jobs
```

If the worker itself is down: `systemctl status gym-queue-worker` /
`systemctl restart gym-queue-worker`.

## Database Backup

Plain `mysqldump` on a cron schedule is sufficient at this scale — no
extra backup service needed:

```sh
# /etc/cron.d/gym-db-backup (adjust paths/retention to your host)
0 3 * * * root mysqldump --single-transaction -u gym_user -p'<password>' gym_system \
  | gzip > /var/backups/gym-system/gym_system-$(date +\%Y\%m\%d).sql.gz \
  && find /var/backups/gym-system -name '*.sql.gz' -mtime +14 -delete
```

Restore:

```sh
gunzip -c /var/backups/gym-system/gym_system-YYYYMMDD.sql.gz | mysql -u gym_user -p gym_system
```

Store the DB password in a root-only-readable credentials file
(`~/.my.cnf` with `chmod 600`) rather than inline in the crontab, to keep
it out of `crontab -l` output and process listings.

## Application Rollback

Recommended layout: deploy each release to its own timestamped directory
and symlink `current` to the active one (a standard "atomic deploy"
pattern) so rollback is just repointing the symlink:

```
/var/www/gym-system/
├── releases/
│   ├── 2026-09-08-1200/
│   └── 2026-09-09-0930/   <- current release
└── current -> releases/2026-09-09-0930/
```

Nginx's `root`/`fastcgi_param SCRIPT_FILENAME` should point at
`.../current/...`, not a specific release directory, so switching
releases doesn't require an Nginx reload.

Rollback:

```sh
ln -sfn /var/www/gym-system/releases/2026-09-08-1200 /var/www/gym-system/current
php /var/www/gym-system/current/backend/artisan config:cache   # re-cache under the new path
```

If the release being rolled back *from* ran new migrations, decide
whether `php artisan migrate:rollback` is safe first — rolling back a
migration that already has production data depending on it (e.g. a
dropped column) can lose data. When in doubt, roll back the application
code only and leave the schema forward-compatible, rather than rolling
back migrations blindly.

## Cache Clearing

After any config/env change or a deploy that didn't go through
`deploy/deploy-backend.sh`:

```sh
php artisan config:clear && php artisan config:cache
php artisan route:clear  && php artisan route:cache
php artisan view:clear   && php artisan view:cache
php artisan event:clear  && php artisan event:cache
```

`config:clear` alone (without re-caching) is useful when debugging a
production issue that might be caused by a stale cached config —
uncached config is slower but always reads live `.env` values.

## Monitoring Points

No external monitoring service is introduced here (per Phase 30 — avoid
adding one unless actually needed); this is what to point whatever you
already have (uptime pinger, log shipper, alert rule) at:

| Concern | Where to look |
|---|---|
| App liveness | `GET /up` (Laravel's built-in health check — confirms the app boots and its `AppServiceProvider`-registered checks pass; wire an uptime monitor at this URL) |
| API errors (5xx) | `laravel.log` — every unexpected exception is `report()`-ed there before the client gets a generic message (see `bootstrap/app.php`'s catch-all exception render) |
| Authentication failures | `laravel.log` for repeated 401s from one IP/email; Sanctum's own `throttle:6,1` on every login route already rate-limits brute-forcing (see `routes/api.php`) |
| Failed jobs / queue health | `php artisan queue:failed` (see above); alert if the queue worker's systemd unit isn't `active` |
| Scheduler failures | `UpdateMembershipStatuses` (daily) and `UpdateSubscriptionStatuses` logs to `laravel.log` on error; alert if `schedule:run`'s cron entry stops firing (no output is normal — silence for >24h is the signal) |
| Payment webhook failures | `payment_webhook_events` table records every *verified* event (idempotency ledger); a signature that fails verification never reaches it and instead logs `Rejected a payment webhook request...` to `laravel.log` (`VerifyPaymentWebhookSignature`) — repeated rejections mean a misconfigured `STRIPE_WEBHOOK_SECRET` or a spoofing attempt |
| Database performance | Standard MySQL slow-query log (`long_query_time` in `my.cnf`) — nothing app-specific needed |
| Storage failures | An upload endpoint returning 500 with `laravel.log` showing a filesystem exception; check disk space / `storage/` permissions first |
| AI provider failures | `AIProviderException` responses (502, see `bootstrap/app.php`) — logged with the provider's error in `laravel.log`; the app degrades gracefully (AI features become unavailable, nothing else breaks) |

## Maintenance Mode

```sh
php artisan down --secret="<a-hard-to-guess-token>" --retry=60
# ... do the risky work (e.g. a migration with downtime, a hotfix) ...
php artisan up
```

While down, staff can still reach the app by visiting
`https://api.gym-domain.com/<the-secret-token>` (Laravel sets a bypass
cookie), while all other visitors see a 503. Keep this brief — the
frontend has no special handling for a 503 from the API beyond its
existing generic error state, so extended downtime shows as "could not
reach the server" rather than a dedicated maintenance page.
