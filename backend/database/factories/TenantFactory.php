<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->company().' Gym',
            'slug' => $this->faker->unique()->slug(3),
            'email' => $this->faker->unique()->companyEmail(),
            'phone' => $this->faker->e164PhoneNumber(),
            'address' => $this->faker->address(),
            'status' => 'Active',
            'timezone' => 'UTC',
            'currency' => 'USD',
        ];
    }
}
