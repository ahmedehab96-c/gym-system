<?php

namespace Database\Factories;

use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'phone' => $this->faker->e164PhoneNumber(),
            'subject' => $this->faker->randomElement(['Membership inquiry', 'Personal training', 'Facility tour', 'General question']),
            'message' => $this->faker->paragraph(3),
            'handled' => $this->faker->boolean(30),
        ];
    }
}
