<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'actor_id' => User::factory()->platformAdmin(),
            'actor_name' => $this->faker->name(),
            'action' => 'gym.updated',
            'entity_type' => 'Tenant',
            'entity_id' => null,
            'tenant_id' => null,
            'description' => $this->faker->sentence(),
        ];
    }
}
