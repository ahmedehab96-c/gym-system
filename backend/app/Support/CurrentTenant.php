<?php

namespace App\Support;

/**
 * Holds the tenant context for the current request, resolved once by
 * App\Http\Middleware\ResolveTenant right after authentication. Bound as
 * a singleton (see AppServiceProvider), so it's naturally reset between
 * requests/tests instead of leaking state.
 *
 * Three distinct states, all meaningful to App\Models\Concerns\TenantScope:
 *  - not resolved()  -> console (artisan/seeders) or a guest/public route
 *                       ran with no authenticated tenant user; queries are
 *                       left unscoped (legacy, pre-multi-tenant behavior).
 *  - resolved(), id() null -> an authenticated platform-level admin (not
 *                       tied to a single gym); queries are left unscoped
 *                       so they can see across every tenant.
 *  - resolved(), id() set  -> a normal tenant (gym) user; queries are
 *                       filtered to that tenant only.
 */
class CurrentTenant
{
    private ?int $id = null;

    private bool $resolved = false;

    public function id(): ?int
    {
        return $this->id;
    }

    public function resolved(): bool
    {
        return $this->resolved;
    }

    public function set(?int $id): void
    {
        $this->id = $id;
        $this->resolved = true;
    }
}
