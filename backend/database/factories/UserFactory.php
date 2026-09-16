<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => fn () => Tenant::default()->id,
            'name' => fake()->name(),
            'photo' => 'https://i.pravatar.cc/200?img='.fake()->numberBetween(1, 70),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->e164PhoneNumber(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => fake()->randomElement(['Admin', 'Manager', 'Receptionist', 'Trainer', 'Accountant']),
            'status' => 'Active',
            'last_login_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * A platform-level user who isn't tied to any single gym (see
     * User::isPlatformAdmin() / Phase 18 §8).
     */
    public function platformAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => null,
            'is_platform_admin' => true,
            'role' => 'Super Admin',
        ]);
    }
}
