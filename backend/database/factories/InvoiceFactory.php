<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Member;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        $issueDate = $this->faker->dateTimeBetween('-3 months', 'now');

        return [
            'tenant_id' => fn () => Tenant::default()->id,
            'member_id' => Member::factory(),
            'issue_date' => $issueDate->format('Y-m-d'),
            'due_date' => (clone $issueDate)->modify('+14 days')->format('Y-m-d'),
            'total' => 0,
            'status' => $this->faker->randomElement(['Paid', 'Unpaid', 'Overdue', 'Draft']),
        ];
    }
}
