<?php

namespace Database\Factories;

use App\Models\RolePermission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RolePermission>
 */
class RolePermissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'role' => $this->faker->randomElement(['Super Admin', 'Admin', 'Manager', 'Receptionist', 'Trainer', 'Accountant']),
            'module' => $this->faker->randomElement(['Members', 'Payments', 'Reports']),
            'can_view' => true,
            'can_create' => $this->faker->boolean(70),
            'can_edit' => $this->faker->boolean(60),
            'can_delete' => $this->faker->boolean(30),
        ];
    }
}
