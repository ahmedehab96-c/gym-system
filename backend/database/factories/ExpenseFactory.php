<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    public function definition(): array
    {
        $category = $this->faker->randomElement(['Rent', 'Equipment', 'Maintenance', 'Salaries', 'Utilities', 'Marketing', 'Other']);

        return [
            'tenant_id' => fn () => Tenant::default()->id,
            'title' => $category.' - '.$this->faker->word(),
            'category' => $category,
            'amount' => $this->faker->numberBetween(500, 20000),
            'date' => $this->faker->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'vendor' => $this->faker->company(),
            'notes' => $this->faker->sentence(8),
        ];
    }
}
