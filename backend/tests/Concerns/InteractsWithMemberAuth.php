<?php

namespace Tests\Concerns;

use App\Models\Member;
use App\Models\Tenant;
use Illuminate\Support\Facades\Hash;

trait InteractsWithMemberAuth
{
    protected function memberWithPassword(string $password = 'password123', ?Tenant $tenant = null, array $attributes = []): Member
    {
        $tenant ??= Tenant::default();

        return Member::factory()->create(array_merge([
            'tenant_id' => $tenant->id,
            'password' => Hash::make($password),
            'status' => 'Active',
        ], $attributes));
    }
}
